<?php
namespace DspaceConnector\Job;

use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;

class Import extends AbstractJob
{
    protected $client;

    protected $apiUrl;

    protected $api;

    protected $limit;

    protected $termIdMap;

    protected $addedCount;

    protected $updatedCount;

    protected $itemSites;

    protected $itemSetIdArray;

    protected $ignoredFields;

    protected $originalIdentityMap;

    public function perform()
    {
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $this->addedCount = 0;
        $this->updatedCount = 0;
        $this->prepareTermIdMap();
        $this->client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $this->client->setHeaders(['Accept' => 'application/json']);
        $this->client->setOptions(['timeout' => 120]);
        $this->apiUrl = $this->getArg('api_url');
        $this->limit = $this->getArg('limit');
        $this->itemSiteArray = $this->getArg('itemSites', false);

        foreach (explode(',', $this->getArg('ignored_fields')) as $field) {
            $field = trim($field);
            if ($field !== '') {
                $this->ignoredFields[$field] = true;
            }
        }

        $comment = $this->getArg('comment');
        $dspaceImportJson = [
            'o:job' => ['o:id' => $this->job->getId()],
            'comment' => $comment,
            'added_count' => $this->addedCount,
            'updated_count' => $this->updatedCount,
        ];
        $response = $this->api->create('dspace_imports', $dspaceImportJson);
        $importRecordId = $response->getContent()->id();

        $this->originalIdentityMap = $this->getServiceLocator()->get('Omeka\EntityManager')->getUnitOfWork()->getIdentityMap();
        $this->importCollection($this->getArg('collection_link'));

        $dspaceImportJson = [
            'o:job' => ['o:id' => $this->job->getId()],
            'comment' => $comment,
            'added_count' => $this->addedCount,
            'updated_count' => $this->updatedCount,
        ];
        $response = $this->api->update('dspace_imports', $importRecordId, $dspaceImportJson);
    }

    public function importCollection($collectionLink)
    {
        $offset = 0;
        $hasNext = true;
        while ($hasNext) {
            $response = $this->getResponse($collectionLink, 'items', $offset);
            if ($response) {
                $collection = json_decode($response->getBody(), true);
                if ($collectionLink === '/rest/items'){
                    //import entire repository
                    $collectionResponse = $collection;
                } else {
                    //import collection
                    $collectionResponse = $collection['items'];
                }
                //set the item set id array. called here so that, if a new item set needs
                //to be created from the collection data, I have the data to do so
                $this->setItemSetIdArray($collection);
                $toCreate = [];
                $toUpdate = [];
                if (empty($collectionResponse)) {
                    // not a good way to really check, this just see if the last query
                    // got nothing
                    $hasNext = false;
                }
                foreach ($collectionResponse as $index => $itemData) {
                    $resourceJson = $this->buildResourceJson($itemData['link']);
                    $importRecord = $this->importRecord($resourceJson['remote_id'], $this->apiUrl);
                    //separate the items to create from those to update
                    if ($importRecord) {
                        // keep existing item sets/sites, add any new item sets/sites
                        $existingItem = $this->api->search('items', ['id' => $importRecord->item()->id()])->getContent();

                        $existingItemSets = array_keys($existingItem[0]->itemSets()) ?: [];
                        $newItemSets = $resourceJson['o:item_set'] ?: [];
                        $resourceJson['o:item_set'] = array_merge($existingItemSets, $newItemSets);

                        $existingItemSites = array_keys($existingItem[0]->sites()) ?: [];
                        $newItemSites = $resourceJson['o:site'] ?: [];
                        $resourceJson['o:site'] = array_merge($existingItemSites, $newItemSites);
                        //add the Omeka S item id to the itemJson
                        //and key by the importRecordid for reuse
                        //in both updating the item itself, and the importRecord
                        $resourceJson['id'] = $importRecord->item()->id();
                        $toUpdate[$importRecord->id()] = $resourceJson;
                    } else {
                        $toCreate["create" . $index] = $resourceJson;
                    }
                }
                $this->createItems($toCreate);
                $this->updateItems($toUpdate);

                $offset = $offset + $this->limit;
            }
        }
    }

