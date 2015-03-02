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
    
    public function perform()
    {
        $this->api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $itemUrl = '';
        
        $this->importItem($itemUrl);
    }
    
    public function importItem($itemUrl)
    {
        
        //$itemXml = simplexml_load_file($itemUrl);
        $itemXml = $this->client->getResponse($itemUrl);
        $dspaceItem = new DspaceItem;
        $dspaceItem->setHandle($itemXml->handle);
        $dspaceItem->setRemoteId($itemXml->id);
        //$itemMetadataXml = simplexml_load_file($itemUrl . '/metadata');
        $itemMetadataXml = $this->client->getResponse($itemUrl . '/metadata');
        $itemArray = array();
        $this->processItemMetadata($itemMetadataXml, $itemArray);
    }
    
    public function processItemMetadata(SimpleXMLElement $itemMetadataXml, $itemArray)
    {
        foreach ($itemMetadataXml->metadataentry as $metadataEntry) {
            $terms = $this->mapKeyToTerm($metadataEntry->key);

            foreach ($terms as $term) {
                $valueArray = array();
                if ($term == 'bibo:uri') {
                    $valueArray['@id'] = $metadataEntry->value;
                } else {
                    $valueArray['@value'] = $metadataEntry->value;
                    if ($metadataEntry->language) {
                        $valueArray['@language'] = $metadataEntry->language;
                    }
                }
                $itemArray[$term][] = $valueArray;                    
            }

        }
    }
    
    public function processItemBitstreams($item)
    {
        
    }
    
    protected function mapKeyToTerm($key)
    {
        $parts = explode('.', $key);
        if ($parts[0] != 'dc') {
            return array();
        }
        
        if (count($parts) == 2) {
            return array('dcterms:' . $parts[1]);
        }
        
        if (count($parts) == 3) {
            //liberal mapping onto refined term always
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
                    $termsArray[] = "dcterms:identifier";
                    $termsArray[] = "bibo:uri";
                break;
                default :
                    $termsArray[] = 'dcterms:' . $parts[2]; 
            }
            return $termsArray;
        }
    }
}