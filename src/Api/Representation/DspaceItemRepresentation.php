<?php
namespace DspaceConnector\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class DspaceItemRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return [
            'last_modified' => $this->lastModified(),
            'api_url' => $this->apiUrl(),
            'remote_id' => $this->remoteId(),
            'handle' => $this->handle(),
            'o:item' => $this->item(),
            'o:job' => $this->job(),
        ];
    }

    public function getJsonLdType()
    {
        return 'o:DspaceItem';
    }

    public function lastModified()
    {
        return $this->resource->getlastModified();
    }

    public function apiUrl()
    {
        return $this->resource->getApiUrl();
    }

    public function remoteId()
    {
        return $this->resource->getRemoteId();
    }

    public function handle()
    {
        return $this->resource->getHandle();
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }
}
