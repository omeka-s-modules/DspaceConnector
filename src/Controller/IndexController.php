<?php
namespace DspaceConnector\Controller;

use DspaceConnector\Form\ImportForm;
use DspaceConnector\Form\UrlForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    
    protected $client;
    
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
        if (isset($params['collection_link'])) {
            //coming from the import page, do the import
            $importForm = $this->getForm(ImportForm::class);
            $importForm->setData($params);
            if (! $importForm->isValid()) {
                $this->messenger()->addError('There was an error during validation');
                return $view;
            }

            $job = $this->jobDispatcher()->dispatch('DspaceConnector\Job\Import', $params);
            $view->setVariable('job', $job);
            $this->messenger()->addSuccess('Importing in Job ID ' . $job->getId());
            return $this->redirect()->toRoute('admin/dspace-connector/past-imports');

        } else {
            //coming from the index page, dig up data from the endpoint url
            $urlForm = $this->getForm(UrlForm::class);
            $urlForm->setData($params);
            if (! $urlForm->isValid()) {
                $this->messenger()->addError('There was an error during validation');
                return $this->redirect()->toRoute('admin/dspace-connector');
            }

            $importForm = $this->getForm(ImportForm::class);
            $dspaceUrl = rtrim($params['api_url'], '/');

            try {
                $communities = $this->fetchData($dspaceUrl . '/rest/communities', 'collections');
                $collections = $this->fetchData($dspaceUrl . '/rest/collections');
            } catch (Exception $e) {
                $this->logger()->err('Error importing data');
                $this->logger()->err($e);
            }
            $view->setVariable('collections', $collections);
            $view->setVariable('communities', $communities);
            $view->setVariable('dspace_url', $dspaceUrl);
            $view->setVariable('form', $importForm);
            return $view;
        }
    }

    /**
     * 
     * @param string $link either 'collections' or 'communities'
     * @throws \RuntimeException
     */
    protected function fetchData($endpoint, $expand = null)
    {
        $this->client->setHeaders(array('Accept' => 'application/json'));
        $this->client->setUri($endpoint);
        $this->client->setParameterGet(array('expand' => $expand));

        $response = $this->client->send();
        if (!$response->isSuccess()) {
            $this->logger()->err(sprintf('Requested "%s" got "%s".', $endpoint, $response->renderStatusLine()));
            $this->messenger()->addError('There was an error retrieving data. Please try again.');
        }
        return json_decode($response->getBody(), true);
    }

    public function pastImportsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $undoJobIds = [];
            foreach ($data['jobs'] as $jobId) {
                $undoJob = $this->undoJob($jobId);
                $undoJobIds[] = $undoJob->getId();
            }
            $this->messenger()->addSuccess('Undo in progress in the following jobs: ' . implode(', ', $undoJobIds));
        }
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array(
            'page'       => $page,
            'sort_by'    => $this->params()->fromQuery('sort_by', 'id'),
            'sort_order' => $this->params()->fromQuery('sort_order', 'desc'),
        );
        $response = $this->api()->search('dspace_imports', $query);
        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('imports', $response->getContent());
        return $view;
    }

    protected function undoJob($jobId) {
        $response = $this->api()->search('dspace_imports', array('job_id' => $jobId));
        $dspaceImport = $response->getContent()[0];
        $job = $this->jobDispatcher()->dispatch('DspaceConnector\Job\Undo', array('jobId' => $jobId));
        $response = $this->api()->update('dspace_imports', 
                $dspaceImport->id(), 
                array(
                    'o:undo_job' => array('o:id' => $job->getId() )
                )
            );
        return $job;
    }
}
