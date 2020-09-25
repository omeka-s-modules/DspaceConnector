<?php
namespace DspaceConnector\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class DspaceItemAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'DspaceConnector\Entity\DspaceItem';
    }

    public function getResourceName()
    {
        return 'dspace_items';
    }

    public function getRepresentationClass()
    {
        return 'DspaceConnector\Api\Representation\DspaceItemRepresentation';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['remote_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.remoteId',
                $this->createNamedParameter($qb, $query['remote_id']))
            );
        }

        if (isset($query['api_url'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.apiUrl',
                $this->createNamedParameter($qb, $query['api_url']))
            );
        }

        if (isset($query['job_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.job',
                $this->createNamedParameter($qb, $query['job_id']))
            );
        }

        if (isset($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.item',
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (isset($data['o:job']['o:id'])) {
            $job = $this->getAdapter('jobs')->findEntity($data['o:job']['o:id']);
            $entity->setJob($job);
        }
        if (isset($data['api_url'])) {
            $entity->setApiUrl($data['api_url']);
        }
        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }
        if (isset($data['remote_id'])) {
            $entity->setRemoteId($data['remote_id']);
        }
        if (isset($data['handle'])) {
            $entity->setHandle($data['handle']);
        }
        if (isset($data['last_modified'])) {
            $entity->setLastModified($data['last_modified']);
        }
    }
}
