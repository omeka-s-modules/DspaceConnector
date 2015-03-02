<?php
namespace DpaceConnector\Model\Entity;

use Omeka\Model\Entity\AbstractEntity;
use Omeka\Model\Entity\Job;

/**
 * @Entity
 */
class DspaceItem extends AbstractEntity
{
    
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    
    /**
     * @OneToOne(targetEntity="Omeka\Model\Entity\Item")
     * @var int
     */
    protected $itemId;

    /**
     * @OneToOne(targetEntity="Omeka\Model\Entity\Job")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $job;
    
    /**
     * @Column(type="string")
     * @var string
     */
    protected $apiUrl;
    
    /**
     * @Column(type="integer")
     * @var int
     */
    protected $remoteId;
    
    protected $handle;
    
    protected $lastModified;
    
    public function getId()
    {
        return $this->id;
    }

    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function getJob()
    {
        return $this->job;
    }
    
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }
    
    public function getApiUrl()
    {
        return $this->apiUrl;
    }
    
    public function setRemoteId($remoteId)
    {
        $this->remoteId = $remoteId;
    }
    
    public function getRemoteId()
    {
        return $this->remoteId;
    }
    
    public function setHandle($handle)
    {
        //@todo ensure uri form for handle
        $this->handle = $handle;
    }
    
    public function getHandle() 
    {
        return $this->handle;
    }
    
    public function setLastModified($lastModified) 
    {
        $this->lastModified = $lastModified;
    }
    
    public function getLastModified()
    {
        return $this->lastModified;
    }
}