<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Partnerlinks extends Controller_Backend_Base {

	public function action_index()
	{
        $plink = new Model_Partnerlink();
        $values = $plink->to_array();

        if($_POST) {
            $validate = Validation::factory($_POST)
         				->rule('title', 'not_empty');

            if($validate->check()) {
                try {
                    $plink->from_array($validate->as_array());
                    Doctrine::instance()->persist($plink);
                    Doctrine::instance()->flush();

                    $this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
                    $this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'partnerlinks', 'action' => 'index')));
                } catch(Exception $e) {
                    $this->msg(Helper_Message::get('backend.add_error'), 'error');
                }
            } else {
                $this->msg(Helper_Message::get("backend.check_input"), 'error');
            }

             $this->view->errors = $validate->errors('validation');
             $values = $validate->as_array();
        }

        $this->view->values = $values;
        $this->view->links = Doctrine::instance()->getRepository('Model_Partnerlink')->findAll();
    }

	public function action_edit()
	{
        if(!$id = $this->request->param('id') OR !$links = Doctrine::instance()->getRepository('Model_Partnerlink')->findOneById($id)) {
      			throw new Kohana_Exception('feed not found', null, 404);
        }

        $values = $links->to_array();

        if($_POST) {
            $validate = Validation::factory($_POST)
                ->rule('title', 'not_empty');

            if($validate->check()) {
                try {
                    $links->from_array($validate->as_array());
                    Doctrine::instance()->persist($links);
                    Doctrine::instance()->flush();

                    $this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
                    $this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'partnerlinks', 'action' => 'index')));
                } catch(Exception $e) {
                    $this->msg(Helper_Message::get('backend.add_error'), 'error');
                }
            } else {
                $this->msg(Helper_Message::get("backend.check_input"), 'error');
            }

             $this->view->errors = $validate->errors('validation');
             $values = $validate->as_array();
        }

        $this->view->values = $values;
        $this->view->link = $links;
    }


    public function action_delete() {
   		if(!$id = $this->request->param('id') OR !$link = Doctrine::instance()->getRepository('Model_Partnerlink')->findOneById($id)) {
   			throw new Kohana_Exception('rss feed not found', null, 404);
   		}

   		try {
   			Doctrine::instance()->remove($link);
   			Doctrine::instance()->flush();
   			$this->flashMsg(Helper_Message::get('backend.delete_success'), 'success');
   		} catch(Exception $e) {
   			$this->flashMsg(Helper_Message::get('backend.delete_error'), 'error');
   		}

   		$this->redirectBack();
   	}
}
