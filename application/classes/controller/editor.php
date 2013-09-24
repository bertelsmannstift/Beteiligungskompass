<?php

class Controller_Editor extends Controller_Base {

	public function before() {
		parent::before();
		$this->checkRights('editor');
	}

	public function action_index() {
		//List of pending articles
		$query = Doctrine::instance()->createQuery("SELECT a FROM Model_Article a WHERE a.ready_for_publish = 1 AND a.active = false  AND a.deleted != 1 ORDER BY a.id ASC")->getResult();
		$this->view->pending = $query;
	}
}