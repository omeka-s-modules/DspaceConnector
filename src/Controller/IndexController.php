<?php
namespace DspaceConnector\Controller;

use Omeka\Stdlib\Message;
use DspaceConnector\Form\ImportForm;
use DspaceConnector\Form\UrlForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $client;

    protected $limit = 20;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function indexAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(UrlForm::class);
        $view->setVariable('form', $form);
        return $view;
    }

    public function importAction()
    {
        $view = new ViewModel;
        $params = $this->params()->fromPost();
        $this->limit = $params['limit'];
        if (isset($params['collection_link'])) {
            //coming from the import page, do the import
            $importForm = $this->getForm(ImportForm::class);
            $importForm->setData($params);
            if (! $importForm->isValid()) {
                $this->messenger()->addError('There was an error during validation'); // @translate
                return $view;
            }

            $job = $this->jobDispatcher()->dispatch('DspaceConnector\Job\Import', $params);
            $view->setVariable('job', $job);
            $message = new Message('Importing in Job ID %s', // @translate
                $job->getId());
            $this->messenger()->addSuccess($message);
            return $this->redirect()->toRoute('admin/dspace-connector/past-imports');
        } else {
            //coming from the index page, dig up data from the endpoint url
            $urlForm = $this->getForm(UrlForm::class);
            $urlForm->setData($params);
            if (! $urlForm->isValid()) {
                $this->messenger()->addError('There was an error during validation'); // @translate
                return $this->redirect()->toRoute('admin/dspace-connector');
            }

            $importForm = $this->getForm(ImportForm::class);
            $dspaceUrl = rtrim($params['api_url'], '/');

            try {
                $communities = $this->fetchData($dspaceUrl . '/' . $params['endpoint'] . '/communities', 'collections');
                $repository = '/' . $params['endpoint'] . '/items';
            } catch (\Exception $e) {
                $this->logger()->err($this->translate('Error importing data'));
                $this->logger()->err($e);
            }
            $view->setVariable('communities', $communities);
            $view->setVariable('repository', $repository);
            $view->setVariable('dspace_url', $dspaceUrl);
            $view->setVariable('form', $importForm);
            $view->setVariable('limit', $this->limit);
            return $view;
        }
    }

    /**
     * @param string $link either 'collections' or 'communities'
     * @throws \RuntimeException
     */
    protected function fetchData($endpoint, $expand = null)
    {
        $this->client->setHeaders(['Accept' => 'application/json'])->setOptions(['timeout' => 60]);
        $this->client->setUri($endpoint);
        $offset = 0;
        $limit = $this->limit;
        $getParams = [
            'expand' => $expand,
            'offset' => $offset,
            'limit' => $limit,
        ];
        $this->client->setParameterGet($getParams);
        $fullResponse = [];

        $hasNext = true;
        while ($hasNext) {
            $response = $this->client->send();
            if (!$response->isSuccess()) {
                $this->logger()->err(sprintf('Requested "%s" got "%s".', $endpoint, $response->renderStatusLine()));
                $this->messenger()->addError('There was an error retrieving data. Please try again.'); // @translate
            }
            $responseBody = json_decode($response->getBody(), true);
            if (empty($responseBody)) {
                $hasNext = false;
            } else {
                $offset = $offset + $limit;
                $getParams['offset'] = $offset;
                $this->client->setParameterGet($getParams);
                $fullResponse = array_merge($responseBody, $fullResponse);
            }
        }
        return $fullResponse;
    }

    public function pastImportsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            if (isset($data['jobs'])) {
                $undoJobIds = [];
                foreach ($data['jobs'] as $jobId) {
                    $undoJob = $this->undoJob($jobId);
                    $undoJobIds[] = $undoJob->getId();
                }
                $message = new Message('Undo in progress in the following jobs: %s', // @translate
                    implode(', ', $undoJobIds));
                $this->messenger()->addSuccess($message);
            } else {
                $this->messenger()->addError('Error: no jobs selected to undo'); // @translate
            }
        }
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + [
            'page' => $page,
            'sort_by' => $this->params()->fromQuery('sort_by', 'id'),
            'sort_order' => $this->params()->fromQuery('sort_order', 'desc'),
        ];
        $response = $this->api()->search('dspace_imports', $query);
        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('imports', $response->getContent());
        return $view;
    }

    protected function undoJob($jobId)
    {
        $response = $this->api()->search('dspace_imports', ['job_id' => $jobId]);
        $dspaceImport = $response->getContent()[0];
        $job = $this->jobDispatcher()->dispatch('DspaceConnector\Job\Undo', ['jobId' => $jobId]);
        $response = $this->api()->update('dspace_imports',
                $dspaceImport->id(),
                [
                    'o:undo_job' => ['o:id' => $job->getId() ],
                ]
            );
        return $job;
    }
}
