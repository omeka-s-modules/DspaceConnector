<?php
namespace DspaceConnector\Job;

use Omeka\Job\AbstractJob;
use Omeka\Job\Exception;
use DspaceConnector\Model\Entity\DspaceItem;
use Zend\Http\Client;
use SimpleXMLElement;

class Import extends AbstractJob
{
    protected $client;
    
    protected $apiUrl;
    
    protected $api;
    
    protected $termIdMap;
    
    public function perform()
    {
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $this->prepareTermIdMap();
        $this->client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $this->client->setHeaders(array('Accept' => 'application/json'));
        $this->apiUrl = $this->getArg('api_url');
        $this->importCollection($this->getArg('collection_link'));
    }

    public function importCollection($collectionLink)
    {
        $response = $this->getResponse($collectionLink, 'items');
        if ($response) {
            $collection = json_decode($response->getBody(), true);
            foreach ($collection['items'] as $itemData) {
                $this->importItem($itemData['link']);
            }
        }
    }

    public function importItem($itemLink)
    {
        $response = $this->getResponse($itemLink, 'metadata,bitstreams');
        if ($response) {
            $itemArray = json_decode($response->getBody(), true);
        }
        $dspaceItem = new DspaceItem;
        $dspaceItem->setHandle($itemArray['handle']);
        $dspaceItem->setRemoteId($itemArray['id']);
        $itemJson = array();
        $itemJson = $this->processItemMetadata($itemArray['metadata'], $itemJson);
        if ($this->getArg('ingest_files')) {
            $itemJson = $this->processItemBitstreams($itemArray['bitstreams'], $itemJson);
        }
        $this->api->create('items', $itemJson);
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
                    } else {
                        $valueArray['@value'] = $metadataEntry['value'];
                        if (isset($metadataEntry['language'])) {
                            $valueArray['@language'] = $metadataEntry['language'];
                        }
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
                'o:type'     => 'file',
                'o:data'     => json_encode($bitstream),
                'o:source'   => $this->apiUrl . $bitstream['link'],
                'ingest_uri' => $this->apiUrl . '/rest' . $bitstream['retrieveLink'],
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

    public function getResponse($link, $expand = 'all')
    {
        $this->client->setUri($this->apiUrl . $link);
        $this->client->setParameterGet(array('expand' => $expand));
        
        $response = $this->client->send();
        if (!$response->isSuccess()) {
            throw new Exception\RuntimeException(sprintf(
                'Requested "%s" got "%s".', $url, $response->renderStatusLine()
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
}