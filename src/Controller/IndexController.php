<?php
namespace DspaceConnector\Controller;

use DspaceConnector\Form\ImportForm;
use DspaceConnector\Form\UrlForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $form = new UrlForm($this->getServiceLocator());
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                $job = $dispatcher->dispatch('DspaceConnector\Job\Import', $data);
                $view->setVariable('job', $job);
                $this->messenger()->addSuccess('Importing ' . $data['collection_name'] .  ' in Job ID ' . $job->getId());
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }
        
        $view->setVariable('form', $form);
        return $view;

    }
    
    public function importAction()
    {
        $view = new ViewModel;
        //$logger = $this->getServiceLocator()->get('Omeka\Logger');
        $form = new ImportForm($this->getServiceLocator());
        $params = $this->params()->fromPost();
        $communities = $this->fetchData('communities');
        $collections = $this->fetchData('collections');
        $view->setVariable('form', $form);
        return $view;
    }
    
    /**
     * 
     * @param string $link either 'collections' or 'communities'
     * @throws \RuntimeException
     */
    protected function fetchData($link)
    {
        
        $logger = $this->getServiceLocator()->get('Omeka\Logger');
        $view = new JsonModel;
        $params = $this->params()->fromQuery();
        $dspaceUrl = rtrim($params['dspaceUrl'], '/');
        if (isset($params['expand'])) {
            $expand = $params['expand'];
        } else {
            $expand = 'all';
        }
        
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        
        $clientConfig = array(
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
            'curloptions' => array(
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_SSL_VERIFYPEER => FALSE
            ),
        );
        
        $client->setOptions($clientConfig);
        
        $client->setHeaders(array('Accept' => 'application/json'));
        $client->setUri($dspaceUrl . '/rest/' . $link);
        $client->setParameterGet(array('expand' => $expand));
        
        $response = $client->send();
        if (!$response->isSuccess()) {
            $logger->err('no response');
            throw new \RuntimeException(sprintf(
                'Requested "%s" got "%s".', $dspaceUrl . '/rest/' . $link, $response->renderStatusLine()
            ));
        }
        $view->setVariable('data', $response->getBody());
        return $view;
    }
    
    public function pastImportsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            foreach ($data['jobs'] as $jobId) {
                $this->undoJob($jobId);
            }
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
        if ($response->isError()) {

        }
        $dspaceImport = $response->getContent()[0];
        $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
        $job = $dispatcher->dispatch('DspaceConnector\Job\Undo', array('jobId' => $jobId));
        $response = $this->api()->update('dspace_imports', 
                $dspaceImport->id(), 
                array(
                    'o:undo_job' => array('o:id' => $job->getId() )
                )
            );
        if ($response->isError()) {
        }
    }
}