    public function buildResourceJson($itemLink)
    {
        $response = $this->getResponse($itemLink, 'metadata,bitstreams');
        if ($response) {
            $itemArray = json_decode($response->getBody(), true);
        }
        $itemJson = [];
        if ($this->itemSetIdArray) {
            $itemJson['o:item_set'] = $this->itemSetIdArray;
        }
        if ($this->itemSiteArray) {
            foreach ($this->itemSiteArray as $itemSite) {
                $itemSites[] = $itemSite;
            }
            $itemJson['o:site'] = $itemSites;
        } else {
            $itemJson['o:site'] = [];
        }
        $itemJson = $this->processItemMetadata($itemArray['metadata'], $itemJson);
        //stuff some data that's not relevant to Omeka onto the JSON array
        //for later reuse during create and update operations
        if (isset($itemArray['uuid'])) {
            $itemJson['remote_id'] = $itemArray['uuid'];
        } elseif (isset($itemArray['id'])) {
            $itemJson['remote_id'] = $itemArray['id'];
        }
        $itemJson['handle'] = $itemArray['handle'];
        $itemJson['lastModified'] = $itemArray['lastModified'];

        if ($this->getArg('ingest_files')) {
            $itemJson = $this->processItemBitstreams($itemArray['bitstreams'], $itemJson);
        }
        return $itemJson;
    }

    public function processItemMetadata($itemMetadataArray, $itemJson)
    {
        foreach ($itemMetadataArray as $metadataEntry) {
            $termArray = $this->mapKeyToTerm($metadataEntry['key']);
            if (!$termArray) {
                continue;
            }

            $valueArray = [];
            if ($termArray['name'] == 'bibo:uri') {
                $valueArray['@id'] = $metadataEntry['value'];
                $valueArray['type'] = 'uri';
            } else {
                $valueArray['@value'] = $metadataEntry['value'];
                if (isset($metadataEntry['language'])) {
                    $valueArray['@language'] = $metadataEntry['language'];
                }
                $valueArray['type'] = 'literal';
            }
            $valueArray['property_id'] = $termArray['term_id'];
            $itemJson[$termArray['name']][] = $valueArray;
        }
        return $itemJson;
    }

    public function processItemBitstreams($bitstreamsArray, $itemJson)
    {
        foreach ($bitstreamsArray as $bitstream) {
            if (isset($bitstream['bundleName']) && $bitstream['bundleName'] == 'ORIGINAL') {
                $itemJson['o:media'][] = [
                    'o:ingester' => 'url',
                    'o:data' => json_encode($bitstream),
                    'o:source' => $this->apiUrl . $bitstream['link'],
                    'ingest_url' => $this->apiUrl . $bitstream['link'] . '/retrieve',
                    'dcterms:title' => [
                        [
                            'type' => 'literal',
                            '@language' => '',
                            '@value' => $bitstream['name'],
                            'property_id' => $this->termIdMap['dcterms:title'],
                        ],
                    ],
                ];
            }
        }
        return $itemJson;
    }

    public function getResponse($link, $expand = 'all', $offset = 0)
    {
        //work around some dspace api versions reporting RESTapi instead of rest in the link
        $link = str_replace('RESTapi', 'rest', $link);

        $this->client->setUri($this->apiUrl . $link);
        $this->client->setParameterGet(['expand' => $expand,
                                        'limit' => $this->limit,
                                        'offset' => $offset,
                                       ]);
        $response = $this->client->send();
        if (!$response->isSuccess()) {
            throw new Exception\RuntimeException(sprintf(
                'Requested "%s" got "%s".', $this->apiUrl . $link, $response->renderStatusLine()
            ));
        }
        return $response;
    }

    protected function mapKeyToTerm($key)
    {
        $termArray = [];
        if (isset($this->ignoredFields[$key])) {
            return null;
        }

        $parts = explode('.', $key);
        // Only attempt to read from Dublin Core elements
        if ($parts[0] != 'dc') {
            return null;
        }

        switch (count($parts)) {
            case 3:
                //parse out refinements where known
                switch ($parts[2]) {
                    case 'author':
                        $term = "dcterms:creator";
                        break;

                    case 'uri':
                        $term = "bibo:uri";
                        break;

                    default:
                        $term = 'dcterms:' . $parts[2];
                }

                if (isset($this->termIdMap[$term])) {
                    $termArray['name'] = $term;
                    $termArray['term_id'] = $this->termIdMap[$term];
                    return $termArray;
                }

                // break purposely omitted; falls back to "base" term
            case 2:
                $term = 'dcterms:' . $parts[1];

                if (isset($this->termIdMap[$term])) {
                    $termArray['name'] = $term;
                    $termArray['term_id'] = $this->termIdMap[$term];
                    return $termArray;
                }
            default:
                return null;
        }
    }

    protected function prepareTermIdMap()
    {
        $this->termIdMap = [];
        $properties = $this->api->search('properties', [
            'vocabulary_namespace_uri' => 'http://purl.org/dc/terms/',
        ])->getContent();
        foreach ($properties as $property) {
            $term = "dcterms:" . $property->localName();
            $this->termIdMap[$term] = $property->id();
        }

        $properties = $this->api->search('properties', [
            'vocabulary_namespace_uri' => 'http://purl.org/ontology/bibo/',
        ])->getContent();
        foreach ($properties as $property) {
            $term = "bibo:" . $property->localName();
            $this->termIdMap[$term] = $property->id();
        }
    }

