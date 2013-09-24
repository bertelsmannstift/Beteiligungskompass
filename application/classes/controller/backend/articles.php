<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Articles extends Controller_Backend_Base {

    public function action_index() {

        /*
        // This query takes about 3s, do not use like this
        // on dev machine with about 250 articles / 2800 criterion associations
        $articles = Doctrine::instance()->createQuery("SELECT a, o, c FROM Model_Article a
                LEFT JOIN a.criteria o WITH o.deleted = false AND o.default = false
                LEFT JOIN o.criterion as c WITH c.deleted = false
                WHERE a.ready_for_publish = 1
                AND a.deleted != 1
            ORDER BY a.title ASC")->getResult();
        */
        $articles = Doctrine::instance()->createQuery("SELECT a FROM Model_Article a
                WHERE a.ready_for_publish = 1
                AND a.deleted != 1
            ORDER BY a.title ASC")->getResult();
        $this->prepareArticleList($articles);
    }

    public function action_inprogress() {
        $articles = Doctrine::instance()->createQuery("SELECT a FROM Model_Article a
            WHERE a.ready_for_publish = 0 AND a.active = 0 AND a.deleted != 1 AND a.title != ''
            ORDER BY a.title ASC")->getResult();
        $this->prepareArticleList($articles);
    }

    protected function prepareArticleList($articles) {
        $articleCounts = array();
        foreach(Model_Article::$articleTypes as $type) {
            $articleCounts[$type] = array("all" => 0, "active" => 0);
        }
        foreach($articles as $a) {
            $articleCounts[$a->type()]["all"] += 1;
            $articleCounts[$a->type()]["active"] += ($a->active) ? 1 : 0;
        }
        $this->view->articleCounts = $articleCounts;
        $this->view->articles = $articles;

        $this->template = "backend/articles/index";
    }

    public function action_edit($id) {
        $this->request->redirect(Url::get(array("controller"=>"article","action"=>"edit","id"=>$id)),301);
    }

    public function action_publish() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        if($article->active) {
            $this->flashMsg(Helper_Message::get('backend.article.already_published'), 'notice');
        } else {
            try {
                $article->active = true;
                $article->ready_for_publish  = true;

                $link = Url::base() . Url::get(array('route' => 'default',
                                        'controller' => 'article',
                                        'action' => 'show',
                                        'id' => $article->id));

                $mail = Helper_Message::get('email.published', array('title' => $article->title, 'link' => $link));

                mail($article->user->email, Helper_Message::get('email.published.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Kohana::$config->load('project.email.published.from'));

                Doctrine::instance()->persist($article);
                Doctrine::instance()->flush();
                $this->flashMsg(Helper_Message::get('backend.article.published_ok'), 'success');
            } catch(Exception $e) {
                $this->flashMsg(Helper_Message::get('backend.article.published_failed'), 'error');
            }
        }

        $this->redirectBack();
    }

    public function action_unpublish() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        if(!$article->active) {
            $this->flashMsg(Helper_Message::get('backend.article.already_unpublished'), 'notice');
        } else {
            try {
                $article->active = false;
               // $article->ready_for_publish = false;
                Doctrine::instance()->persist($article);
                Doctrine::instance()->flush();
                $this->flashMsg(Helper_Message::get('backend.article.unpublished_ok'), 'success');
            } catch(Exception $e) {
                $this->flashMsg(Helper_Message::get('backend.article.unpublished_failed'), 'error');
            }
        }

        $this->redirectBack();
    }

    public function action_remove() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        if(count($article->getLinkedArticles())) {
            foreach($article->getLinkedArticles() as $larticle) {
                $article->linked_articles->removeElement($larticle);
            }
        }

        try {
            $article->deleted = true;
            $article->active = false;
            Doctrine::instance()->persist($article);
            Doctrine::instance()->flush();
            $this->flashMsg(Helper_Message::get("flash_message.article_removed_successfully"), 'success');
        } catch(Exception $e) {
            $this->flashMsg(Helper_Message::get("flash_message.article_removed_error"), 'error');
        }

        $this->redirectBack();
    }
/*
    public function action_list() {
        $articleType = $this->request->param('id',null);
        if(!in_array($articleType,Model_Article::$articleTypes)) {
            throw new Kohana_Exception('Invalid value given for type of article parameter', null, 400);
        }

        $articles = Helper_Article::getAllArticles($articleType);
        $this->view->articles = $articles;
    }
*/
}