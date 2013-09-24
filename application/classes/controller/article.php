<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Article extends Controller_Base {

    /**
     * Display articles
     */
    public function action_index () {

        $articleType = isset($_GET['type']) ? $_GET['type'] : $this->request->param('id', 'search');

        if($_POST AND !$this->request->is_ajax()) {
            $this->request->redirect(Url::get());
        }

        $planningOnly = $this->request->param('param', false) === 'planning';
        $params = Helper_Article::getFilterParams();

        $sort = Arr::get($params, 'sort', Helper_Article::getDefaultSort($articleType));
        $params['sort'] = $sort;
        $this->view->params = $params;

        $this->view->types = Helper_Article::getTypes(true);
        $criteriaList = Helper_Article::getCriteriaListWithoutUnusedSelectOptions($articleType);

        if(!isset($params['criteria']) || !is_array($params['criteria'])) {
            $params['criteria'] = array();
        }

        $this->view->planningCriteria = $this->getPlanningCriteria($criteriaList, $params['criteria']);
        $this->view->selectedCriteria = $this->getSelectedCriteria($criteriaList, $params['criteria'], $articleType);
        $this->view->criteria = $criteriaList;

        $this->view->type = $articleType;

        $this->view->typeName = Helper_Message::get("article.add_{$this->view->type}");
        $this->view->shareConfUrl = urlencode(Url::base() . Url::get(array(
                        'route' => 'default',
                        'controller' => $this->request->controller(),
                        'action' => 'index',
                        'id' => $articleType,
                    )) . '?' . http_build_query($_GET));

        $archivArticles = $this->request->param('param', false) === 'archiv' ? true : false;

        if(in_array($articleType, array('news', 'event'))) {
            $resultsArchiv = Helper_Article::getFilteredArticles($articleType, !$archivArticles);
            $this->view->archivArticleCount = $resultsArchiv['counts'][$articleType];
        }

        if(!$planningOnly) {

            $results = Helper_Article::getFilteredArticles($articleType, $archivArticles);

            $this->view->articleCount = $results['counts'];

            $results = $results['articles'][$articleType];

            if($articleType == 'search') {
                $this->view->groupList = true;
            } else if(in_array($articleType, array('event'))) {
                $this->view->dateList = true;
                $results = $this->sortForDateList($results, $articleType, $sort);
            } elseif($this->checkForGroupList($articleType)) {
                $this->view->groupList = true;
            }

            $this->view->results = $results;
        }

        $this->view->planningOnly = $planningOnly;
        $this->view->archivArticles = $archivArticles;
        $this->view->dateTimeNow = new DateTime('now');
        $dt = new DateTime('now');
        $this->view->dateTime = $dt->modify('-2 weeks');
        $this->response->headers('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Return the artive criterias for the planning page
     *
     * @param $criteriaList
     * @param $activeOptions
     * @return array
     */
    private function getPlanningCriteria($criteriaList, $activeOptions) {
        $planningCriteria = array();
        foreach($criteriaList as $c) {
            foreach($c->options as $option) {
                if($c->showInPlanner && in_array($option->id, $activeOptions) && $option->default == false) {
                    if(!isset($planningCriteria[$c->title])) {
                        $planningCriteria[$c->title] = array();
                    }
                    $planningCriteria[$c->title][] = $option;
                }
            }
        }
        return $planningCriteria;
    }

    /**
     * Get selected criterias
     *
     * @param $criteriaList
     * @param $activeOptions
     * @param $articleType
     * @return array
     */
    private function getSelectedCriteria($criteriaList, $activeOptions, $articleType) {
        $selectedCriteria = array();
        foreach($criteriaList as $c) {
            if($c->isArticleTypeAllowed($articleType)) {
                foreach($c->options as $option) {
                    if($c->showInPlanner == false && in_array($option->id, $activeOptions) && $option->default == false) {
                        if(!isset($selectedCriteria[$c->title])) {
                            $selectedCriteria[$c->title] = array();
                        }
                        $selectedCriteria[$c->title][] = $option;
                    }
                    if(count($option->childOptions)) {
                        foreach($option->childOptions as $childOption) {
                            if($c->showInPlanner == false && in_array($childOption->id, $activeOptions) && $option->default == false) {
                                if(!isset($selectedCriteria[$c->title])) {
                                    $selectedCriteria[$c->title] = array();
                                }
                                $selectedCriteria[$c->title][] = $childOption;
                            }
                        }
                    }
                }
            }
        }
        return $selectedCriteria;
    }

    /**
     * Check if the article type has grouped criterias
     *
     * @param $articleType
     * @return bool
     */
    function checkForGroupList($articleType) {
        $params = Helper_Article::getFilterParams();
        $criteria = Arr::get($params, 'criteria', array());

        foreach($criteria as $key => $c) {
            $opt = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($c);
            if($opt && $opt->criterion->isGroupedArticleType($articleType) && $opt->default == true) {
                return true;
            }
            elseif($opt && $opt->criterion->isGroupedArticleType($articleType) && $opt->default == false) {
                return false;
            } elseif($opt && $opt->criterion->isGroupedArticleType($articleType) && $opt->criterion->type == 'check') {
                return false;
            }
        }

        // get default options
        $criterias = Doctrine::instance()->getRepository('Model_Criterion')->findBy(array('deleted' => 0));
        foreach($criterias as $c) {
            if($c->isArticleTypeAllowed($articleType) && $c->isGroupedArticleType($articleType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ajax method to get the currently filtered article count
     */
    public function action_resultcount() {
       $this->auto_render = false;

       Helper_Article::saveFilterParams();

       $typeCount = Helper_Article::getArticleResultCount();

       $all = Helper_Article::getArticleCountWithoutFilter();
       $this->response->body(json_encode(array('filtered' => $typeCount, 'all' => $all)));
    }

    /**
     * Return a ical article file
     *
     * @throws Kohana_Exception
     */
    function action_ical() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id)) {
            throw new Kohana_Exception('Article not found', null, 404);
        }
        $vcard = "BEGIN:VCALENDAR\n";
        $vcard .= "VERSION:2.0\n";
        $vcard .= "PRODID:" .Url::base() . Url::get(array('route' => 'default',
                                                    'controller' => 'article',
                                                    'action' => 'show',
                                                    'id' => $article->id)) . "\n";

        $vcard .= "CALSCALE:GREGORIAN\n";
        $vcard .= "METHOD:PUBLISH\n";
        $vcard .= "BEGIN:VEVENT\n";
        $vcard .= "ORGANIZER:CN=\"" . $article->organized_by . "\":MAILTO:" . $article->email . "\n";
        $vcard .= "SUMMARY:" . $article->title  . "\n";
        $vcard .= "LOCATION:" . ($article->venue ? $article->venue . ', ' : '') . $article->street . ' ' . $article->street_nr . ', ' . $article->zip . ' ' . $article->city   . "\n";
        $vcard .= "DESCRIPTION:" . trim(str_replace("\n", '\n', strip_tags($article->description)))  . "\n";

        if($article->start_date) {
            $vcard .= "UID:" . md5(date('Ymd\THis', $article->start_date->format('U')).date('Ymd\THis', $article->end_date->format('U')).$article->title) . "\n";
            $vcard .= "DTSTART:" . date('Ymd\THis', $article->start_date->format('U')) . "\n";
            $vcard .= "DTEND:" . date('Ymd\THis', $article->end_date->format('U')) . "\n";
        }

        $vcard .= "DTSTAMP:" . date('Ymd\THis', time()) . "\n";

        $vcard .= "END:VEVENT\n";
        $vcard .= "END:VCALENDAR\n";

        $this->auto_render = false;

        $this->response->body(utf8_encode($this->convertToUTF8($vcard)));
        $this->response->send_file(true, $this->convertToUTF8($article->title) . ".ics");
    }

    /**
     * Convert a string to utf-8
     *
     * @param $str
     * @return string
     */
    private function convertToUTF8($str) {
        // to fix latin issues
        return html_entity_decode(mb_convert_encoding($str,'HTML-ENTITIES','UTF-8'));
    }

    /**
     * @param $results
     * @param $articleType
     * @param $sort
     * @return mixed
     */
    private function sortForDateList($results, $articleType, $sort) {
        $sorted = array();
        //$dateFormat = Kohana::$config->load('project.dateformat');

        foreach($results as &$result) {

            if(!is_object($result)) {
                continue;
            }

            foreach($result->articles as $article) {
                if($articleType === 'news' || $articleType === 'event') {

                    if($articleType === 'event') {
                        $dateKey = $article->start_date->format('Ym');
                    } else {
                        $dateKey = $article->date->format('Ym');
                    }


                    if(!isset($sorted[$dateKey])) {

                        if($articleType === 'event') {
                            $sorted[$dateKey] = array('date' => strftime('%B %Y', $article->start_date->format('U')),
                                                      'articles' => array());
                         } else {
                            $sorted[$dateKey] = array('date' => strftime('%B %Y', $article->date->format('U')),
                                                      'articles' => array());
                         }
                    }
                    $sorted[$dateKey]['articles'][] = $article;
                }
            }

            if($sort != 'title') {
                ksort($sorted);
            }

            $result->articles = $sorted;
        }

        return $results;
    }

    /**
     * Ajax function to get article count with filters
     */
    public function action_getArticleTypeFilterCount() {
        $this->auto_render = false;
        $count = Helper_Article::getArticleTypeCount();

        $this->response->body(json_encode($count['counts']));
    }

    public function action_create() {
        $this->checkRights('user');

        $types = Helper_Article::getTypes(true, false);
        $this->view->types = $types;

        $this->render_body = false;
    }

    /**
     * @throws Kohana_Exception
     */
    public function action_new() {
        $this->checkRights('user');
        $types = Helper_Article::getTypes(true);

        if(!$type = $this->request->param('id') OR !array_key_exists($type, $types)) {
            throw new Kohana_Exception('No article type set or invalid', null, 404);
        }

        try{
            $articleClass = "Model_Article_" . ucfirst($type);
            $article = new $articleClass;
            $article->user = Helper_User::getUser();

            Doctrine::instance()->persist($article);
            Doctrine::instance()->flush();

            $this->request->redirect(Url::get(array(
                'route' => 'default',
                'controller' => $this->request->controller(),
                'action' => 'edit',
                'id' => $article->id,
            )));
        } catch(Exception $e) {
            $this->msg(Helper_Message::get('flash_message.article_saved_error'), 'error');
            $this->request->redirect(Url::get('route:home'));
        }
    }

    /**
     * @throws Kohana_Exception
     */
    public function action_newquestion() {
        $this->checkRights('user');


        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id)) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        try{
            $article = new Model_Article_Qa();
            $article->user = Helper_User::getUser();
            $article->setLinked_articles(array($id));

            Doctrine::instance()->persist($article);
            Doctrine::instance()->flush();

            $this->request->redirect(Url::get(array(
                'route' => 'default',
                'controller' => 'article',
                'action' => 'edit',
                'id' => $article->id,
            )));
        } catch(Exception $e) {
            $this->msg(Helper_Message::get('flash_message.article_saved_error'), 'error');
            $this->request->redirect(Url::get('route:home'));
        }
    }

    /**
     * Edit article
     *
     * @throws Kohana_Exception
     */
    public function action_edit() {
        $this->checkRights('user');

        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        $user = Helper_User::getUser();

        if(!$user->isEditor()) {
            if($user != $article->user) {
                $this->flashMsg(Helper_Message::get('flash_message.not_allowed_to_edit'), 'notice');
                return $this->redirectBack();
            } elseif($article->active == 1 && !($user == $article->user && $article->type() == 'expert')) {
                $this->flashMsg(Helper_Message::get('flash_message.article_already_submitted'), 'notice');
                return $this->redirectBack();
            }
        }

        $types = Helper_Article::getTypes();
        $articleTitle = Arr::get($types, $article->type());

        $this->view->data = $article->getForm();

        $this->view->title = $articleTitle;
        $this->view->type = $article->type();
        $this->view->showAddExpertLink = false;

        if($article->type() == 'study' && Kohana::$config->load('project.add_expert.study') === true) {
            $this->view->showAddExpertLink = true;
        } elseif($article->type() == 'expert' && Kohana::$config->load('project.add_expert.expert') === true) {
            $this->view->showAddExpertLink = true;
        }

        $this->view->article = $article;
        $this->view->users = Helper_User::getUsers();

        if($_POST) {
            try {
                $article->from_array($_POST);
                switch(Arr::get($_POST, 'save')) {
                    case 'verification':
                        $article->ready_for_publish = true;
                        break;
                    case 'publish':
                        $article->ready_for_publish = true;
                        break;
                    default:
                        //$article->ready_for_publish = false;
                }

                if(isset($_POST['active']) && $_POST['active'] == '0') {
                    $article->active = false;
                    $article->ready_for_publish = false;
                }

                if (isset($_POST['user']) && $user->isEditor() && ($articleUser = Doctrine::instance()->getRepository('Model_User')->findOneById($_POST['user']))) {
                    $article->user = $articleUser;
                }

                Doctrine::instance()->persist($article);
                Doctrine::instance()->flush();

                if($article->active && $article->ready_for_publish) {

                    Helper_Search::sendDocuments(array(array(
                         'id' => $article->id,
                         'title' => $article->title,
                         'description' => html_entity_decode(strip_tags($article->description(), '<i><b><strong><u><em>'), null, 'utf-8'),
                    )));

                    $link = Url::base() . Url::get(array('route' => 'default',
                                            'controller' => 'article',
                                            'action' => 'show',
                                            'id' => $article->id));

                    $mail = Helper_Message::get('email.published', array('title' => $article->title, 'link' => $link));
                    mail($article->user->email, Helper_Message::get('email.published.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Kohana::$config->load('project.email.published.from'));
                }

                $this->flashMsg(Helper_Message::get("flash_message.article_saved"), 'success');

                $this->request->redirect(Url::get(array('route' => 'default',
                                                            'controller' => 'article',
                                                            'action' => 'show',
                                                            'id' => $article->id,
                )) . (!$user->isEditor() && $article->ready_for_publish ? '#submitted-' . $article->id : ''));

            } catch(Exception $e) {
                $this->msg(Helper_Message::get("flash_message.article_saved_error"), 'error');
            }
        }
    }

    /**
     * Publish article
     *
     * @throws Kohana_Exception
     */
    public function action_publish() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }
        $user = Helper_User::getUser();

        if($user->id != $article->user->id) {
            throw new Kohana_Exception("You can't edit this article", null, 404);
        }

        $article->ready_for_publish = true;
        Doctrine::instance()->persist($article);
        Doctrine::instance()->flush();

        $this->request->redirect(Url::get(array(
            'route' => 'default',
            'controller' => 'favorites',
            'action' => 'index'
        )) . ($user->isEditor() ? '#article-' . $article->id : '#submitted-' . $article->id));
    }

    /**
     * Save article step
     *
     * @return mixed
     * @throws Kohana_Exception
     */
    public function action_savearea() {
        $this->auto_render = false;

        if(!$id = Arr::get($_POST, 'id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Validation fails: article not found');
        }

        if(!$area = Arr::get($_POST, 'area')) {
            throw new Kohana_Exception('Validation fails: no area present');
        }

        $areaFields = Helper_Article::getFieldsForArea($article->type(), $area);
        $values = Arr::extract($_POST, $areaFields, null);

        $validation = Helper_Article::validateArea($article->type(), $area, $values);
        $errors = array();

        if(!$valid = ($validation === true)) {
            $errors = $validation;
        }

        $article->from_array($values);

        //Doctrine::getCache()->delete("results_{$article->type()}_*");

        $user = Helper_User::getUser();
        if($user->isEditor() && isset($_POST['active']) && (int)$_POST['active'] === 1) {
            $article->ready_for_publish = true;
        }

        if($valid === true) {
            try {
                Doctrine::instance()->persist($article);
                Doctrine::instance()->flush();
            } catch (Exception $e) {
                return $this->response->body(json_encode(false));
            }
        }

        $html = SmartyView::factory('article/steps', array(
            'data' => array($area => Arr::get($article->getForm(), $area)),
            'article' => $article,
            'errors' => $errors,
        ))->render();

        return $this->response->body(json_encode(array(
            'success' => $valid,
            'stepHtml' => $html
        )));

    }

    /**
     * Validate article fields
     *
     * @return mixed
     * @throws Kohana_Exception
     */
    public function action_validatearea() {
        $this->auto_render = false;

        if(!$type = Arr::get($_GET, 'type')) {
            throw new Kohana_Exception('Validation fails: no type present');
        }

        if(!$area = Arr::get($_GET, 'area')) {
            throw new Kohana_Exception('Validation fails: no area present');
        }

        if(($valid = Helper_Article::validateArea($type, $area, $_GET)) !== true) {
            return $this->response->body(json_encode($valid));
        }

        return $this->response->body(json_encode(true));
    }

    /**
     * Show article detail
     *
     * @throws Kohana_Exception
     */
    public function action_show() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article ' . $this->request->param('id') . ' not found', null, 404);
        }

        $user = Helper_User::getUser();

        if(!$this->hasRole("editor") && ((!$article->active || !$article->ready_for_publish) && !$article->isOwnedByCurrentUser())) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        $types = Helper_Article::getTypes(true);
        $type = $article->type();
        $articleTitle = Arr::get($types, $type);

        $this->view->title = $articleTitle;
        $this->view->type = $type;
        $this->view->article = $article;
        $this->globalData["pagetitle"] = $article->title;

        // get next and prev article
        $params = Helper_Article::getFilterParams();

        $filter = array();
        if($search = Arr::get($params, 'search')) {
            $filter['search'] = $search;
        }

        $results = Helper_Article::getFilteredArticles($type);

        $next = null;
        $prev = null;

        $this->view->articleTypeCount = $results['counts'];
        if(isset($results['articles'][$type])) {
            $results = $results['articles'][$type];
            $groupList = false;

            if($this->checkForGroupList($type)) {
                $groupList = true;
            }

            if($groupList) {
                foreach($results as &$groups) {
                    foreach($groups as &$result) {

                        $articles = $result['articles'];
                        while($cur=current($articles))
                        {
                            if($cur->id == $article->id) {
                                $prev = prev($articles);
                                if($prev == false) {
                                    $prev = false;
                                    reset($articles);
                                    $next = next($articles);
                                    break;
                                }
                                next($articles);
                                $next = next($articles);
                                break;
                            }
                            next($articles);
                        }
                    }
                }
            } else {
                foreach($results as &$result) {
                    $articles = $result->articles;
                    while($cur=current($articles))
                    {
                        if($cur->id == $article->id) {
                            $prev = prev($articles);
                            if($prev == false) {
                                $prev = false;
                                reset($articles);
                                $next = next($articles);
                                break;
                            }
                            next($articles);
                            $next = next($articles);
                            break;
                        }
                        next($articles);
                    }
                }
            }
        }

        $this->view->prevArticle = $prev;
        $this->view->nextArticle = $next;
    }

    /**
     * Create and send a PDF with article data
     *
     * @throws Kohana_Exception
     */
    public function action_pdf() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Article not found', null, 404);
        }

        $types = Helper_Article::getTypes();
        $type = $article->type();
        $articleTitle = Arr::get($types, $type);

        $this->view->title = $articleTitle;
        $this->view->type = $type;
        $this->view->article = $article;

        $this->auto_render = false;

        $tmppdf = tempnam(sys_get_temp_dir(), 'pdf') . '.pdf';
        $tmpfname = tempnam(sys_get_temp_dir(), 'temp') . '.html';
        $content = $this->view->render($this->template);
        file_put_contents($tmpfname, $content);

        $httpuser = Kohana::$config->load('project.wkhtmltopdf_http_auth_username');
        $httppassword = Kohana::$config->load('project.wkhtmltopdf_http_auth_password');

        $cmd = Kohana::$config->load('project.wkhtmltopdf_path') . " --disable-internal-links --disable-external-links --dpi 300 --username ".$httpuser." --password ".$httppassword." --disable-smart-shrinking --page-size A4 --margin-top 10 --margin-bottom 10 --margin-left 0 --encoding UTF-8 " . escapeshellarg($tmpfname) . " " . escapeshellarg($tmppdf) . "  2>&1";
        shell_exec($cmd);

        if(is_file($tmppdf)) {
            $this->response->body(file_get_contents($tmppdf));

            @unlink($tmppdf);
            @unlink($tmpfname);

            $this->response->send_file(true, 'download.pdf');
        }

        throw new Kohana_Exception('PDF not found', null, 404);
    }

    /**
     * Remove a article
     *
     * @throws Kohana_Exception
     */
    public function action_remove() {
        if(!$id = $this->request->param('id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
           throw new Kohana_Exception('Article not found', null, 404);
        }

        $user = Helper_User::getUser();

        if(!$user OR ($article->user != $user AND !$user->isEditor())) {
           $this->flashMsg(Helper_Message::get("flash_message.article_removed_not_allowed"), 'error');
           return $this->redirectBack();
        }

        if(count($article->getLinkedArticles())) {
          foreach($article->getLinkedArticles() as $larticle) {
              $article->linked_articles->removeElement($larticle);
          }
        }

        try {
            $article->deleted = true;
            Doctrine::instance()->persist($article);
            Doctrine::instance()->flush();
            $this->flashMsg(Helper_Message::get("flash_message.article_removed_successfully"), 'success');
            if($article->isOwnedByCurrentUser()) {
                $this->request->redirect(Url::get(array(
                                    'route' => 'default',
                                    'controller' => 'myarticles',
                                    'action' => 'index',
                                )));
            } else {
                $this->request->redirect(Url::get(array(
                                    'route' => 'default',
                                    'controller' => 'favorites',
                                    'action' => 'index',
                                )));
            }

        } catch(Exception $e) {
           $this->flashMsg(Helper_Message::get("flash_message.article_removed_error"), 'error');
           return $this->redirectBack();
        }
    }

    /**
     * Upload files for article
     *
     * @return mixed
     */
    public function action_upload() {
        $this->auto_render = false;

        // HTTP headers for no cache etc
        $this->response->headers("Expires", "Mon, 26 Jul 1997 05:00:00 GMT");
        $this->response->headers("Last-Modified", gmdate("D, d M Y H:i:s") . " GMT");
        $this->response->headers("Cache-Control", "no-store, no-cache, must-revalidate");
        $this->response->headers("Cache-Control", "post-check=0, pre-check=0");
        $this->response->headers("Pragma", "no-cache");

        // Settings
        $targetDir = Kohana::$config->load('project.upload_dir');

        // 5 minutes execution time
        @set_time_limit(5 * 60);

        // Uncomment this one to fake upload time
        usleep(5000);

        // Get parameters
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

        // Clean the fileName for security reasons
        $fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

        // Make sure the fileName is unique but only if chunking is disabled
        if ($chunks < 2) {
            $filename = Helper_Article::getUniqueFilename($targetDir,$fileName);
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        // Create target dir
        if (!file_exists($targetDir))
            @mkdir($targetDir);

        $contentType  = '';

        // Look for the content type header
        if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
            $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

        if (isset($_SERVER["CONTENT_TYPE"]))
            $contentType = $_SERVER["CONTENT_TYPE"];

        // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
                $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = fopen($_FILES['file']['tmp_name'], "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096))
                            fwrite($out, $buff);
                    } else
                        return $this->jsonResponse(array("jsonrpc" => "2.0", "error" => array("code" => 101, "message" => "Failed to open input stream."), "id" => "id"), false);

                    fclose($in);
                    fclose($out);
                    @unlink($_FILES['file']['tmp_name']);
                } else
                    return $this->jsonResponse(array("jsonrpc" => "2.0", "error" => array("code" => 102, "message" => "Failed to open output stream."), "id" => "id"), false);
            } else
                return $this->jsonResponse(array("jsonrpc" => "2.0", "error" => array("code" => 103, "message" => "Failed to move uploaded file."), "id" => "id"), false);
        } else {
            // Open temp file
            $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                } else
                    return $this->jsonResponse(array("jsonrpc" => "2.0", "error" => array("code" => 101, "message" => "Failed to open input stream."), "id" => "id"), false);

                fclose($in);
                fclose($out);
            } else
                return $this->jsonResponse(array("jsonrpc" => "2.0", "error" => array("code" => 102, "message" => "Failed to open output stream."), "id" => "id"), false);
        }

        // Check if file has been uploaded
        if (!$chunks || $chunk == $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
        }

        // Return JSON-RPC response
        $file = new Model_File($filePath);
        Doctrine::instance()->persist($file);
        Doctrine::instance()->flush($file);

        if(in_array($file->ext, array('pdf'))) {
            $html = SmartyView::factory('article/form/file_item', array(
                    'item' => $file,
                ))->render();
        } else {
            if(isset($_GET['logo']) && intval($_GET['logo']) == 1) {
                $html = SmartyView::factory('article/form/logo_item', array(
                        'item' => $file,
                    ))->render();
            } else {
                $html = SmartyView::factory('article/form/image_item', array(
                        'item' => $file,
                    ))->render();
            }
        }

        $this->jsonResponse(array("jsonrpc" => "2.0", "result" => null, "id" => "id", "html" => $html), false);
    }

    /**
     * Return article thumbs
     *
     * @throws Kohana_Exception
     */
    public function action_previewimage() {
        $this->auto_render = false;
        if(!$id = $this->request->param('id') OR !$file = Doctrine::instance()->getRepository('Model_File')->findOneById($id)) {
            throw new Kohana_Exception('File not found', null, 404);
        }

        $info = pathinfo($file->filename);
        $filename =  basename($file->filename,'.'.$info['extension']);

        $sizes = Kohana::$config->load('project.thumbnail_sizes');
        $returnFile = '';

        foreach($sizes as $size) {

            if(!is_dir(Kohana::$config->load('project.public_preview_path') . DIRECTORY_SEPARATOR . $size)) {
                mkdir(Kohana::$config->load('project.public_preview_path') . DIRECTORY_SEPARATOR . $size, 0777);
            }

            $dst = Kohana::$config->load('project.public_preview_path') . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $file->id . '-' . $filename. '.jpg';

            if($size == $this->request->param('size')) {
                $returnFile = $dst;
            }

            $s = explode('x', $size);
            $width = isset($s[0]) && intval($s[01]) ? intval($s[0]) : '';
            $height = isset($s[1]) && intval($s[1]) ? intval($s[1]) : '';

            if(!is_file($dst)) {
                //image
                if(substr(strtolower($file->mime), 0, 5) == 'image') {
                    $cmd = Kohana::$config->load('project.convert_path') . " {$file->path()} -resize {$width}x{$height} -gravity center -background white -extent {$width}x{$height} {$dst}";
                // pdfs
                } elseif($file->ext == 'pdf') {
                    $cmd = Kohana::$config->load('project.convert_path') . " '{$file->path()}[0]' -background '#ffffff' -resize {$size} -colorspace RGB -quality 80 {$dst}";
                } else {
                    throw new Kohana_Exception('File not found', null, 404);
                }
                $return = shell_exec($cmd . ' 2>&1');

                @chmod($dst,0777);
            }
        }

        $this->response->headers('Content-Type', 'image/jpg');
        $this->response->body(file_get_contents($returnFile));
    }

    /**
     * @throws Kohana_Exception
     */
    public function action_media() {
        $this->auto_render = false;
        if(!$id = $this->request->param('id') OR !$file = Doctrine::instance()->getRepository('Model_File')->findOneById($id)) {
            throw new Kohana_Exception('File not found', null, 404);
        }

        $info = pathinfo($file->filename);
        $filename = basename($file->filename,'.'.$info['extension']);

        $dst = Kohana::$config->load('project.public_media_path') . DIRECTORY_SEPARATOR . $file->id . '-' . $filename. '.jpg';

        if(!is_file($file->path())) {
            // throw new Kohana_Exception('File not found.', null, 404);
            error_log('File not found: ' . $file->path());
            $this->response->body('');
        } else {
            copy($file->path(), $dst);

            $this->response->headers('Content-Type', $file->mime);
            $this->response->body(file_get_contents($dst));
        }
    }

    function action_submitted() {

    }

    /**
     * Create a sharelink for the article list with filters
     *
     * @throws Kohana_Exception
     */
    function action_share() {
        if(isset($_GET['id'])) {
            $this->view->article = Doctrine::instance()->getRepository('Model_Article')->findOneById($_GET['id']);
            $this->view->id = $_GET['id'];
        } elseif(isset($_GET['url'])) {
            $params = Helper_Article::getFilterParams();
            unset($params['url']);
            unset($params['_']);
            if(isset($params['type'])) {
                unset($params['type']);
                $this->view->articleType = $_GET['type'];
            }
            $this->view->url = $_GET['url'] . (isset($_GET['url_only']) ? '' : http_build_query($params));

        }
        else {
            throw new Kohana_Exception('Share error', null, 404);
        }
    }

    /**
     * @return mixed
     * @throws Kohana_Exception
     */
    function action_ask() {

        if(!$id = Arr::get(array_merge($_POST, $_GET), 'id') OR !$article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) OR $article->deleted) {
            throw new Kohana_Exception('Validation fails: article not found');
        }

        if($_POST) {
            $validate = Validation::factory($_POST)
                        ->rule('question', 'not_empty');

            if($validate->check()) {

                $this->flashMsg(Helper_Message::get("flash_message.ask_successful"), 'success');

                $link = Url::base() . Url::get(array('route' => 'default',
                                        'controller' => 'article',
                                        'action' => 'show',
                                        'id' => $article->id));

                $mail = Helper_Message::get('email.question_info', array('text' => $_POST['question'], 'title' => $article->title, 'link' => $link));
                mail(Helper_User::getUser()->email, Helper_Message::get('email.question_info.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Kohana::$config->load('project.email.question_info.from'));

                $mail = Helper_Message::get('email.new_question', array('text' => $_POST['question'], 'title' => $article->title, 'name' => Helper_User::getUser()->getName(), 'link' => $link));
                mail(Kohana::$config->load('project.email.new_question.to'), Helper_Message::get('email.new_question.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Helper_User::getUser()->email);

                $this->auto_render = false;
                return $this->response->body('true');
            }

             $this->msg(Helper_Message::get("flash_message.login_error"), 'error');
             $this->view->errors = $validate->errors('validation');
             $this->view->values = $validate->as_array();
        }
        else {
            $this->view->title = $article->title;
        }
        $this->view->article_id = $article->id;
        $this->render_body = false;
    }

    /*
    function action_articleExport() {
        $articles = Doctrine::instance()->getRepository('Model_Article')->findBy(array('deleted' => false, 'active' => true, 'ready_for_publish' => true));
        $articleRows = '';

        foreach($articles as $article) {
            foreach($article->getCriteriaList() as $title => $opts) {
                $header[$title] = '';
            }
        }
        ksort($header);

        foreach($articles as $article) {
            $articleData = array();
            $articleData = $header;
            foreach($article->getCriteriaList() as $title => $opts) {
                $csvOptString = array();
                foreach($opts as $opt) {
                    $csvOptString[] = (string)$opt;
                }

                $articleData[$title] = implode(',',$csvOptString);
            }
            ksort($articleData);

            $articleRows .= implode(';', array($article->title, $article->type())) . ';' . implode(';', $articleData) . "\n";

        }
        $header = implode(';', array('Title', 'Type')) . ';' . implode(';', array_keys($header));
        $tmpfname = tempnam("/tmp", "FOO");
        file_put_contents($tmpfname,  $header . "\n" . $articleRows);
        $this->response->send_file($tmpfname, "article_export.csv");
        unlink($tmpfname);
    }
    */
}
