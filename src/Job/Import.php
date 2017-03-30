<?php
namespace DspaceConnector\Job;

use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;
use DspaceConnector\Entity\DspaceItem;
use Zend\Http\Client;
use SimpleXMLElement;

class Import extends AbstractJob
{
    protected $client;
    
    protected $apiUrl;
    
    protected $api;
    
    protected $termIdMap;
    
    protected $addedCount;
    
    protected $updatedCount;
    
    protected $itemSetId;
    
    public function perform()
    {
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $this->addedCount = 0;
        $this->updatedCount = 0;
        $this->prepareTermIdMap();
        $this->client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $this->client->setHeaders(array('Accept' => 'application/json'));
        $this->apiUrl = $this->getArg('api_url');
        $this->importCollection($this->getArg('collection_link'));
        $comment = $this->getArg('comment');
        $dspaceImportJson = array(
                            'o:job'         => array('o:id' => $this->job->getId()),
                            'comment'       => $comment,
                            'added_count'   => $this->addedCount,
                            'updated_count' => $this->updatedCount
                          );
        $response = $this->api->create('dspace_imports', $dspaceImportJson);
    }

    public function importCollection($collectionLink)
    {
        $offset = 0;
        $hasNext = true;
        while ($hasNext) {
            $response = $this->getResponse($collectionLink, 'items', $offset);
            if ($response) {
                $collection = json_decode($response->getBody(), true);
                //set the item set id. called here so that, if a new item set needs
                //to be created from the collection data, I have the data to do so
                $this->setItemSetId($collection);
                $toCreate = array();
                $toUpdate = array();
                if (empty($collection['items'])) {
                    // not a good way to really check, this just see if the last query
                    // got nothing
                    $hasNext = false;
                }
                foreach ($collection['items'] as $index => $itemData) {
                    $resourceJson = $this->buildResourceJson($itemData['link']);
                    $importRecord = $this->importRecord($resourceJson['remote_id'], $this->apiUrl);
                    //separate the items to create from those to update
                    if ($importRecord) {
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
                
                // limit is hardcoded at 50, so bump to next page
                $offset = $offset + 50;
            }
         }
     }

      public function buildResourceJson($itemLink)
      {

        $response = $this->getResponse($itemLink, 'metadata,bitstreams');
        if ($response) {
            $itemArray = json_decode($response->getBody(), true);
        }
        $itemJson = array();
        if ($this->itemSetId) {
            $itemJson['o:item_set'] = array(array('o:id' => $this->itemSetId));
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
            $terms = $this->mapKeyToTerm($metadataEntry['key']);

            foreach ($terms as $term) {
                //skip non-understood or mis-written terms
                if (isset($this->termIdMap[$term])) {
                    $valueArray = array();
                    if ($term == 'bibo:uri') {
                        $valueArray['@id'] = $metadataEntry['value'];
                        $valueArray['type'] = 'uri';
                    } else {
                        $valueArray['@value'] = $metadataEntry['value'];
                        if (isset($metadataEntry['language'])) {
                            $valueArray['@language'] = $metadataEntry['language'];
                        }
                        $valueArray['type'] = 'literal';
                    }
                    $valueArray['property_id'] = $this->termIdMap[$term];
                    $itemJson[$term][] = $valueArray;
                }
            }
        }
        return $itemJson;
    }
    
    public function processItemBitstreams($bitstreamsArray, $itemJson)
    {
        foreach($bitstreamsArray as $bitstream) {
            $itemJson['o:media'][] = array(
                'o:ingester'     => 'url',
                'o:data'     => json_encode($bitstream),
                'o:source'   => $this->apiUrl . $bitstream['link'],
                'ingest_url' => $this->apiUrl . $bitstream['link'] . '/retrieve',
                'dcterms:title' => array(
                    array(
                        '@value' => $bitstream['name'],
                        'property_id' => $this->termIdMap['dcterms:title']
                    ),
                ),
            );
        }
        return $itemJson;
    }

    public function getResponse($link, $expand = 'all', $offset = 0)
    {
        //work around some dspace api versions reporting RESTapi instead of rest in the link
        $link = str_replace('RESTapi', 'rest', $link);
        $this->client->setUri($this->apiUrl . $link);
        $this->client->setParameterGet(['expand' => $expand,
                                        'limit'  => 50,
                                        'offset' => $offset
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
        $parts = explode('.', $key);
        //only using dc. Don't know if DSpace ever emits anything else
        //(except for the subproperties listed below that aren't actually in dcterms
        if ($parts[0] != 'dc') {
            return array();
        }
        
        if (count($parts) == 2) {
            return array('dcterms:' . $parts[1]);
        }
        
        if (count($parts) == 3) {
            //liberal mapping onto superproperties by default
            $termsArray = array('dcterms:' . $parts[1]);
            //parse out refinements where known
            switch ($parts[2]) {
                case 'author' :
                    $termsArray[] = "dcterms:creator";
                break;
                
                case 'abstract' :
                    $termsArray[] = "dcterms:abstract";
                break;
                
                case 'uri' : 
                    $termsArray[] = "bibo:uri";
                break;
                case 'iso' : //handled by superproperty dcterms:language
                case 'editor' : //handled as dcterms:contributor
                case 'accessioned' : //ignored
                break;
                default :
                    $termsArray[] = 'dcterms:' . $parts[2]; 
            }
            return $termsArray;
        }
    }
    
    protected function prepareTermIdMap()
    {
        $this->termIdMap = array();
        $properties = $this->api->search('properties', array(
            'vocabulary_namespace_uri' => 'http://purl.org/dc/terms/'
        ))->getContent();
        foreach ($properties as $property) {
            $term = "dcterms:" . $property->localName();
            $this->termIdMap[$term] = $property->id();
        }

        $properties = $this->api->search('properties', array(
            'vocabulary_namespace_uri' => 'http://purl.org/ontology/bibo/'
        ))->getContent();
        foreach ($properties as $property) {
            $term = "bibo:" . $property->localName();
            $this->termIdMap[$term] = $property->id();
        }
    }
    
    protected function setItemSetId($collection)
    {
        $itemSetId = $this->getArg('itemSet', false);
        if ($itemSetId == 'new') {
            $itemSet = $this->createItemSet($collection);
            $this->itemSetId = $itemSet->id();
        } else {
            $this->itemSetId = $itemSetId; 
        }
    }
    
    protected function createItemSet($collection)
    {
        $itemSetData = array();
        $titlePropId = $this->termIdMap['dcterms:title'];
        $descriptionPropId = $this->termIdMap['dcterms:description'];
        $rightsPropId = $this->termIdMap['dcterms:rights'];
        $licensePropId = $this->termIdMap['dcterms:license'];
        $itemSetData['dcterms:title'] = array(
                array('@value' => $collection['name'], 
                      'property_id' => $this->termIdMap['dcterms:title']
                ));
        $itemSetData['dcterms:license'] = array(
                array('@value' => $collection['license'], 
                      'property_id' => $this->termIdMap['dcterms:license']
                ));
        
        $itemSetData['dcterms:rights'] = array(
                array('@value' => $collection['copyrightText'], 
                      'property_id' => $this->termIdMap['dcterms:rights']
                ));
        
        $itemSetData['dcterms:description'] = array(
                array('@value' => $collection['shortDescription'], 
                      'property_id' => $this->termIdMap['dcterms:description']
                ),
                array('@value' => $collection['introductoryText'], 
                      'property_id' => $this->termIdMap['dcterms:description']
                ));
        $response = $this->api->create('item_sets', $itemSetData);
        return $response->getContent();
    }
    
    protected function createItems($toCreate) 
    {
        $createResponse = $this->api->batchCreate('items', $toCreate, array(), ['continueOnError' => true]);
        $this->addedCount = $this->addedCount + count($createResponse->getContent());

        $createImportRecordsJson = array();
        $createContent = $createResponse->getContent();
        
        foreach($createContent as $id=>$resourceReference) {
            //get the original data used for individual item creation
            $toCreateData = $toCreate[$id];
            
            $dspaceItemJson = array(
                            'o:job'     => array('o:id' => $this->job->getId()),
                            'o:item'    => array('o:id' => $resourceReference->id()),
                            'api_url'   => $this->apiUrl,
                            'remote_id' => $toCreateData['remote_id'],
                            'handle'    => $toCreateData['handle'],
                            'last_modified' => new \DateTime($toCreateData['lastModified'])
                        );
            $createImportRecordsJson[] = $dspaceItemJson;
        }
        
        $createImportRecordResponse = $this->api->batchCreate('dspace_items', $createImportRecordsJson, array(), ['continueOnError' => true]);
    }
    
    protected function updateItems($toUpdate)
    {
        //  batchUpdate would be nice, but complexities abound. See https://github.com/omeka/omeka-s/issues/326
        $updateResponses = array();
        foreach ($toUpdate as $importRecordId=>$itemJson) {
            $this->updatedCount = $this->updatedCount + 1;
            $updateResponses[$importRecordId] = $this->api->update('items', $itemJson['id'], $itemJson);
        }
        
        foreach ($updateResponses as $importRecordId => $resourceReference) {
            $toUpdateData = $toUpdate[$index];
            $dspaceItemJson = array(
                            'o:job'     => array('o:id' => $this->job->getId()),
                            'remote_id' => $toUpdateData['remote_id'],
                            'last_modified' => new \DateTime($toUpdateData['lastModified'])
                        );
            $updateImportRecordResponse = $this->api->update('dspace_items', $importRecordId, $dspaceItemJson);
        }
    }
    
    protected function importRecord($remoteId, $apiUrl)
    {
        //see if the item has already been imported
        $response = $this->api->search('dspace_items',
                                        array('remote_id' => $remoteId,
                                              'api_url'   => $apiUrl
                                            ));
        $content = $response->getContent();
        if (empty($content)) {
            return false;
        }
        return $content[0];
    }
}