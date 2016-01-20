<?php

namespace Livre\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Livre\Model\Livre;          // <-- Add this import
use Livre\Form\LivreForm;       // <-- Add this import

class LivreController extends AbstractActionController {

    protected $livreTable;
    protected $authservice;

    public function addAction() {
        $form = new LivreForm();
        $form->get('submit')->setValue('Add');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $livre = new Livre();
            $form->setInputFilter($livre->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $livre->exchangeArray($form->getData());
                $this->getLivreTable()->saveLivre($livre);

                // Redirect to list of livres
                return $this->redirect()->toRoute('livre');
            }
        }
        return array('form' => $form);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('livre', array(
                        'action' => 'add'
            ));
        }

        // Get the Livre with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $livre = $this->getLivreTable()->getLivre($id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('livre', array(
                        'action' => 'index'
            ));
        }

        $form = new LivreForm();
        $form->bind($livre);
        $form->get('submit')->setAttribute('value', 'Edit');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setInputFilter($livre->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $this->getLivreTable()->saveLivre($livre);

                // Redirect to list of livres
                return $this->redirect()->toRoute('livre');
            }
        }

        return array(
            'id' => $id,
            'form' => $form,
        );
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('livre');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');

            if ($del == 'Yes') {
                $id = (int) $request->getPost('id');
                $this->getLivreTable()->deleteLivre($id);
            }

            // Redirect to list of livres
            return $this->redirect()->toRoute('livre');
        }

        return array(
            'id' => $id,
            'livre' => $this->getLivreTable()->getLivre($id)
        );
    }

    public function getLivreTable() {
        if (!$this->livreTable) {
            $sm = $this->getServiceLocator();
            $this->livreTable = $sm->get('Livre\Model\LivreTable');
        }
        return $this->livreTable;
    }

    public function getAuthService() {
        if (!$this->authservice) {
            $this->authservice = $this->getServiceLocator()
                    ->get('AuthService');
        }

        return $this->authservice;
    }

    public function indexAction() {
        $user = $this->getAuthService()-> getStorage() -> read();
        
        
        return new ViewModel(array(
            'livres' => $this->getLivreTable()->fetchUser($user->id),
        ));
    }

    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->artist = (isset($data['artist'])) ? $data['artist'] : null;
        $this->title = (isset($data['title'])) ? $data['title'] : null;
    }

    // Add the following method:
    public function getArrayCopy() {
        return get_object_vars($this);
    }

}
