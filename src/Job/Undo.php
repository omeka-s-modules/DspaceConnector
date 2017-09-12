<?php
namespace DspaceConnector\Job;

use Omeka\Job\AbstractJob;

class Undo extends AbstractJob
{
    public function perform()
    {
        $jobId = $this->getArg('jobId');
        echo $jobId;
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('dspace_items', ['job_id' => $jobId]);
        $dspaceItems = $response->getContent();
        if ($dspaceItems) {
            foreach ($dspaceItems as $dspaceItem) {
                $dspaceResponse = $api->delete('dspace_items', $dspaceItem->id());
                $itemResponse = $api->delete('items', $dspaceItem->item()->id());
            }
        }
    }
}
