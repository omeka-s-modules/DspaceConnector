<?php
namespace DspaceConnector\Job;

use Omeka\Job\AbstractJob;

class Undo extends AbstractJob
{
    public function perform()
    {
        $jobId = $this->getArg('jobId');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('dspace_items', array('job_id' => $jobId));
        $dspaceItems = $response->getContent();
        if ($dspaceItems) {
            foreach ($dspaceItems as $dspaceItem) {
                $dspaceResponse = $api->delete('dspace_items', array('id' => $dspaceItem->id()));
                if ($dspaceResponse->isError()) {
                }

                $itemResponse = $api->delete('items', array('id' => $dspaceItem->item()->id()));
                if ($itemResponse->isError()) {
                }
            }
        }
    }
}