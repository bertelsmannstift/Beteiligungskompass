<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Featured extends Controller_Backend_Base {

    public function action_index() {

        $articles = Doctrine::instance()->createQuery("SELECT a FROM Model_Article a
                WHERE a.videos != 'a:0:{}'
                AND a.deleted != 1
            ORDER BY a.title ASC")->getResult();

        $videos = array();
        foreach($articles as $article) {
            foreach($article->getVideos() as $video) {
                $videos[] = array('article' => $article,
                                  'video' => $video);
            }
        }
        $this->view->videos = $videos;
    }


    public function action_toggle() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        $article->setVideo(array('url' => $_GET['video'], 'featured' => $_GET['featured'] == 'true'));
        Doctrine::instance()->persist($article);
        Doctrine::instance()->flush();
        $this->auto_render = false;
    }
}