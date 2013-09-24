<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Search extends Controller_Backend_Base {

	/**
	 *	@link http://readthedocs.org/docs/doctrine-orm/en/latest/reference/query-builder.html
	 */
	public function action_index() {
		$postdata = $this->request->post();
		if(!isset($postdata["searchstring"])) {
			throw new Kohana_Exception('POST-Parameter searchstring is missing', null, 400);
		}
		$searchstring = $postdata["searchstring"];

		$qb = Doctrine::instance()->createQueryBuilder();
		// In order to query the same model with another alias,
		// reset the QueryBuilder like this: $qb->resetDQLParts();

		$whereClause = $qb->expr()->orX(
				$qb->expr()->like('u.first_name', ':suchstring'),
				$qb->expr()->like('u.last_name', ':suchstring'));

		// Editors
		$editors = $qb->select('u')->from('Model_User', 'u')
				->where($qb->expr()->andX(
					'u.is_editor = true',
					'u.is_deleted = false',
					$whereClause
				))
				->setParameter("suchstring","%".$searchstring."%")
			->getQuery()->getResult();

		// Users (in a separate query)
		$users = $qb->where($qb->expr()->andX(
					'u.is_editor = false',
					'u.is_deleted = false',
					$whereClause
				))
				->setParameter("suchstring","%".$searchstring."%")
			->getQuery()->getResult();

		// Articles
		$articles = $qb->select('a')->from('Model_Article', 'a')
			->where($qb->expr()->andX(
				'a.deleted = false',
				'a.ready_for_publish = true',
				$qb->expr()->like('a.title', ':suchstring')
			))
			->setParameter("suchstring","%".$searchstring."%")
			->getQuery()->getResult();

		// Criteria
		$criteria = $qb->select('c')->from('Model_Criterion', 'c')
			->where($qb->expr()->andX(
				'c.deleted = false',
				$qb->expr()->like('c.title', ':suchstring')
			))
			->setParameter("suchstring","%".$searchstring."%")
			->getQuery()->getResult();

		$this->view->editors = $editors;
		$this->view->users = $users;
		$this->view->articles = $articles;
		$this->view->criteria = $criteria;
	}


    /**
     * Start cron "php index.php --uri=backend/search/buildSolrIndex"
     */
    function action_buildSolrIndex() {

        $this->auto_render = false;

        $articles = Doctrine::instance()->createQuery("SELECT a
                                                         FROM Model_Article a
                                                        WHERE a.ready_for_publish = 1
                                                          AND a.deleted != 1
                                                          AND a.active = 1")->getResult();
        $documents = array();
        foreach($articles as $article) {
            $documents[] = array('id' => $article->id,
                                 'title' => $article->title,
                                 'description' => html_entity_decode(strip_tags($article->description(), '<i><b><strong><u><em>'), null, 'utf-8'),
                        );
        }
        Helper_Search::deleteIndex();
        Helper_Search::sendDocuments($documents);
    }
}