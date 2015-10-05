<?php 
namespace DspaceConnector\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class DspaceItemRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return array(
            'last_modified' => $this->getLastModified(),
            'api_url'       => $this->getApiUrl(),
            'remote_id'     => $this->getRemoteId(),
            'handle'        => $this->getHandle(),
            'o:item'        => $this->getReference(),
            'o:job'         => $this->getReference()
        );
    }
    
    public function getJsonLdType()
    {
        return 'o:DspaceItem';
    }

    public function lastModified()
    {
        return $this->getData()->getlastModified();
    }
    
    public function apiUrl()
    {
        return $this->getData()->getApiUrl();
    }
    
    public function remoteId()
    {
        return $this->getData()->getRemoteId();
    }
    
    public function handle()
    {
        return $this->getData()->getHandle();
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation(null, $this->getData()->getItem());
    }
    
    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation(null, $this->getData()->getJob());
    }
}




