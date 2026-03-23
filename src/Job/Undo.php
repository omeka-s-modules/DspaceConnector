<?php
namespace DspaceConnector\Job;

use Omeka\Job\AbstractJob;

class Undo extends AbstractJob
{
    protected $deletedItemCount;

    public function perform()
    {
        $jobId = $this->getArg('previous_job');
        $comment = $this->getArg('comment');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');

        // Delete items
        $response = $api->search('dspace_items', ['job_id' => $jobId]);
        $dspaceItems = $response->getContent();
        $deletedItemCount = 0;
        $deletedFileCount = 0;
        if ($dspaceItems) {
            foreach ($dspaceItems as $dspaceItem) {
                $deletedFileCount += count($dspaceItem->item()->media());
                $api->delete('dspace_items', $dspaceItem->id());
                $api->delete('items', $dspaceItem->item()->id());
                $deletedItemCount++;
            }
        }

        $commentParts = array_filter([
            $comment,
            $deletedItemCount ? $deletedItemCount . ' items deleted' : null,
            $deletedFileCount ? $deletedFileCount . ' files deleted' : null,
        ]);
        $comment = implode('; ', $commentParts);
        $dspaceImportJson = [
                            'o:job' => ['o:id' => $this->job->getId()],
                            'comment' => $comment,
                            'added_count' => 0,
                            'updated_count' => 0,
                            'added_files' => 0,
                          ];
        $response = $api->create('dspace_imports', $dspaceImportJson);
        $jobArgs = $this->job->getArgs();
        $jobArgs['comment'] = $comment;
        $this->job->setArgs($jobArgs);
    }
}
