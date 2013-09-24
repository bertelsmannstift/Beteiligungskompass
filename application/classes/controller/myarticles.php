<?php

class Controller_Myarticles extends Controller_Base {
	public function before() {
		parent::before();
        if((string)$this->request->action() != 'showgroup') {
            $this->checkRights('user');
        }
	}

    /**
     * Show all own articles
     */
    public function action_index() {
        $user = Helper_User::getUser();
        $order = Arr::get($_GET, 'orderby', 'created');
        $grouped = false;

        $favorites = $user->getFavoritesList(false, $order);

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
     * Create a new user article group
     *
     * @return mixed
     */
    function action_newgroup() {
        if(Helper_User::newArticleGroup($_POST, true) === true) {
            $this->auto_render = false;
            return $this->response->body('true');
        } else {
            list($errors, $values) = Helper_User::newArticleGroup($_POST, true);
            $this->view->errors = $errors;
            $this->view->values = $values;
        }
       $this->render_body = false;
    }
}