    protected function setItemSetIdArray($collection)
    {
        if (! is_null($this->itemSetIdArray)) {
            return;
        }
        $itemSetIds = $this->getArg('itemSets', false);
        if ($itemSetIds) {
            foreach ($itemSetIds as $itemSetId) {
                if ($itemSetId == 'new') {
                    $itemSet = $this->createItemSet($collection);
                    $itemSets[] = $itemSet->id();
                } else {
                    $itemSets[] = $itemSetId;
                }
            }
            $this->itemSetIdArray = $itemSets;
        }
    }

    protected function createItemSet($collection)
    {
        $itemSetData = [];
        $itemSetData['dcterms:title'] = [
                ['@value' => $collection['name'],
                      'property_id' => $this->termIdMap['dcterms:title'],
                      'type' => 'literal',
                ], ];

        $itemSetData['dcterms:license'] = [
                ['@value' => $collection['license'],
                      'property_id' => $this->termIdMap['dcterms:license'],
                      'type' => 'literal',
                ], ];

        $itemSetData['dcterms:rights'] = [
                ['@value' => $collection['copyrightText'],
                      'property_id' => $this->termIdMap['dcterms:rights'],
                       'type' => 'literal',
                ], ];

        $itemSetData['dcterms:description'] = [
                ['@value' => $collection['shortDescription'],
                      'property_id' => $this->termIdMap['dcterms:description'],
                      'type' => 'literal',
                ],
                ['@value' => $collection['introductoryText'],
                      'property_id' => $this->termIdMap['dcterms:description'],
                      'type' => 'literal',
                ], ];

        $response = $this->api->create('item_sets', $itemSetData);
        return $response->getContent();
    }

    protected function createItems($toCreate)
    {
        $createResponse = $this->api->batchCreate('items', $toCreate, [], ['continueOnError' => true]);
        $this->addedCount = $this->addedCount + count($createResponse->getContent());

        $createImportRecordsJson = [];
        $createContent = $createResponse->getContent();

        foreach ($createContent as $id => $resourceReference) {
            //get the original data used for individual item creation
            $toCreateData = $toCreate[$id];

            $dspaceItemJson = [
                            'o:job' => ['o:id' => $this->job->getId()],
                            'o:item' => ['o:id' => $resourceReference->id()],
                            'api_url' => $this->apiUrl,
                            'remote_id' => $toCreateData['remote_id'],
                            'handle' => $toCreateData['handle'],
                            'last_modified' => new \DateTime($toCreateData['lastModified']),
                        ];
            $createImportRecordsJson[] = $dspaceItemJson;
        }

        $createImportRecordResponse = $this->api->batchCreate('dspace_items', $createImportRecordsJson, [], ['continueOnError' => true]);
    }

    protected function updateItems($toUpdate)
    {
        //  batchUpdate would be nice, but complexities abound. See https://github.com/omeka/omeka-s/issues/326
        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $updateResponses = [];
        foreach ($toUpdate as $importRecordId => $itemJson) {
            $this->updatedCount = $this->updatedCount + 1;
            $updateResponses[$importRecordId] = $this->api->update('items', $itemJson['id'], $itemJson, [], ['flushEntityManager' => false]);
        }

        foreach ($updateResponses as $importRecordId => $resourceReference) {
            $toUpdateData = $toUpdate[$importRecordId];
            $dspaceItemJson = [
                            'o:job' => ['o:id' => $this->job->getId()],
                            'remote_id' => $toUpdateData['remote_id'],
                            'last_modified' => new \DateTime($toUpdateData['lastModified']),
                        ];
            $updateImportRecordResponse = $this->api->update('dspace_items', $importRecordId, $dspaceItemJson, [], ['flushEntityManager' => false]);
        }
        $em->flush();
        $this->detachAllNewEntities($this->originalIdentityMap);
    }

    protected function importRecord($remoteId, $apiUrl)
    {
        //see if the item has already been imported
        $response = $this->api->search('dspace_items',
                                        ['remote_id' => $remoteId,
                                              'api_url' => $apiUrl,
                                            ]);
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }
    
    /**
     * Given an old copy of the Doctrine identity map, reset
     * the entity manager to that state by detaching all entities that
     * did not exist in the prior state.
     *
     * @internal This is a copy-paste of the functionality from the abstract entity adapter
     *
     * @param array $oldIdentityMap
     */
    protected function detachAllNewEntities(array $oldIdentityMap)
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $identityMap = $entityManager->getUnitOfWork()->getIdentityMap();
        foreach ($identityMap as $entityClass => $entities) {
            foreach ($entities as $idHash => $entity) {
                if (!isset($oldIdentityMap[$entityClass][$idHash])) {
                    $entityManager->detach($entity);
                }
            }
        }
    }
}
