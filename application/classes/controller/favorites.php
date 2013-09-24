<?php

class Controller_Favorites extends Controller_Base {
	public function before() {
		parent::before();
        if((string)$this->request->action() != 'showgroup') {
            $this->checkRights('user');
        }
	}

    /**
     * Show all own favorites
     */
	public function action_index() {
		$user = Helper_User::getUser();
        $order = Arr::get($_GET, 'orderby', 'created');
        $grouped = false;

		$favorites = $user->getFavoritesList(true, $order);

        if($order != 'created') {
            $grouped = true;
        }

        $this->view->grouped = $grouped;
        $this->view->favorites = $favorites;
        $this->view->user = $user;
        $this->view->values = $_GET;
        $this->view->articleGroups = Helper_Article::getArticleGroups($favorites);
	}


    /**
     * Copy a article group to favorites
     */
    function action_copygroup() {
        return Helper_User::copyArticleGroup($this->request->param('id'), $this->request->param('param'));
    }

    /**
     * Group show page - the group share link points to this page
     */
	public function action_showgroup() {
        list($result, $group, $hashData) = Helper_User::showArticleGroup($this->request->param('id'), $this->request->param('param'));
        $this->view->favorites = $result;
        $this->view->group = $group;
        $this->view->hash = $hashData;
	}

    /**
     * Adds a article to the user favorites
     *
     * @return mixed
     * @throws Kohana_Exception
     */
    function action_addToFavorites() {
        $user = Helper_User::getUser();
        $this->auto_render = false;

        if($_POST && $user) {
            Doctrine::getCache()->delete('my_favorites');
            Session::instance()->delete('type_results');

            $favGroup = isset($_POST['fav_group']) ? $_POST['fav_group'] : 0;

            if(!$id = $_POST['article'] OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
                throw new Kohana_Exception('Article not found', null, 404);
            }

            Session::instance()->set('articlefilter_result_' . $article->type(), false);

            if($favGroup == '0') {
                $article->addToFavorites();
                return $this->response->body(json_encode(array('success' => true)));
            } elseif($favGroup != '') {
                $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $_POST['fav_group']));

                if($group) {
                    $fav = $article->addToFavorites();
                    $group->favorites->add($fav);

                    $fav->favoriteGroups->add($group);
                    Doctrine::instance()->persist($fav);
                    Doctrine::instance()->persist($group);
                    Doctrine::instance()->flush();
                    return $this->response->body(json_encode(array('success' => true)));
                }
            } elseif(isset($_POST['new_group']) && !empty($_POST['new_group'])) {
                $fav = $article->addToFavorites();
                $favGroup = new Model_Favoritegroup();
                $favGroup->name = $_POST['new_group'];
                $favGroup->user = $user;
                $favGroup->favorites->add($fav);
                $fav->favoriteGroups->add($favGroup);
                Doctrine::instance()->persist($fav);
                Doctrine::instance()->persist($favGroup);
                Doctrine::instance()->flush();
                return $this->response->body(json_encode(array('success' => true)));
            }

            return $this->response->body(json_encode(array('success' => false)));
        }
    }

    /**
     * Remove a article from the favorites
     *
     * @throws Kohana_Exception
     */
    public function action_removeFavorite() {
		$success = true;
		$action = null;

		if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
			throw new Kohana_Exception('Article not found', null, 404);
		}

		if(!$user = Helper_User::getUser()) {
			$this->flashMsg(Helper_Message::get("flash_message.favourites.not_logged_in"), 'notice');
		} elseif($article->user->id == $user->id) {
			$this->flashMsg(Helper_Message::get("flash_message.favourites.own_article"), 'notice');
		} else {
            Doctrine::getCache()->delete('my_favorites');
            Session::instance()->set('type_results', false);
            Session::instance()->set('articlefilter_result_' . $article->type(), false);

            $action = 'remove';
            $article->removeFromFavorites();
            $this->flashMsg(Helper_Message::get("flash_message.favourites.removed_successfully"), 'success');
            $success = true;
		}

		if($this->request->is_ajax()) {
			$this->jsonResponse(array(
				'action' => $action,
				'success' => $success,
			));
		} else {
			$this->redirectBack();
		}
	}

    /**
     * Show the add to favorite ovleray with article groups from the user
     *
     * @throws Kohana_Exception
     */
    function action_add_to_fav() {
        $this->render_body = false;
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }
        $this->view->article = $article;
    }

    /**
     * Adds a article to a group
     */
    function action_addToGroup() {
        $this->auto_render = false;
        $groupId = isset($_POST['group']) ? $_POST['group'] : null;
        Helper_User::addArticleToGroup($groupId, $_POST['article']);
    }

    /**
     * Creates a group share link
     */
    function action_share_group() {
        $user = Helper_User::getUser();
        $this->auto_render = false;

        $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $_POST['group']));

        if($group) {
            $hash = $group->getSharehash();
            $group->shared = true;
            Doctrine::instance()->persist($group);
            Doctrine::instance()->flush();

            $this->jsonResponse(array('link' => Url::base() . Url::get(array(
                                        'route' => 'default',
                                        'controller' => $this->request->controller(),
                                        'action' => 'showgroup',
                                        'id' => $group->id,
                                        'param' => $hash,
                                    ))));
        }
    }

    /**
     * Deletes a group
     */
    function action_deletegroup() {
        $this->auto_render = false;
        Helper_User::deleteArticleGroup($this->request->param('id'));
        $this->redirectBack();
    }

    /**
     * Removes a article from a group
     */
    function action_removeFromGroup() {
        $this->auto_render = false;

        $user = Helper_User::getUser();
        $article = Doctrine::instance()->getRepository('Model_Article')->findOneBy(array('id' => $_POST['article']));
        $favorite = Doctrine::instance()->getRepository('Model_Favorite')->findOneByArticle($article);
        $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $_POST['group']));

        if($user && $group && $favorite) {
            if($group->favorites->contains($favorite)) {
                $group->favorites->removeElement($favorite);
                $favorite->favoriteGroups->removeElement($group);
                Doctrine::instance()->persist($favorite);
                Doctrine::instance()->persist($group);
                Doctrine::instance()->flush();
            }
        }
    }

    /**
     * Create a new user Favorite group
     *
     * @return mixed
     */
    function action_newgroup() {
        if(Helper_User::newArticleGroup($_POST) === true) {
            $this->auto_render = false;
            return $this->response->body('true');
        } else {
            list($errors, $values) = Helper_User::newArticleGroup($_POST);
            $this->view->errors = $errors;
            $this->view->values = $values;
        }
       $this->render_body = false;
    }

    /**
     * Remove favorite from user, called from the favorite view
     *
     * @return bool
     * @throws Kohana_Exception
     */
    public function action_remove() {
		if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
			throw new Kohana_Exception('Article not found', null, 404);
		}
        $this->auto_render = false;

    	try {
            $article->removeFromFavorites();
            $this->flashMsg(Helper_Message::get("flash_message.favourites.removed_successfully"), 'success');
        } catch(Exception $e) {
            $this->flashMsg(Helper_Message::get("flash_message.favourites.error") .  $e->getMessage(), 'error');
        }

        return true;
	}
}