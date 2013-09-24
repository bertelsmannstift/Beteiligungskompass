<?php defined('SYSPATH') or die('No direct script access.');

class Controller_User extends Controller_Base {

    /**
     * @return mixed
     */
    public function action_login() {
		if($_POST) {
			$validate = Validation::factory($_POST)
				->rule('email', 'not_empty')
				->rule('email', 'email')
				->rule('password', 'not_empty')
				->rule('username', 'Helper_User::validate_login', array(':validation', 'email', 'password'))
				->rule('email', 'Helper_User::validate_check_dbloptin', array(':validation', 'email'));

		 	if($validate->check()) {
				$this->flashMsg(Helper_Message::get("flash_message.login_successful"), 'success');
				if(!$this->request->is_ajax()) {
					$this->request->redirect(Url::get('route:home'));
				} else {
					$this->auto_render = false;
					return $this->response->body('true');
				}
			}

            $this->msg(Helper_Message::get("flash_message.login_error"), 'error');
            $this->view->errors = $validate->errors('validation');
            $this->view->values = $validate->as_array();
		}

        $this->render_body = false;
	}

    /**
     * @return mixed
     */
    public function action_removeacc() {
        if($_POST) {
            if(Arr::get($_POST, 'remove') == 'true') {
                $this->view->success = true;
                Helper_User::getUser()->sendRemoveAccEmail();
            } else {
                $this->auto_render = false;
                return $this->response->body('true');
            }
        }
    }

    /**
     * @throws Kohana_Exception
     */
    public function action_removeaccconfirm() {
        if(!$hash = $this->request->param('hash') OR !$user = Doctrine::instance()->getRepository('Model_User')->findOneByHash($hash)) {
            throw new Kohana_Exception('No hash or user not found', null, 404);
        }

        Helper_User::logout();
        Helper_User::anonymizeUserAndMarkAsDeleted($user);

        $this->flashMsg(Helper_Message::get("flash_message.accountremoved"), 'success');
        return $this->request->redirect(Url::get('route:home'));
    }

    /**
     * Show user profile
     */
    public function action_profile() {
        $user = Helper_User::getUser();
        $this->view->user = $user;
        $errors = array();

        if($_POST) {
            $validate = Validation::factory($_POST);

            if(!empty($_POST['old_password'])) {
                $validate = $validate->rule('old_password', 'not_empty')
                         ->rule('old_password', array($user, 'comparePassword'))
                         ->rule('password', 'not_empty')
                         ->rule('password', 'min_length', array(':value', 8))
                         ->rule('password_repeat', 'not_empty')
                         ->rule('password_repeat', 'matches', array(':validation', 'password_repeat', 'password'));

                if($validate->check()) {
                    $user->setPassword($_POST['password']);
                }
                else {
                    $errors = $validate->errors('validation');
                }
            }

            $validate = Validation::factory($_POST)->rule('first_name', 'not_empty')
				                                   ->rule('last_name', 'not_empty');

            if($validate->check()) {
                $user->first_name = $_POST['first_name'];
                $user->last_name = $_POST['last_name'];
            }

            Doctrine::instance()->persist($user);
            Doctrine::instance()->flush();

            $this->view->errors = array_merge($errors, $validate->errors('validation'));
            $this->view->values = $validate->as_array();
        }
    }

    /**
     * User logout
     */
    public function action_logout() {
		if(!Helper_User::logout()) {
			$this->flashMsg(Helper_Message::get("flash_message.not_logged_in"), 'notice');
			$this->request->redirect(Url::get('route:login'));
		} else {
			$this->flashMsg(Helper_Message::get("flash_message.logout_successful"), 'success');
			$this->request->redirect(Url::get('route:home'));
		}
	}

    /**
     * Lost password view
     *
     * @return mixed
     */
    function action_lostpassword() {
        if($_POST) {
            $validate = Validation::factory($_POST)
                        ->rule('email', 'not_empty')
                        ->rule('email', 'email')
                        ->rule('email', 'Helper_User::validate_check_email', array(':validation', 'email'));

            if($validate->check()) {
                $this->flashMsg(Helper_Message::get("flash_message.password_sent"), 'success');
                if(!$this->request->is_ajax()) {
                    $this->request->redirect(Url::get('route:home'));
                } else {
                    $this->auto_render = false;
                    return $this->response->body('true');
                }
            }

            $this->view->errors = $validate->errors('validation');
            $this->view->values = $validate->as_array();
        }

        $this->render_body = false;
    }

    /**
     * Registration view
     */
    public function action_register() {
  	    $user = new Model_User();

		if($_POST) {
			$validate = Validation::factory($_POST)
				->rule('first_name', 'not_empty')
				->rule('last_name', 'not_empty')
				->rule('terms', 'not_empty')
				->rule('email', 'not_empty')
				->rule('email', 'email')
				->rule('email', 'Helper_User::validate_email_unique', array(':validation', 'email'))
				->rule('password', 'not_empty')
				->rule('password', 'min_length', array(':value', 8))
				->rule('password_repeat', 'not_empty')
				->rule('password_repeat', 'matches', array(':validation', 'password_repeat', 'password'));

			if($validate->check()) {
				$user->from_array($_POST);

				Doctrine::instance()->persist($user);
				Doctrine::instance()->flush();

				$this->view->register_ok = true;
				$user->sendActivationMail();
				return;
			}

            $this->msg(Helper_Message::get("flash_message.register_error"), 'error');
            $this->view->errors = $validate->errors('validation');
            $this->view->values = $validate->as_array();
		}
        $this->view->terms_of_use = '<a href="' . Url::get("route:default controller:pages action:index id:privacy") . '" target="_blank">' . Helper_Message::get("global.terms_of_use") . '</a>';

        $this->render_body = false;
	}

    /**
     * Activate user account
     *
     * @throws Kohana_Exception
     */
    public function action_activation() {
		if(!$hash = $this->request->param('hash') OR !$user = Doctrine::instance()->getRepository('Model_User')->findOneByHash($hash)) {
			throw new Kohana_Exception('No hash or user not found', null, 404);
		}

		if($user->dbloptin) {
			$this->flashMsg(Helper_Message::get("flash_message.already_active"), 'notice');
			return $this->request->redirect(Url::get('route:home'));
		}

		$user->dbloptin = true;
		Doctrine::instance()->persist($user);
		Doctrine::instance()->flush();

        Session::instance()->set('user_id', $user->id);
		$this->flashMsg(Helper_Message::get("flash_message.activation_successful"), 'success');
		return $this->request->redirect(Url::get('route:home'));
	}
}
