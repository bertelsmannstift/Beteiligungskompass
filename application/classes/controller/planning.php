<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Planning extends Controller_Base {

	public function before() {
		parent::before();

		if(!Helper_Module::isActive("planning")) {
			$this->request->redirect(Url::get('route:home'));
		}
	}

    /**
     * Planning page view
     */
    public function action_index() {
        $mobile = new Helper_Mobile();

        if($mobile->isMobile()) {
            $this->view->types = Helper_Article::getTypes(true);
            $this->view->allCriteria = Helper_Article::getCriteriaListWithoutUnusedSelectOptions();

            $mobile = new Helper_Mobile();
            if($mobile->isMobile()) {
              $this->view->resultText = Request::factory(Url::get('action:resultcount'))->execute()->body();
            }

            $params = Helper_Article::getFilterParams();

            $types = $this->view->types;
            foreach($types as &$type) {
              $type = strtolower($type);
            }

            if(!isset($params['type'])) {
              $params['type'] = $types;
            }

            $this->view->params = $params;
        }

        $articleCounts = Helper_Article::getArticleResultCount(false);
        $this->view->articleTypeCount = $articleCounts;
	}

    /**
     * AJAX call to get article result count
     */
    public function action_resultcount() {
        $this->auto_render = false;
        Helper_Article::saveFilterParams();

        $mobile = new Helper_Mobile();
        $typeCount = Helper_Article::getArticleResultCount();

        if($mobile->isMobile() && !$mobile->isTablet()) {
            $count = 0;
            foreach($typeCount as $counts) {
                $count += $counts;
            }

            $msg = Helper_Message::get("module.planning.result_button", array('count' =>  '<strong>'.$count.'</strong>'));
            $this->response->body("<span class=\"result-text\">{$msg}</span>");
        } else {
            $this->response->body(json_encode($typeCount));
        }
	}

    /**
     * Save the questions from the planning page in a cookie
     */
    function action_askquestion() {
		$this->render_body = false;
		$cookie_life_time = 2592000;

		$ask = cookie::get('ask', false);
		$answers = cookie::get('answers', array());
		$op = isset($_POST['op']) ? $_POST['op'] : false;
		$forceLoad = isset($_POST['forceLoad']) && $_POST['forceLoad'] == 'true' ? true : false;

		if(!is_array($answers)) {
			$answers = unserialize($answers);
		}

		if($op !== false) {
			if($op == 'disable_ask') {
				// 30 days
				cookie::set('ask', true, $cookie_life_time);
				die;
			}
			elseif($op == 'answer') {
				// set answer
				$answer = isset($_POST['answer']) ? $_POST['answer'] : false;
				$question = isset($_POST['question']) ? $_POST['question'] : false;

				if(isset($answers[$question])) {
					cookie::set('answers', serialize(array_merge(array($question => $answer))), $cookie_life_time);
				}
				else {
					cookie::set('answers', serialize(array_merge($answers, array($question => $answer))), $cookie_life_time);
				}

				die;
			}
		}
		else {
			if($ask === false || $forceLoad === true) {
				// load question overlay
				$questions = Helper_Message::getArr("project", "questions");
	      foreach($questions as $k => &$q) {
	          if(!empty($q['title']) && Helper_Planning::isQuestionActive($k)) {
	              $article = Doctrine::instance()->getRepository('Model_Article')->findOneById($q['body']);
	              $q['body'] = $article && $article->answer ? $article->answer : $q['body'];
	          } else {
	              unset($questions[$k]);
	          }
	      }
	      $questions = array_values($questions);
				$this->view->questions = $questions;
			} else {
				// already asked
				die;
			}
		}
	}
}
