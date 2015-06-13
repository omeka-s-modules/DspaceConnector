<?php
namespace DspaceConnector\Controller;

use DspaceConnector\Form\ImportForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $form = new ImportForm($this->getServiceLocator());
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                $job = $dispatcher->dispatch('DspaceConnector\Job\Import', $data);
                $view->setVariable('job', $job);
                $view->setVariable('collectionName', $data['collection_name']);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }
        
        $view->setVariable('form', $form);
        return $view;

    }
    
    public function fetchAction()
    {
        $view = new JsonModel;
        $params = $this->params()->fromQuery();
        $dspaceUrl = $params['dspaceUrl'];
        $link = $params['link'];
        if (isset($params['expand'])) {
            $expand = $params['expand'];
        } else {
            $expand = 'all';
        }
        
        
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setHeaders(array('Accept' => 'application/json'));
        $client->setUri($dspaceUrl . '/rest/' . $link);
        $client->setParameterGet(array('expand' => $expand));
        
        $response = $client->send();
        if (!$response->isSuccess()) {
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
        $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
        $job = $dispatcher->dispatch('DspaceConnector\Job\Undo', array('jobId' => $jobId));
    }
}