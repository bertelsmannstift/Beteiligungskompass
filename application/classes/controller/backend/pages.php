<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Pages extends Controller_Backend_Base {

	public function action_index()
	{

       if($_POST) {
            foreach($_POST as $type => $field) {
                $page = Doctrine::instance()->getRepository('Model_Page')->findOneByType($type);
                $page->title = $field['title'];
                $page->content = $field['content'];
                $page->shortTitle = $field['shorttitle'];
                $page->active = isset($field['active']) && $field['active'] == '1' ? true : false;
                if(isset($field['field'])) {
                    $page->setFields($field['field']);
                }
                if(isset($field['fieldsWYSIWYG'])) {
                    $page->fieldsWYSIWYG = $field['fieldsWYSIWYG'];
                }
                Doctrine::instance()->persist($page);
            }
            Doctrine::instance()->flush();
       }
       $this->view->pages = Doctrine::instance()->getRepository('Model_Page')->findBy(array(), array('title' => 'asc'));
	}

    function action_newpage() {
        $this->auto_render = false;
        $page = new Model_Page;
        //$page = Doctrine::instance()->getRepository('Model_Page')->findOneByType('contact');
        $page->title = 'Contact';
        $page->content = '';
        $page->shortTitle = '';
        $page->type = 'contact';
        $page->setFields(array (
          'name' => 'Name',
          'email' => 'E-Mail-Address',
          'subject' => 'Subject',
          'message' => 'Message',
          'submit' => 'Submit',
          'required' => 'Required fields'
        ));

        Doctrine::instance()->persist($page);
        Doctrine::instance()->flush();
    }

    function action_imgupload() {
        $this->auto_render = false;
        if($_FILES) {
            move_uploaded_file($_FILES['upload']['tmp_name'], Kohana::$config->load('project.public_imgupload_path') . DIRECTORY_SEPARATOR . $_FILES['upload']['name']);
            $funcNum = $_GET['CKEditorFuncNum'] ;
            $url = DIRECTORY_SEPARATOR . Kohana::$config->load('project.public_imgupload_path') . DIRECTORY_SEPARATOR . $_FILES['upload']['name'];
            $message = '';

            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '$message');</script>";
        }

    }
} // End Welcome
