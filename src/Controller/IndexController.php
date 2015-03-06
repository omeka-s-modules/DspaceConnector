<?php
namespace DspaceConnector\Controller;

use DspaceConnector\Form\ImportForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $form = new ImportForm($this->getServiceLocator());
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            
            $args = array();
            $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
            //$job = $dispatcher->dispatch('DspaceConnector\Job\Import', $args);
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;

    }
    
    public function fetchAction()
    {
        $view = new ViewModel;
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
            throw new Exception\RuntimeException(sprintf(
                'Requested "%s" got "%s".', $url, $response->renderStatusLine()
            ));
        }
        $view->setVariable('data', $response->getBody());
        $view->setTerminal(true);
        return $view;
    }
}