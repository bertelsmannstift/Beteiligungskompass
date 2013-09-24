<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Criteria extends Controller_Backend_Base {

	public function action_index() {
		$this->view->criteria = Doctrine::instance()->createQuery('SELECT c, o FROM Model_Criterion c LEFT JOIN c.options o ORDER BY c.title ASC')->getResult();
	}

	public function action_changeorder() {
		if($_POST AND $orderindex = Arr::get($_POST, 'orderindex', array()) AND count($orderindex )) {
			foreach($orderindex as $id => $value) {
				if($criterion = Doctrine::instance()->getRepository('Model_Criterion')->findOneById($id)) {
					$criterion->orderindex = (int) $value;
					Doctrine::instance()->persist($criterion);
				}
			}

			Doctrine::instance()->flush();
			$this->flashMsg(Helper_Message::get('backend.save_success'), 'success');
		}
		$this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'criteria', 'action' => 'index')));
	}

	public function action_changeoptionorder() {
		if($_POST AND $orderindex = Arr::get($_POST, 'orderindex', array()) AND count($orderindex )) {
			foreach($orderindex as $id => $value) {
				if($option = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($id)) {
					$option->orderindex = (int) $value;
					Doctrine::instance()->persist($option);
				}
			}

			Doctrine::instance()->flush();
			$this->flashMsg(Helper_Message::get('backend.save_success'), 'success');
			$urlparams = array('action' => 'edit','id' => $_POST["criterion_id"]);
		}else {
			$urlparams = array('action' => 'index');
		}

        $this->redirectBack();
	}

	public function action_edit() {
		if(!$id = $this->request->param('id') OR !$criterion = Doctrine::instance()->getRepository('Model_Criterion')->findOneById($id)) {
			throw new Kohana_Exception('Criterion not found', null, 404);
		}

		$this->view->criterion = $criterion;
        $this->view->types = Helper_Article::getTypes(true);
		$values = $criterion->to_array();

        $criteria = Doctrine::instance()->getRepository('Model_Criterion')->findBy(array('deleted' => 0));

        $groupedby = array();
        $this->view->grouptypes = Helper_Article::getTypes(true);
        unset($this->view->grouptypes['event']);
        foreach($criteria as $c) {
            foreach($this->view->grouptypes as $k => $gt) {
                if($c->id != $criterion->id && $c->isGroupedArticleType($k)) {
                    $groupedby[$k] = $c;
                }
            }
        }
        $this->view->alreadyGrouped = $groupedby;

		if($_POST) {

			$validate = Validation::factory($_POST)
				->rule('title', 'not_empty')
				->rule('title', array($this, 'criterion_unique'), array(':validation', 'title', $criterion->title));

		 	if($validate->check()) {
		 		try {
		 			$criterion->from_array($validate->as_array());

                    $criterion->showInPlanner = isset($_POST['showInPlanner']) ? $_POST['showInPlanner'] : false;

                    // check if default option exists
                    if($criterion->type == 'radio' || $criterion->type == 'select' || $criterion->type == 'resource') {

                        $default_exist = false;
                        foreach($criterion->options as $opt) {
                            if($opt->default == true && $opt->deleted == false) {
                                $default_exist = true;
                                break;
                            }
                        }
                        // does not exist -> create default option
                        if($default_exist === false) {
                            $option = new Model_Criterion_Option();
                            $option->criterion = $criterion;
                            $option->title = 'All';
                            $option->default = true;

                            Doctrine::instance()->persist($option);
                            Doctrine::instance()->flush();
                        }

                        try {
                            // set single option and uncheck all other options
                            if($criterion->type == 'resource') {
                                $criteriaOpts = array();
                                foreach($criterion->options as $opt) {
                                    $criteriaOpts[] = $opt->id;
                                }

                                $query = Doctrine::instance()->createQueryBuilder();
                                $query->select('a')->from('Model_Article', 'a');
                                $query->leftJoin('a.criteria', "c_{$criterion}");
                                $query->andWhere("c_{$criterion}.id IN (" . implode(',', $criteriaOpts) . ")");
                                $query->andWhere("a.deleted!=1");
                                $results = $query->getQuery()->getResult();

                                foreach($results as $article) {
                                    $optToDel = array();
                                    foreach($article->criteria as $c) {
                                        if(in_array($c->id, $criteriaOpts)) {
                                            $optToDel[] = $c;
                                        }
                                    }

                                    if(count($optToDel) > 1) {
                                        foreach($optToDel as $key => $optDel) {
                                            if($key > 0) {
                                                $article->criteria->removeElement($optDel);
                                            }
                                        }

                                        Doctrine::instance()->persist($article);
                                    }
                                }
                            }
                        }   catch(Exception $e) {
                            // error
                        }
                    } elseif($criterion->type == 'check') {

                        foreach($criterion->options as $opt) {
                            if($opt->default == true) {
                                $criterion->options->removeElement($opt);
                                Doctrine::instance()->remove($opt);
                            }
                        }
                    }

                    if(isset($_POST['types']) && is_array($_POST['types'])) {
                        $criterion->setArticleTypes($_POST['types']);
                    } else {
                        $criterion->setArticleTypes(array());
                    }

                    if(isset($_POST['group_article_types']) && is_array($_POST['group_article_types'])) {
                        $criterion->setGroupArticleTypes($_POST['group_article_types']);
                    } else {
                        $criterion->setGroupArticleTypes(array());
                    }

		 			Doctrine::instance()->persist($criterion);
		 			Doctrine::instance()->flush();
		 			$this->flashMsg(Helper_Message::get('backend.save_success'), 'success');
		 			$this->redirectBack();
		 		} catch(Exception $e) {
		 			$this->msg(Helper_Message::get('backend.save_error'), 'error');
		 		}
		 	} else {
		 		$this->msg(Helper_Message::get("backend.check_input"), 'error');
		 	}

            $this->view->errors = $validate->errors('validation');
            $values = $validate->as_array();
		}

		$this->view->values = $values;

		$this->view->options = $criterion->options;
	}

	public function action_add() {
		$criterion = new Model_Criterion();
		$values = $criterion->to_array();

		if($_POST) {
			$validate = Validation::factory($_POST)
				->rule('title', 'not_empty')
				->rule('title', array($this, 'criterion_unique'), array(':validation', 'title'));

		 	if($validate->check()) {
		 		try {
		 			$criterion->from_array($validate->as_array());
                    $criterion->showInPlanner = isset($_POST['showInPlanner']) ? $_POST['showInPlanner'] : false;


                     if(isset($_POST['types']) && is_array($_POST['types'])) {
                         $criterion->setArticleTypes($_POST['types']);
                     }

                     if(isset($_POST['group_article_types']) && is_array($_POST['group_article_types'])) {
                         $criterion->setGroupArticleTypes($_POST['group_article_types']);
                     }

		 			Doctrine::instance()->persist($criterion);
		 			Doctrine::instance()->flush();

                    if($criterion->type == 'radio' || $criterion->type == 'select' || $criterion->type == 'resource') {
                        // add default option - "all"
                        $option = new Model_Criterion_Option();
                        $option->criterion = $criterion;
                        $option->title = 'All';
                        $option->default = true;

                        Doctrine::instance()->persist($option);
                        Doctrine::instance()->flush();
                    }
		 			$this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
		 			$this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'criteria', 'action' => 'index')));
		 		} catch(Exception $e) {
		 			$this->msg(Helper_Message::get('backend.add_error'), 'error');
		 		}
		 	} else {
		 		$this->msg(Helper_Message::get("backend.check_input"), 'error');
		 	}

            $this->view->errors = $validate->errors('validation');
            $values = $validate->as_array();
		}

        $criteria = Doctrine::instance()->getRepository('Model_Criterion')->findBy(array('deleted' => 0));

        $groupedby = array();
        $this->view->grouptypes = Helper_Article::getTypes(true);
        unset($this->view->grouptypes['event']);
        foreach($criteria as $c) {
            foreach($this->view->grouptypes as $k => $gt) {
                if($c->id != $criterion->id && $c->isGroupedArticleType($k)) {
                    $groupedby[$k] = $c;
                }
            }
        }
        $this->view->alreadyGrouped = $groupedby;
		$this->view->values = $values;
        $this->view->types = Helper_Article::getTypes(true);
	}

	public function action_addchildoption() {
        if(!$id = $this->request->param('id') OR !$option = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($id)) {
            throw new Kohana_Exception('Criterion option not found', null, 404);
        }

        if($_POST) {
            $validate = Validation::factory($_POST)
         				->rule('title', 'not_empty');

            if($validate->check()) {
                try {
                    $subOption = new Model_Criterion_Option();
                    $subOption->criterion = $option->criterion;
                    $subOption->from_array($validate->as_array());
                    $subOption->parentOption = $option;
                    $option->childOptions->add($subOption);
                    Doctrine::instance()->persist($option);
                    Doctrine::instance()->persist($subOption);
                    Doctrine::instance()->flush();

                    $this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
                    $this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'criteria', 'action' => 'editoption', 'id' => $option->id)));
                } catch(Exception $e) {
                    $this->msg(Helper_Message::get('backend.add_error'), 'error');
                }
            } else {
                $this->msg(Helper_Message::get("backend.check_input"), 'error');
            }

            $this->view->errors = $validate->errors('validation');
            $values = $validate->as_array();
            $this->view->values = $values;
        }

        $this->template = trim(implode('/', array($this->request->directory(), $this->request->controller(), 'addoption')), '/');
    }

	public function action_editchildoption() {
        if(!$id = $this->request->param('id') OR !$option = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($id)) {
            throw new Kohana_Exception('Criterion option not found', null, 404);
        }

        $values = $option->to_array();
        $this->view->option = $option;

        if($_POST) {
            $validate = Validation::factory($_POST)
         				->rule('title', 'not_empty');

            if($validate->check()) {
                try {
                    $option->from_array($validate->as_array());
                    Doctrine::instance()->persist($option);
                    Doctrine::instance()->flush();

                    $this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
                    $this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'criteria', 'action' => 'editoption', 'id' => $option->id)));
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
        $this->template = trim(implode('/', array($this->request->directory(), $this->request->controller(), 'editoption')), '/');
    }

	public function action_addoption() {

		if(!$id = $this->request->param('id') OR !$criterion = Doctrine::instance()->getRepository('Model_Criterion')->findOneById($id)) {
			throw new Kohana_Exception('Criterion not found', null, 404);
		}

		$option = new Model_Criterion_Option();
		$option->criterion = $criterion;
		$this->view->option = $option;
		$values = $option->to_array();

		if($_POST) {
			$validate = Validation::factory($_POST)
				->rule('title', 'not_empty');

		 	if($validate->check()) {
		 		try {
		 			$option->from_array($validate->as_array());
		 			Doctrine::instance()->persist($option);
		 			Doctrine::instance()->flush();
		 			$this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
		 			$this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'criteria', 'action' => 'edit', 'id' => $criterion->id)));
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
	}

	public function action_editoption() {
		if(!$id = $this->request->param('id') OR !$option = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($id)) {
			throw new Kohana_Exception('Option not found', null, 404);
		}

		$this->view->option = $option;

		$values = $option->to_array();

		if($_POST) {
			$validate = Validation::factory($_POST)
				->rule('title', 'not_empty');

		 	if($validate->check()) {
		 		try {
		 			$option->from_array($validate->as_array());
		 			Doctrine::instance()->persist($option);
		 			Doctrine::instance()->flush();
		 			$this->flashMsg(Helper_Message::get('backend.save_success'), 'success');
		 			$this->redirectBack();
		 		} catch(Exception $e) {
		 			$this->msg(Helper_Message::get('backend.save_error'), 'error');
		 		}
		 	} else {
		 		$this->msg(Helper_Message::get("backend.check_input"), 'error');
		 	}

            $this->view->errors = $validate->errors('validation');
            $values = $validate->as_array();
		}

		$this->view->values = $values;
	}

	public function action_delete() {
		if(!$id = $this->request->param('id') OR !$criterion = Doctrine::instance()->getRepository('Model_Criterion')->findOneById($id)) {
			throw new Kohana_Exception('criterion not found', null, 404);
		}

		$criterion->deleted = true;

		try {
			Doctrine::instance()->persist($criterion);
			Doctrine::instance()->flush();
			$this->flashMsg(Helper_Message::get('backend.delete_success'), 'success');
		} catch(Exception $e) {
			$this->flashMsg(Helper_Message::get('backend.delete_error'), 'error');
		}

		$this->redirectBack();
	}

	public function action_deleteoption() {
		if(!$id = $this->request->param('id') OR !$option = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($id)) {
			throw new Kohana_Exception('Option not found', null, 404);
		}

        if($option->default == true && ($option->criterion->type == 'radio' || $option->criterion->type == 'select' || $option->criterion->type == 'resource')) {
            $this->redirectBack();
        }

		$option->deleted = true;
		try {
			Doctrine::instance()->persist($option);
			Doctrine::instance()->flush();
			$this->flashMsg(Helper_Message::get('backend.delete_success'), 'success');
		} catch(Exception $e) {
			$this->flashMsg(Helper_Message::get('backend.delete_error'), 'error');
		}

		$this->redirectBack();
	}

	public function action_deletechildoption() {
		if(!$id = $this->request->param('id') OR !$option = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($id)) {
			throw new Kohana_Exception('Option not found', null, 404);
		}

        if($option->default == true) {
            $this->redirectBack();
        }

		$option->deleted = true;

		try {
			Doctrine::instance()->persist($option);
			Doctrine::instance()->flush();
			$this->flashMsg(Helper_Message::get('backend.delete_success'), 'success');
		} catch(Exception $e) {
			$this->flashMsg(Helper_Message::get('backend.delete_error'), 'error');
		}

        $this->redirectBack();
    }

	public function criterion_unique(Validation $validation, $field, $current = false) {
		if($current AND $current == $validation[$field]) return true;

        $criterions = Doctrine::instance()->getRepository('Model_Criterion')->findByTitle($validation[$field]);

        foreach($criterions as $criterion) {
            if($criterion->deleted == false) {
                $validation->error($field, 'criterion_unique');
            }
        }
	}

	protected function accessAllowed() {
		return $this->hasRole("admin");
	}

}