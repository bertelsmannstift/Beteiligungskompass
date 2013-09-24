<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Editors extends Controller_Backend_Base {

	public function action_index() {
		$editors = Doctrine::instance()->createQuery("SELECT u FROM Model_User u WHERE u.is_editor = 1 AND u.is_deleted = 0 ORDER BY u.last_name ASC")->getResult();
		$this->view->editors = $editors;
	}

	public function action_add() {
		if($_POST) {
			$_POST["is_editor"] = "on";
			$success = $this->saveUser();
			if($success) {
				$this->flashMsg(Helper_Message::get("flash_message.user_saved"), 'success');
				$this->request->redirect(Url::get(array(
					'route' => 'backend-default',
					'directory' => 'backend',
					'controller' => 'editors',
					'action' => 'index'
				)));
			} else {
				$this->flashMsg(Helper_Message::get("flash_message.user_saved_invalid"), 'success');
			}
		}
	}

	public function action_edit() {
		if(!$id = $this->request->param('id')
			OR !$user = Doctrine::instance()->getRepository('Model_User')->findOneById($id)) {
			throw new Kohana_Exception('User could not be found', null, 404);
		}
		$this->view->user = $user;

		if($_POST) {
			$_POST["is_editor"] = "on";
			$success = $this->saveUser($user);
			if($success) {
				$this->flashMsg(Helper_Message::get("flash_message.user_saved"), 'success');
				$this->request->redirect(Url::get(array(
					'route' => 'backend-default',
					'directory' => 'backend',
					'controller' => 'editors',
					'action' => 'index'
				)));
			} else {
				$this->flashMsg(Helper_Message::get("flash_message.user_saved_invalid"), 'success');
			}
		}
	}

	public function action_delete() {
		if(!$id = $this->request->param('id')
			OR !$user = Doctrine::instance()->getRepository('Model_User')->findOneById($id)) {
			throw new Kohana_Exception('User could not be found', null, 404);
		}
		Helper_User::anonymizeUserAndMarkAsDeleted($user);

		$this->request->redirect(Url::get(array(
		    'route' => 'backend-default',
		    'directory' => 'backend',
		    'controller' => 'editors',
		    'action' => 'index'
		)));
	}

	protected function accessAllowed() {
		return $this->hasRole("admin");
	}
}