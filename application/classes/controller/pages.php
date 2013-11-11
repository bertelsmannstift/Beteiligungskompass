<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Pages extends Controller_Base {
    /**
     * Display static pages
     *
     * @throws Kohana_Exception
     */
    public function action_index()
	{
        if(!$id = $this->request->param('id') OR !$page = Doctrine::instance()->getRepository('Model_Page')->findOneByType($id)) {
      			throw new Kohana_Exception('Page not found', null, 404);
        }

        if($id == 'contact') {
            $formFieldNames = $this->getFormFieldNames();
        }

        if($_POST && $id == 'contact') {
            $validate = Validation::factory($_POST)
         				->rule($formFieldNames['email'], 'not_empty')
         				->rule($formFieldNames['email'], 'email')
         				->rule($formFieldNames['name'], 'not_empty')
         				->rule($formFieldNames['subject'], 'not_empty')
         				->rule($formFieldNames['msg'], 'not_empty');

            if($validate->check() &&
                isset($_POST['name']) &&
                isset($_POST['message']) &&
                isset($_POST['phone']) &&
                $_POST['name'] === 'MyName' &&
                $_POST['message'] === '' &&
                $_POST['phone'] === '') {

                $mailData = array(
                    'name' => $_POST[$formFieldNames['name']],
                    'msg' => $_POST[$formFieldNames['msg']]);

                $mail = SmartyView::factory('email/contact', $mailData)->render();

                mail(Kohana::$config->load('project.email.contact.to'), Helper_Message::get("email.contact.subject", array('subject' => $_POST[$formFieldNames['subject']])), $mail, "Content-type: text/plain; charset=utf-8\r\n" ."From: " . $_POST[$formFieldNames['email']]);

                $this->flashMsg(Helper_Message::get("email.contact.email_send"), 'success');
                $this->request->redirect(Url::get('route:home'));
            }

            $this->view->errors = $validate->errors('validation');
        }

        $this->view->formFieldNames = $formFieldNames;
        $this->view->page = $page;
	}

    /**
     * @return array
     */
    function getFormFieldNames() {
        return array(
            'name' => md5('name_' . session_id()),
            'email' => md5('email_' . session_id()),
            'subject' => md5('subject_' . session_id()),
            'msg' => md5('msg_' . session_id()),
        );
    }
}
