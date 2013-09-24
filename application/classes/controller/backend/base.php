<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Base extends Controller_Base {

	/*
	public function action_index() {
		$this->request->redirect(Url::get(array("controller"=>"backend","action"=>"editors")),301);
	}
	*/

	private $_allowEditorModules = array('users', 'articles', 'base');

	public function before() {
		parent::before();

		if(!$this->checkPermission() && !Kohana::$is_cli) {
			$this->handleUnauthorizedRequest();
		} elseif(isset($_GET['base_url']) && Kohana::$is_cli) {
            Kohana::$base_url = $_GET['base_url'];
        }
		$this->template_body = "layout/backend";
	}

	private function checkPermission() {
		$user = Helper_User::getUser();
		$controller = (string) Request::initial()->controller();

		if($user && $user->isEditor()) {
			if($user->isEditor() && !$user->isAdmin()) {
				if(in_array($controller, $this->_allowEditorModules)) {
					return true;
				} else {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	protected function accessAllowed() {
		$isEditor = $this->hasRole("editor");
		return $isEditor;
	}

	protected function handleUnauthorizedRequest() {
		if(Helper_User::getUser()) {
			throw new Kohana_Exception('Access denied', null, 403);
		} else {
			$this->flashMsg(Helper_Message::get("flash_message.not_logged_in"),"error");
			$this->request->redirect(Url::get('route:home'));
		}
	}


    public function render() {
        $mobile = new Helper_Mobile();

   		$globalData = array(
   			'controller' => (string) Request::initial()->controller(),
   			'action' => (string)$this->request->action(),
   			'id' => (string)$this->request->param('id', ''),
   			'staticPages' => Doctrine::instance()->getRepository('Model_Page')->findBy(array('active' => 1)),
   			'isMobile' => $mobile->isMobile() && !$mobile->isTablet(),
   			'isTablet' => $mobile->isTablet(),
   			'isWebkit' => $mobile->isWebkit(),
   			'isAppleBrowser' => $mobile->isiOS(),
   			'isAjax' => $this->request->is_ajax(),
   			'etrackercode' => Kohana::$config->load('project.etrackercode'));

   		foreach($globalData as $key => $val) {
   			$this->view->$key = $val;
   		}

   		$content = $this->view->render($this->template);

   		if($this->render_body) {
   			$data = array_merge($globalData, array(
   				'_content_' => $content,
   				'messages' => $this->messages,
   				));

   			$content = SmartyView::factory($this->template_body, $data);
   		}

   		return $content;
   	}

	protected function saveUser($user = null) {

		$currentEmail = null;
		if($user) {
			$currentEmail = $user->email;
		}

		$success = false;
		$passwordMustBeSet = false;

		if(!$user) {
			$user = new Model_User();
			$passwordMustBeSet = true;
		}
		try {
			$user->from_array($_POST);

			if(isset($_POST["is_editor"])) {
				$user->is_editor = Arr::get($_POST, 'is_editor') == "on";
			}
			if(isset($_POST["is_admin"])) {
				$user->is_admin = Arr::get($_POST, 'is_admin') == "on";
			}

			$user->is_active = Arr::get($_POST, 'is_active') == "on";
			// User created in the backend are always verified
			$user->dbloptin = true;

			$validate = Validation::factory($_POST);

			$validate = $validate
				->rule('first_name', 'not_empty')
				->rule('last_name', 'not_empty')
				->rule('email', 'not_empty')
				->rule('email', 'email')
				->rule('email', 'Helper_User::validate_email_unique', array(':validation', 'email', $currentEmail));

			if($passwordMustBeSet) {
				$validate = $validate->rule('new_password', 'not_empty');
			}

			if(isset($_POST['new_password']) && !empty($_POST['new_password'])) {
				$validate = $validate
					->rule('new_password_again', 'not_empty')
					->rule('new_password', 'min_length', array(':value', 8))
					->rule('new_password_again', 'matches', array(':validation', 'new_password_again', 'new_password'));
				$user->setPassword($_POST['new_password']);
			}
			if($validate->check()) {
				Doctrine::instance()->persist($user);
				Doctrine::instance()->flush();
				$success = true;
			} else {
				$this->view->errors = $validate->errors('validation');
				$this->view->user = $user;
			}
		} catch(Exception $e) {
			$this->msg(Helper_Message::get("flash_message.user_saved_error"), 'error');
		}
		return $success;
	}

	/**
	 * Called from backend.js to get messages for the datatables plug-in
	 * @link http://datatables.net
	 * @link http://datatables.net/ref
	 */
	public function action_texts() {
		$texts = array(
			"oAria" => array(
				"sSortAscending" => Helper_Message::get("backend.tables.oAria.sSortAscending"),
				"sSortDescending" => Helper_Message::get("backend.tables.oAria.sSortDescending"),
			),
			"oPaginate" => array(
				"sFirst" => Helper_Message::get("backend.tables.oPaginate.sFirst"),
				"sLast" => Helper_Message::get("backend.tables.oPaginate.sLast"),
				"sNext" => Helper_Message::get("backend.tables.oPaginate.sNext"),
				"sPrevious" => Helper_Message::get("backend.tables.oPaginate.sPrevious"),
			),
			"sEmptyTable" => Helper_Message::get("backend.tables.sEmptyTable"),
			"sInfo" => Helper_Message::get("backend.tables.sInfo"),
			"sInfoEmpty" => Helper_Message::get("backend.tables.sInfoEmpty"),
			"sInfoFiltered" => Helper_Message::get("backend.tables.sInfoFiltered"),
			"sInfoPostFix" => Helper_Message::get("backend.tables.sInfoPostFix"),
			"sInfoThousands" => Helper_Message::get("backend.tables.sInfoThousands"),
			"sLengthMenu" => Helper_Message::get("backend.tables.sLengthMenu"),
			"sLoadingRecords" => Helper_Message::get("backend.tables.sLoadingRecords"),
			"sProcessing" => Helper_Message::get("backend.tables.sProcessing"),
			"sSearch" => Helper_Message::get("backend.tables.sSearch"),
			"sUrl" => Helper_Message::get("backend.tables.sUrl"),
			"sZeroRecords" => Helper_Message::get("backend.tables.sZeroRecords"),
		);
		$this->jsonResponse($texts);
	}
}