<?php

abstract class Controller_Base extends Controller {

	public $view;

	public $auto_render = true;
	public $template;

	public $template_body = 'layout/base';
	public $render_body = true;
	public $messages = array();

	protected $globalData = array();

    /**
     * @throws Kohana_Exception
     */
    public function before() {
		parent::before();

		if(Request::initial()->controller() == 'api' && !Kohana::$is_cli) {
			$this->auto_render = false;
			if($this->check_api_key() == false) {
				throw new Kohana_Exception("Access denied - missing or incorrect API key", null, 403);
			}
		}

		$this->view = SmartyView::factory();
		$this->template = trim(implode('/', array($this->request->directory(), $this->request->controller(), $this->request->action())), '/');
		$this->messages = $this->getMessages();

		if($this->request->is_ajax()) {
			$this->render_body = false;
		}
	}

    /**
     * @return bool
     */
    private function check_api_key() {
		$api_key = Kohana::$config->load('project.api_key');

		if(isset($_GET['api_key']) && $_GET['api_key'] == $api_key) {
			return true;
		}
		return false;
	}

	public function after() {
		parent::after();

		if($this->auto_render) {
			$this->response->body($this->render());
		}
	}

    /**
     * @return SmartyView
     */
    public function render() {

		$mobile = new Helper_Mobile();

		$params = Helper_Article::getFilterParams();

		$types = Helper_Article::getTypes(true);
		foreach($types as &$type) {
			$type = strtolower($type);
		}

		if(!isset($params['type'])) {
			$params['type'] = $types;
		}

		/*
		 * merge $this->globalData (global view variables set from controller actions)
		 * with an array of additional information
		 * if there are duplicate keys, second array overwrites first
		 */
		$globalData = array_merge(
			$this->globalData,
			array(
				'controller' => (string) Request::initial()->controller(),
				'action' => (string)$this->request->action(),
				'id' => (string)$this->request->param('id', ''),
			  'staticPages' => Doctrine::instance()->getRepository('Model_Page')->findBy(array('active' => 1)),
				'staticMenuPages' => Doctrine::instance()->getRepository('Model_Page')->findAll(),
				'isMobile' => $mobile->isMobile() && !$mobile->isTablet(),
				'isTablet' => $mobile->isTablet(),
				'isWebkit' => $mobile->isWebkit(),
				'isAppleBrowser' => $mobile->isiOS(),
				'isAjax' => $this->request->is_ajax(),
				'global_params' => $params,
				'planningQuestions' => $this->getPlanningQuestions(),
				'etrackercode' => Kohana::$config->load('project.etrackercode')));

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

    /**
     * @return array
     */
    private function getPlanningQuestions() {
        $plannerQuestions = array();
        for($i=1;$i<=5;$i++) {
            if (Helper_Planning::isQuestionActive($i)) {
                $plannerQuestions[] = $i;
            }
        }
        return $plannerQuestions;
    }

    /**
     * @return array
     */
    public function getMessages() {
		return array_merge($this->messages, Session::instance()->get_once('flash_messages', array()));
	}

    /**
     * @param $text
     * @param string $type
     */
    public function msg($text, $type = 'error') {
		$this->messages[] = (object) array(
			'text' => $text,
			'type' => $type
			);
	}


	public function flash() {
		foreach($this->messages as $msg) {
			$this->flashMsg($msg->text, $msg->type);
		}

		$this->messages = array();
	}

    /**
     * @param $text
     * @param string $type
     */
    public function flashMsg($text, $type = 'error') {
		$msg = (object) array(
			'text' => $text,
			'type' => $type
			);

		$messages = Session::instance()->get('flash_messages', array());
		$messages[] = $msg;
		Session::instance()->set('flash_messages', $messages);
	}

    /**
     * Redirect to home
     */
    public function redirectBack() {
		$this->request->redirect(Arr::get($_SERVER, 'HTTP_REFERER', Url::get('route:home')));
	}

    /**
     * Check user permissions
     *
     * @param $type
     */
    public function checkRights($type) {
		if(
			!$user = Helper_User::getUser()
			OR ($type == 'editor' AND !$user->isEditor())
			) {
			$this->flashMsg(Helper_Message::get("global.pleas_login"));
		$this->request->redirect(Url::get('route:home'));
	}
	}
	public function hasRole($type) {
		$user = Helper_User::getUser();
		if(!$user) {
			return false;
		}

		switch ($type) {
			case 'editor':
			return $user->isEditor();
			break;
			case 'admin':
			return $user->isAdmin();
			break;
			default:
			return false;
			break;
		}
	}

    /**
     * @param array $data
     * @param bool $appendMessages
     * @return mixed
     */
    protected function jsonResponse(array $data, $appendMessages = true) {
		$this->auto_render = false;

		$response = array();

		if($appendMessages AND $messages = $this->getMessages()) {
			$response['messages'] = $messages;
		}
		$responsebody = json_encode(array_merge($response, $data));

		return $this->response
		->headers('Content-Type', 'application/json')
		->body($responsebody);
	}
}