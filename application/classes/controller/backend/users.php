<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Users extends Controller_Backend_Base {


    /**
     * php index.php --uri=base/fixtures
     *
     * @throws Kohana_Exception
     */
    public function action_fixtures() {
        $this->auto_render = false;

        if(!Kohana::$is_cli) {
            throw new Kohana_Exception("Can only run on CLI", null, 403);
        }

        $admin = new Model_User();
        $admin->email = 'admin@admin.com';
        $admin->first_name = 'admin';
        $admin->last_name = 'admin';
        $admin->is_admin = true;
        $admin->dbloptin = true;
        $admin->setPassword('admin');
        Doctrine::instance()->persist($admin);

        $editor = new Model_User();
        $editor->email = 'editor@editor.com';
        $editor->first_name = 'editor';
        $editor->last_name = 'editor';
        $editor->is_editor = true;
        $editor->dbloptin = true;
        $editor->setPassword('editor');
        Doctrine::instance()->persist($editor);

        $user = new Model_User();
        $user->email = 'user@user.com';
        $user->first_name = 'user';
        $user->last_name = 'user';
        $user->dbloptin = true;
        $user->setPassword('user');
        Doctrine::instance()->persist($user);

        Doctrine::instance()->flush();
    }

	public function action_index() {
		$user = Helper_User::getUser();

		$users = Doctrine::instance()->createQueryBuilder()
		        ->select('u')
		        ->from('Model_User', 'u')
		        ->orderBy('u.last_name', 'ASC');

		if($user->isEditor() && !$user->isAdmin()) {
			$users->where("u.is_editor = 0 AND u.is_admin = 0 AND u.is_deleted = 0");
		} else {
			$users->where("u.is_deleted = 0");
		}

		$this->view->users = $users->getQuery()->getResult();
	}

	public function action_add() {
  	    $user = new Model_User();

		if($_POST) {
			$success = $this->saveUser();
			if($success) {
				$this->flashMsg(Helper_Message::get("flash_message.user_saved"), 'success');
				$this->request->redirect(Url::get(array(
					'route' => 'backend-default',
					'directory' => 'backend',
					'controller' => 'users',
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
			$success = $this->saveUser($user);
			if($success) {
				$this->flashMsg(Helper_Message::get("flash_message.user_saved"), 'success');
				$this->request->redirect(Url::get(array(
					'route' => 'backend-default',
					'directory' => 'backend',
					'controller' => 'users',
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
		    'controller' => 'users',
		    'action' => 'index'
		)));
	}

	protected function accessAllowed() {
		return $this->hasRole("admin");
	}
}