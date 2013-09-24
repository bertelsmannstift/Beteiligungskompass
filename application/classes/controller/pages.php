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

        if($_POST && $id == 'contact') {
            $validate = Validation::factory($_POST)
         				->rule('email', 'not_empty')
         				->rule('email', 'email')
         				->rule('name', 'not_empty')
         				->rule('subject', 'not_empty')
         				->rule('msg', 'not_empty');

            if($validate->check()) {

                $mail = SmartyView::factory('email/contact', $_POST)->render();

                mail(Kohana::$config->load('project.email.contact.to'), Helper_Message::get("email.contact.subject", array('subject' => $_POST['subject'])), $mail, "Content-type: text/plain; charset=utf-8\r\n" ."From: " . $_POST['email']);

                $this->flashMsg(Helper_Message::get("email.contact.email_send"), 'success');
                $this->request->redirect(Url::get('route:home'));
            }

            $this->view->errors = $validate->errors('validation');
        }

        $this->view->page = $page;
	}
}
