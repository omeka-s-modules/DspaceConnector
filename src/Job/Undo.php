<?php
namespace DspaceConnector\Job;

use Omeka\Job\AbstractJob;

class Undo extends AbstractJob
{
    protected $deletedCount;

    public function perform()
    {
        $jobId = $this->getArg('previous_job');
        $comment = $this->getArg('comment');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');

        // Delete items
        $response = $api->search('dspace_items', ['job_id' => $jobId]);
        $dspaceItems = $response->getContent();
        if ($dspaceItems) {
            foreach ($dspaceItems as $dspaceItem) {
                $dspaceResponse = $api->delete('dspace_items', $dspaceItem->id());
                $itemResponse = $api->delete('items', $dspaceItem->item()->id());
                $deletedCount++;
            }
        }

        if ($deletedCount) {
            $deletedComment = $deletedCount . ' items deleted';
            $comment = strlen($comment) ? $comment . '; ' . $deletedComment : $deletedComment;
        }
        $dspaceImportJson = [
                            'o:job' => ['o:id' => $this->job->getId()],
                            'comment' => $comment,
                            'added_count' => 0,
                            'updated_count' => 0,
                          ];
        $response = $api->create('dspace_imports', $dspaceImportJson);
        $jobArgs = $this->job->getArgs();
        $jobArgs['comment'] = $comment;
        $this->job->setArgs($jobArgs);
    }
}
