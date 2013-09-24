<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller_Base {

    /**
     * Show home page
     */
    public function action_index()
	{

        $articles = Doctrine::instance()->createQuery("SELECT a FROM Model_Article a
                WHERE a.videos LIKE '%\"featured\";b:1%'
                AND a.deleted != 1
            ORDER BY a.title ASC")->useResultCache(true, RESULT_CACHE_LIFETIME)->getResult();

        $videos = array();
        foreach($articles as $article) {
            foreach($article->getVideos() as $video) {
                $videoParam = false;
                if (preg_match('/^(http|https):\/\/www\.youtube\.com\/watch.*?v=([a-zA-Z0-9\-_]+).*$/i' , $video['url'], $match)) {
                    $videoParam = $match[2];
                }
                elseif (preg_match('/^http:\/\/vimeo\.com\/(\d+)$/i' , $video['url'], $match)) {
                    $videoParam = $match[1];
                }

                if($videoParam) {
                    $videos[] = array('article' => $article,
                                      'video' => $video,
                                      'videoParam' => $videoParam);
                }
            }
        }

        $latestNews = $this->getLatestNews();
        $upcomingEvents = $this->getUpcomingEvents();

        $this->view->videos = $videos;
        $this->view->latestNews = array_slice($latestNews, 0, 2);
        $this->view->latestNewsCount = $this->getLatestNewsCount();
        $this->view->eventsCount = $this->getEventsCount();
        $this->view->upcomingEvents = array_slice($upcomingEvents, 0, 2);
        $this->view->partnerlinks = Doctrine::instance()->getRepository('Model_Partnerlink')->findAll();
	}

    /**
     * @return array
     */
    private function getLatestNews() {
        return Doctrine::instance()->getRepository('Model_Article_News')->findBy(array('deleted' => 0, 'active' => 1, 'ready_for_publish' => 1), array('date' => 'desc'), 2);
    }

    /**
     * @return int
     */
    private function getLatestNewsCount() {
        $result = Helper_Article::getResult('news');
        $numberOfNews = 0;
        if($result) {
        	$numberOfNews = count($result['news']->articles);
        }
        return $numberOfNews;
    }

    /**
     * @return int
     */
    private function getEventsCount() {
        $result = Helper_Article::getResult('event');
        $numberOfEvents = 0;
        if($result) {
        	$numberOfEvents = count($result['event']->articles);
        }
        return $numberOfEvents;
    }

    /**
     * @return array
     */
    private function getUpcomingEvents() {
        $query = Doctrine::instance()->createQueryBuilder()
                ->select('a')
                ->from('Model_Article_Event', 'a')
                ->where('a.deleted = 0 AND a.active = 1 AND a.ready_for_publish = 1')
                ->orderBy('a.start_date', 'ASC');

        $dt = new DateTime('now');
        $query->andWhere("a.end_date >= :end_date");
        $query->setParameter("end_date", $dt);
        return $query->getQuery()
                    ->getResult();
    }

}
