<?

class Helper_Article {

    private static $_fields = null;
    private static $_articles = null;
    private static $_data = null;
    private static $_sessionCacheTime = '10 minutes';
    private static $_newsArchivTime = '-6 month';
    // cache disabled, da es probleme gemacht hat mit dem planning filter
    private static $_sessionCacheActive = false;
    private static $_solrArticles = array();

    /**
     * @return array|null
     */
    public static function getArticleConfig() {
        if(!self::$_articles) {
            self::$_articles = Yaml::loadFile(APPPATH . 'data/articleconfig.yaml');
        }
        return self::$_articles;
    }

    /**
     * @param bool $type
     * @return array
     */
    public static function getFields($type = false) {
        $fields = Yaml::loadFile(APPPATH . 'data/fieldconfig.yaml');

        foreach($fields as $field => $data) {

            $label = Helper_Message::get("label." . $field);

            if($type !== false && isset($data['labels']) && isset($data['labels'][$type])) {
                $label = Helper_Message::get("label." . $data['labels'][$type]);
            }

            $fields[$field] = (object) array_merge(array(
                'key' => $field,
                'label' => $label,
                'type' => 'textarea',
                'rules' => array(),
                ), $data ? $data : array());
        }

        return self::$_fields = $fields;
    }

    /**
     * @param bool $articleType
     * @param bool $archivArticles
     * @return mixed
     */
    public static function getFilteredArticles($articleType = false, $archivArticles = false) {
        $returnResult = array('articles' => array(),
                              'counts' => array());

        $data = Session::instance()->get('articlefilter_result_' . $articleType, false);
        if($data != false) {
            $data = unserialize($data);
        }

        $criteriaHash = self::getCriteriaHash($archivArticles);

        if(self::$_sessionCacheActive == false ||
            $data == false ||
            ($data['criteria_hash'] != $criteriaHash) ||
            $data['hash_time'] < strtotime('-' . self::$_sessionCacheTime, time())) {

            $result = self::getFilteredArticleResult($articleType, $archivArticles);
            $returnResult['articles'][$articleType] = $result['articles'];
            $returnResult['counts'][$articleType] = $result['counts'];

            $data = array('results' => $returnResult,
                            'criteria_hash' => $criteriaHash,
                            'hash_time' => time());

            Session::instance()->set('articlefilter_result_' . $articleType, serialize($data));
        }

        return $data['results'];
    }

    /**
     * @param $articleType
     * @param $archivArticles
     * @return array
     */
    private static function getFilteredArticleResult($articleType, $archivArticles) {
        $returnResult = array('articles' => array(),
                              'counts' => array());

        Helper_Article::saveFilterParams();
        $params = self::getFilterParams();
        $filter = array();

        if($search = Arr::get($params, 'search')) {
          $filter['search'] = trim($search);
        }

        $criteria = Arr::get($params, 'criteria', array());

        if($articleType == false) {
          $sort = Arr::get($params, 'sort', false);
        } else {
          $sort = Arr::get($params, 'sort', Helper_Article::getDefaultSort($articleType));
        }

        $result = Helper_Article::getResult(
            $articleType,
            $criteria,
            $sort,
            $filter,
            $archivArticles
            );

        if(count($result) == 0) {
            $returnResult['articles'] = $result;
            $returnResult['counts'] = 0;
            return $returnResult;
        }

        $groupCriteria = false;
        $checkboxGroup = false;
        $noDefaultSet = true;

        foreach($criteria as $c) {
            // check for group criteria
            $opt = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($c);
            if($opt && $opt->criterion->isGroupedArticleType($articleType) && $opt->default == true) {
                $groupCriteria = $opt;
                break;
            }
            elseif($opt && $opt->criterion->isGroupedArticleType($articleType) && $opt->default == false) {
                $noDefaultSet = false;
                break;
            } elseif($opt && $opt->criterion->isGroupedArticleType($articleType) && $opt->criterion->type == 'check') {
                $checkboxGroup = true;
                break;
            }
        }

        if($articleType == 'search') {
            $returnResult['counts'] = self::groupByType($result);
        } else if($groupCriteria) {
            // group if group criteria is found
            $returnResult['counts'] = self::sortForGroupList($result, $groupCriteria->criterion->options, $sort);
        } else {
            $returnResult['counts'] = (isset($result[$articleType]) ? count($result[$articleType]->articles) : 0);
        }

        if(!$groupCriteria && ($checkboxGroup == false && $noDefaultSet == true)) {
            // get default (all) options if no criteria is set

            $criterias = Doctrine::instance()->getRepository('Model_Criterion')->findBy(array('deleted' => 0));
            foreach($criterias as $c) {
                if($c->isArticleTypeAllowed($articleType) && $c->isGroupedArticleType($articleType)) {
                    $returnResult['counts'] = self::sortForGroupList($result, $c->options, $sort);
                    break;
                }
            }
        }

        $returnResult['articles'] = $result;
        return $returnResult;
    }

    /**
     * @param bool $archivArticles
     * @return mixed
     */
    static function getArticleResultCount($archivArticles = false) {

        $data = Session::instance()->get('type_results', false);
        if($data != false) {
            $data = unserialize($data);
        }

        $criteriaHash = self::getCriteriaHash($archivArticles);

        if(self::$_sessionCacheActive == false ||
            $data == false ||
            ($data['criteria_hash'] != $criteriaHash) ||
            $data['hash_time'] < strtotime('-' . self::$_sessionCacheTime, time())) {

            $params = self::getFilterParams();
            $criteria = Arr::get($params, 'criteria', array());
            $filter = array();

            if($search = Arr::get($params, 'search')) {
              $filter['search'] = $search;
            }
            $counts = array();

            foreach(self::getTypes(true) as $k => $name) {
                $result = Helper_Article::getResult(
                    $k,
                    $criteria,
                    'title',
                    $filter,
                    $archivArticles
                    );
                $counts[$k] = isset($result[$k]->articles) ? count($result[$k]->articles) : 0;
            }

            $data = array('results' => $counts,
                          'criteria_hash' => $criteriaHash,
                          'hash_time' => time());

            Session::instance()->set('type_results', serialize($data));
        }

        return $data['results'];
    }

    /**
     * @param string $add
     * @return string
     */
    private static function getCriteriaHash($add = '') {
        $params = self::getFilterParams();
        $search = Arr::get($params, 'search');
        $criteria = Arr::get($params, 'criteria', array());
        $criteriaHash = md5(serialize($criteria) . $search . $add);
        return $criteriaHash;
    }

    /**
     * @param $results
     * @param $options
     * @param $sort
     * @return int
     */
    private static function sortForGroupList(&$results, $options, $sort) {
        $group = array();
        $doublicateGroupedArticles = array();
        $countBefore = 0;

        foreach($results as $type => &$result) {
            $countBefore = count($result->articles);
            foreach($result->articles as $article) {

                foreach($options as $opt) {
                    $groupkey = $opt->default ? 'ALL' : $opt->id;
                    if(!isset($group[$type][$groupkey])) {
                        $group[$type][$groupkey] = array('grouptitle' => $opt->title,
                           'articles' => array());
                    }

                    if((($article->criteria && $article->criteria->contains($opt)) || $opt->default == true)) {
                        if(in_array($article->id, $doublicateGroupedArticles) && $opt->default == false) {
                            unset($group[$type]['ALL']['articles'][$article->id]);
                        }
                        $group[$type][$groupkey]['articles'][$article->id] = $article;

                        $doublicateGroupedArticles[$article->id] = $article->id;
                    }
                }
            }
        }
        $results = $group;
        return $countBefore;
    }

    /**
     * @param $results
     * @return int
     */
    private static function groupByType(&$results) {
        $group = array();
        $count = 0;

        foreach($results as $type => &$result) {
            foreach($result->articles as $article) {
                $count++;
                if(!isset($group[$type]['search'])) {
                    $group[$type]['search'] = array('grouptitle' => Helper_Message::get("article_config.{$type}.title.plurality"),
                                                    'articles' => array());
                }
                $group[$type]['search']['articles'][$article->id] = $article;
            }
        }
        $results = $group;
        return $count;
    }

    /**
     * @param $criteria
     * @param $type
     * @return array
     */
    public static function removeDisabledCriteria($criteria, $type) {

        $result = array();

        if(count($criteria)) {
            $results = Doctrine::instance()
            ->createQuery("SELECT o.id FROM Model_Criterion_Option as o JOIN o.criterion as c
             WHERE c.deleted = 0 AND o.deleted = 0 AND o.id IN (" . implode(',', $criteria) . ") AND c.articleTypes LIKE '%{$type}%' ")
            ->getResult();

            foreach($results as $res) {
                $result[] = $res['id'];
            }
        }

        return $result;
    }

    public static function resetFilterParams() {
        Session::instance()->delete('filter_params');
    }

    /**
     * @return array
     */
    public static function getFilterParams() {

        $session_params = Session::instance()->get('filter_params', array());

        $params = array_merge($_POST, $_GET);

        // Global search term check
        if(isset($params['term']) && !empty($params['term'])) {
            $params['search'] = urldecode(($params['term']));
        } else { // dont reset planning filters on global search
            if(count($params) AND empty($params['criteria']) AND isset($session_params['criteria'])) {
                unset($session_params['criteria']);
            }
        }

        $params = array_merge($session_params, $params);

        return $params;
    }


    public static function saveFilterParams() {
        Session::instance()->set('filter_params', self::getFilterParams());
    }

    /**
     * @return mixed
     */
    public static function getUserState() {
        return Session::instance()->get('user_state', array());
    }

    /**
     * @param array $user_state
     */
    public static function saveUserState(array $user_state) {
        Session::instance()->set('user_state', array_merge(self::getUserState(), $user_state));
    }

    /**
     * @param bool $articleType
     * @return array
     * @throws Kohana_Exception
     */
    private static function getData($articleType = false) {

        if(isset(self::$_data[$articleType])) {
            return self::$_data[$articleType];
        }

        $result = array();

        $mobile = new Helper_Mobile();
        //default version
        $versionKey = 'desktop';

        if($mobile->isMobile() && !$mobile->isTablet()) {
            $versionKey = 'mobile';
        }

        $fieldlist = self::getFields($articleType);
        $config = Yaml::loadFile(APPPATH . 'data/articleconfig.yaml');

        foreach($config as $type => $article) {

        // we set the version mobile/desktop
            $article['main'] = is_array($article['main'][$versionKey]) ? $article['main'][$versionKey] : array();
            $article['sidebar'] = is_array($article['sidebar'][$versionKey]) ? $article['sidebar'][$versionKey] : array();

            $item = (object) array_merge(
                $article,
                array(
                    'form' => array(),
                    'sidebar' => array(),
                    'main' => array(),
                    ));
            $item->title = Helper_Message::get("article_config." . $type . ".title");

            $sectionNumber = 1;
            foreach($article['form'] as $areaId => $area) {
                $areaItem = (object) array_merge(
                    $area,
                    array('fields' => array())
                    );
                $areaItem->title = Helper_Message::get("article_config.".$type.".section_" . $sectionNumber . ".title");
                //$areaItem->subtitle = Helper_Message::get("article_config.".$type.".section_" . $sectionNumber . ".subtitle");
                $areaItem->description = Helper_Message::get("article_config.".$type.".section_" . $sectionNumber . ".description");

                if(count($area['fields'])) {
                    foreach($area['fields'] as $fieldName) {
                        if(!$field = Arr::get($fieldlist, $fieldName)) {
                            throw new Kohana_Exception('Field :fieldName in article :type not found', array(':fieldName' => $fieldName, ':type' => $type));
                        }

                        $areaItem->fields[$fieldName] = $field;
                    }
                }

                $item->form[$areaId] = $areaItem;
                $sectionNumber++;
            }

            foreach($article['sidebar'] as $fieldName) {
                if(!$field = Arr::get($fieldlist, $fieldName)) {
                    throw new Kohana_Exception('Field :fieldName in article :type not found', array(':fieldName' => $fieldName, ':type' => $type));
                }
                $item->sidebar[$fieldName] = $field;
            }

            foreach($article['main'] as $fieldName) {
                if(!$field = Arr::get($fieldlist, $fieldName)) {
                    throw new Kohana_Exception('Field :fieldName in article :type not found', array(':fieldName' => $fieldName, ':type' => $type));
                }
                $item->main[$fieldName] = $field;
            }

            $result[$type] = $item;
        }
        self::$_data[$articleType] = $result;
        return $result;
    }

    /**
     * @param $articleType
     * @return mixed
     * @throws Kohaha_Exception
     */
    public static function getForm($articleType) {

        $data = self::getData($articleType);

        if(!$article = Arr::get($data, $articleType)) {
            throw new Kohaha_Exception('No formdata for type :type found', array('type' => $articleType));
        }
        return $article->form;
    }

    /**
     * @param $type
     * @return array
     */
    public static function getFieldsFor($type) {
        $fields = array();
        foreach(self::getForm($type) as $area) {
            foreach($area->fields as $key => $field) {
                $fields[$key] = $field;
            }
        }
        return $fields;
    }

    /**
     * @param bool $getAll
     * @param bool $onlyActive
     * @return array
     */
    public static function getTypes($getAll = false, $onlyActive = true) {
        $types = array();

        foreach(self::getData() as $key => $type) {
            $active = $onlyActive ? Helper_Module::isActive($key) || ($key == 'expert' && Kohana::$config->load('project.add_expert.global')) : true;
            if((!isset($type->owntype) || $type->owntype === false || $getAll) && $active) {
                $types[$key] = $type->title;
            }
        }
        return $types;
    }

    /**
     * @param $type
     * @param $area
     * @return array|bool
     */
    public static function getFieldsForArea($type, $area) {
        if(!$area = Arr::get(self::getForm($type), $area, false)) {
            return false;
        }
        return array_keys($area->fields);
    }

    /**
     * @param $type
     * @param $area
     * @param $input
     * @return array|bool
     */
    public static function validateArea($type, $area, &$input) {
        if(!$area = Arr::get(self::getForm($type), $area, false)) {
            return false;
        }

        $errors = array();
        $fields = $area->fields;

        // External Links
        if(isset($input['external_links'])) {
            $external_links = array();
            if(count($input['external_links'])) {
                foreach($input['external_links'] as $val) {
                    if(!isset($val['url'])) {
                        continue;
                    }
                    $url = trim($val['url']);
                    $showLink = isset($val['show_link']);
                    if($url AND $url !== '') {
                        $external_links[] = array(
                            'url' => $url,
                            'show_link' => $showLink
                            );
                    }
                }

                if(count($external_links)) {
                    foreach($external_links as $key => $val) {
                        if(!Valid::url($val['url'])) {
                            $errors['external_links'][$key] = 'Please enter a valid url';
                        }
                    }
                }
            }
        }

        //Videos
        if(isset($input['videos'])) {
            $videos = array();

            if(count($input['videos'])) {

                foreach($input['videos'] as $k => $val) {
                    $val['url'] = trim($val['url']);
                    if($val['url'] AND $val['url'] !== '') {
                        $videos[] = array('url' => $val['url'], 'featured' => false);
                    }
                }

                if(count($videos)) {
                    foreach($videos as $key => $val) {
                        if(!preg_match('/^http:\/\/vimeo\.com\/(\d+)$/i', $val['url']) AND !preg_match('/^(http|https):\/\/www\.youtube\.com\/watch.*?v=([a-zA-Z0-9\-_]+).*$/i' , $val['url'])) {
                            $errors['videos']['url'][$key] = 'Please enter a valid youtube/vimeo url';
                        }
                    }
                }
            }
        }

        $validation = $validation = Validation::factory($input);

        foreach($fields as $field => $values) {
            if($values->label) {
                $validation->label($field, $values->label);
            }

            if($values->rules) {
                foreach($values->rules as $rule => $params) {
                    $validation->rule($field, $rule, $params);
                }
            }
        }

        if(!$validation->check()) {
            $errors = array_merge($validation->errors('validate'), $errors);
        }

        return count($errors) > 0 ? $errors : true;
    }

    /**
     * @return array
     */
    public static function getCriteriaList() {
        $items = Doctrine::instance()
        ->createQuery('SELECT c, o FROM Model_Criterion as c JOIN c.options as o
         WHERE c.deleted = 0 AND o.deleted = 0 AND o.parentOption IS NULL
         ORDER BY c.orderindex ASC, c.title ASC, o.orderindex ASC, o.title ASC')->useResultCache(true, RESULT_CACHE_LIFETIME)
        ->getResult();

        return $items;
    }

    /**
     * @param bool $articleType
     * @return array
     */
    public static function getCriteriaListWithoutUnusedSelectOptions($articleType = false) {

        $items = Doctrine::instance()
        ->createQuery("SELECT c, o, o2
                         FROM Model_Criterion as c

                         JOIN c.options o WITH o.deleted = 0 AND o.parentOption IS NULL
                    LEFT JOIN o.articles a WITH a.deleted = 0 AND a.active = 1 AND a.ready_for_publish = 1

                    LEFT JOIN o.childOptions o2 WITH o2.deleted = 0 AND o2.parentOption IS NOT NULL
                    LEFT JOIN o2.articles a2 WITH a2.deleted = 0 AND a2.active = 1 AND a2.ready_for_publish = 1

                        WHERE c.deleted = 0
                          AND (a.id IS NOT NULL OR o.default = 1)
                          AND ((a2.id IS NOT NULL OR o2.default = 1) OR o2.id IS NULL)

                     GROUP BY c.id, o.id, o2.id
                     ORDER BY c.orderindex ASC, c.title ASC")->useResultCache(true, RESULT_CACHE_LIFETIME)
                    ->getResult();

        if($articleType) {
            $grouppedCriteriaList = array();

            foreach($items as $k => $cl) {
                if($cl->isGroupedArticleType($articleType)) {
                    $grouppedCriteriaList[] = $cl;
                    unset($items[$k]);
                    break;
                }
            }
            $items = array_merge($grouppedCriteriaList, $items);
        }

        return $items;
    }

    /**
     * @param $articleType
     * @return mixed
     * @throws Kohaha_Exception
     */
    public static function getSidebar($articleType) {
        $data = self::getData($articleType);

        if(!$article = Arr::get($data, $articleType)) {
            throw new Kohaha_Exception('No sidebar for type :type found', array('type' => $articleType));
        }
        return $article->sidebar;
    }

    /**
     * @param $articleType
     * @return mixed
     * @throws Kohaha_Exception
     */
    public static function getMain($articleType) {
        $data = self::getData($articleType);


        if(!$article = Arr::get($data, $articleType)) {
            throw new Kohaha_Exception('No main for type :type found', array('type' => $articleType));
        }

        return $article->main;
    }

    /**
     * @return mixed
     */
    static function getArticleCountWithoutFilter() {
        $typeResult = array();

        $data = Session::instance()->get('type_results_all', false);
        if($data != false) {
            $data = unserialize($data);
        }

        $criteriaHash = self::getCriteriaHash();

        if(self::$_sessionCacheActive == false ||
            $data == false ||
            ($data['criteria_hash'] != $criteriaHash) ||
            $data['hash_time'] < strtotime('-' . self::$_sessionCacheTime, time())) {

            $query = Doctrine::instance()->createQueryBuilder();
            $query->from("Model_Article", 'a')->andWhere("a.active=1 AND a.deleted!=1");
            $result = $query->select('a')->groupBy('a.id')->getQuery()->useResultCache(true, RESULT_CACHE_LIFETIME)->getResult();
            $dt = new DateTime('now');

            foreach($result as $a) {
                if(!isset($typeResult[$a->type()])) {
                    $typeResult[$a->type()] = 0;
                }
                if($a->type() == 'event' && $a->end_date < $dt) {
                    continue;
                }
                if($a->type() == 'news') {
                    $news_dt = new DateTime('now');
                    if($a->date < $news_dt->modify(self::$_newsArchivTime)) {
                        continue;
                    }
                }
                $typeResult[$a->type()] += 1;
            }

            $data = array('results' => $typeResult,
                          'criteria_hash' => $criteriaHash,
                          'hash_time' => time());

            Session::instance()->set('type_results_all', serialize($data));
        }

        $typeResult = $data['results'];
        return $typeResult;
    }

    /**
     * @param $articleType
     * @param array $criteria
     * @param array $filter
     * @return \Doctrine\ORM\QueryBuilder
     */
    private static function getResultQuery($articleType, array $criteria = array(), array $filter = array()) {
        $query = Doctrine::instance()->createQueryBuilder();

        $searchStr = array();

        if($articleType == 'search') {
            $query->from("Model_Article", 'a');
        } else {
            $query->from("Model_Article_" . ucfirst($articleType), 'a');
        }

        if(isset($filter['search'])) {
            self::$_solrArticles = array();

            $solrQuery = array('fl' => '*,score', 'sort' => 'score desc', 'rows' => 1000, 'hl' => 'true', 'hl.fl' => 'title,description', 'hl.fragsize' => '130','q' => 'title:*' . $filter['search'] . '*^2 OR description:*' . $filter['search'] . '*');

            $result = Helper_Search::search($solrQuery);

            foreach($result->highlighting as $id => $fields) {
                $title = isset($fields->title) ? $fields->title[0] : null;
                $desc = isset($fields->description) ? $fields->description[0] : null;
                self::$_solrArticles[$id] = array('title' => $title, 'description' => $desc);
            }

            $types = Helper_Article::getTypes(true);
            $activeModules = array();
      		foreach($types as $type => $name) {
                if(Helper_Module::isActive(strtolower($type))) {
                    $activeModules[] = "a INSTANCE OF Model_Article_" . ucfirst($type);

                }
      		}

            if(count($activeModules)) {
                $query->andWhere(implode(' OR ', $activeModules));
            }
        }

        if(count($criteria)) {
            $criterion = array();

            foreach($criteria as $c) {
                $opt = Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneBy(array('id' => $c, 'deleted' => 0));
                if($opt) {
                    if($opt->parentOption) {
                        $criterionId = $opt->parentOption->criterion->id;
                    } else {
                        $criterionId = $opt->criterion->id;
                    }
                    //echo $opt->criterion->title;
                    if(!isset($criterion[$criterionId])) {
                        $criterion[$criterionId] = array();
                    }
                    $criterion[$criterionId][$c] = $opt;
                }
            }

            $joinsSet = array();

            foreach($criterion as $critId => $criteriaOpts) {
                $where = array();
                $or = array();
                foreach($criteriaOpts as $c => $opt) {
                    $criterion = $opt->criterion;
                    if(!$criterion) {
                        $criterion = $opt->parentOption->criterion;
                    }
                    if($criterion->type == 'select' || $criterion->type == 'radio' || $criterion->type == 'resource') {
                        $defaultOpt = '0';
                        foreach($criterion->options as $cr) {
                            if($cr->default && $cr->deleted == false) {
                                $defaultOpt = $cr->id;
                            }
                        }

                        if($criterion->type == 'resource') {

                            if($c != $defaultOpt) {
                                $resources = array();
                                foreach($criterion->options as $o) {
                                    if($opt->orderindex > $o->orderindex && $o->default == false && $o->deleted == false) {
                                        $resources[] = $o->id;
                                    }
                                }

                                $resources[] = $c;

                                if(count($resources)+1 != count($criterion->options)) {
                                    if(!in_array($critId, $joinsSet)) {
                                        $query->join('a.criteria', "c_{$critId}");
                                        $joinsSet[] = $critId;
                                    }

                                    $query->andWhere("c_{$critId}.id IN (" . implode(',', $resources) . ", {$defaultOpt})");
                                }
                            }
                        } else {
                            if($c != $defaultOpt) {

                                if(!in_array($critId, $joinsSet)) {
                                    $query->join('a.criteria', "c_{$critId}");
                                    $joinsSet[] = $critId;
                                }

                                if($opt->childOptions && count($opt->childOptions) > 0) {
                                    $childs = array();
                                    foreach($opt->childOptions as $child) {
                                        $childs[] = $child->id;
                                    }
                                    $query->andWhere("c_{$critId}.id IN ({$c}, {$defaultOpt}, " . implode(', ', $childs) . ")");
                                } else {
                                    $query->andWhere("c_{$critId}.id IN ({$c}, {$defaultOpt})");
                                }
                            }
                        }
                    } else {
                        if($criterion->filterTypeOr == true) {

                            if(!in_array($critId, $joinsSet)) {
                                $query->join('a.criteria', "c_{$critId}");
                                $joinsSet[] = $critId;
                            }

                            $or[] = "c_{$critId}.id = {$c}";
                        } else {
                            $query->join('a.criteria', "c_{$critId}_{$c}");
                            $where[] = "c_{$critId}_{$c}.id = {$c}";
                            $joinsSet[] = $critId;
                        }
                    }

                }

                if(count($where)) {
                    $query->andWhere(implode(' AND ', $where));
                } elseif(count($or)) {
                    $query->andWhere(implode(' OR ', $or));
                }

            }
        }

        if(count($filter)) {
            if(count(self::$_solrArticles)) {
                $query->andWhere("a.id IN (:solrIds)");
                $query->setParameter("solrIds", array_keys(self::$_solrArticles));
            } else {
                $query->andWhere("a.id = ''");
            }
        }

        $query->andWhere("a.active=1");
        $query->andWhere("a.deleted!=1");

        return $query;
    }

    /**
     * @param array $types
     * @param array $criteria
     * @return int
     */
    public static function getResultCount(array $types = array(), array $criteria = array()) {
        if(count($types) == 0) {
            return 0;
        }

        $data = 0;
        foreach($types as $type) {
            $query = self::getResultQuery($type, $criteria);
            $data =+ count($query->select('COUNT (a.id)')->groupBy("a.id")->getQuery()->useResultCache(true, RESULT_CACHE_LIFETIME)->getResult());
        }

        return $data;
    }

    /**
     * @param $type
     * @return bool
     */
    public static function getDefaultSort($type) {
        $config = Helper_Message::loadFile(APPPATH . 'config/base.config');

        return isset($config["sort.{$type}"]) ? $config["sort.{$type}"] : false;
    }

    /**
     * @param $type
     * @param array $criteria
     * @param string $sort
     * @param array $filter
     * @param bool $archivArticles
     * @return array
     */
    public static function getResult($type, array $criteria = array(), $sort = 'title', array $filter = array(), $archivArticles = false) {

        $fields = self::getArticleConfig();

        if(isset($fields[$type]['sort']) && !in_array($sort, $fields[$type]['sort'])) {
            $sort = $fields[$type]['sort'][0];
        }

        if($type != 'search') {
            $criteria = Helper_Article::removeDisabledCriteria($criteria, $type);
        }

        $query = self::getResultQuery($type, $criteria, $filter);

        if($type == 'news' && $archivArticles == false) {
            $dt = new DateTime('now');
            $query->andWhere("a.date >= :date");
            $query->setParameter("date", $dt->modify(self::$_newsArchivTime));
        } elseif($type == 'news' && $archivArticles == true) {
            $dt = new DateTime('now');
            $query->andWhere("a.date < :date");
            $query->setParameter("date", $dt->modify(self::$_newsArchivTime));
        }

        if($type == 'event' && $archivArticles == false) {
            // show only events greater than the current date
            $dt = new DateTime('now');
            $query->andWhere("a.end_date >= :end_date");
            $query->setParameter("end_date", $dt);
        } elseif($type == 'event' && $archivArticles == true) {
            // show only events greater than the current date
            $dt = new DateTime('now');
            $query->andWhere("a.end_date < :end_date");
            $query->setParameter("end_date", $dt);
        }

        $org_sort = $sort;

        if(count(self::$_solrArticles) && $sort == 'relevance') {
            $query->orderBy("field");
        } else {
            $direction = "ASC";
            if($sort == "created") {
                $direction = "DESC";
            }
            if($sort == "date" || $sort == "year") {
                $direction = "DESC";
            }

            if($type == 'expert' && $sort == 'lastname') {
                $query->orderBy("a.lastname", "asc");
                $query->addOrderBy("a.institution", "asc");
            }
            elseif($sort == "study_start") {
                $query->orderBy("a.start_year", "DESC");
                $query->addOrderBy("a.start_month", "DESC");
            } elseif($sort) {
                if(count(self::$_solrArticles) && $type == 'search' && !in_array($sort, array('title', 'created', 'relevance'))) {
                    // sort by relevance
                } elseif($type != 'search') {
                    $query->orderBy("a." . $sort, $direction);
                }
            }
        }

        if(count(self::$_solrArticles)) {
            $query->select("a, FIELD(a.id, " . implode(", ", array_keys(self::$_solrArticles)) . ") as HIDDEN field");
        } else {
            $query->select("a");
        }

        $result = $query->groupBy('a.id')
                        ->getQuery()
                        ->useResultCache(true, RESULT_CACHE_LIFETIME)
                        ->getResult();

        if(!count($result)) return array();

        $sortedResult =  self::sortResult($result, $org_sort);

        return $sortedResult;
    }

    /**
     * @param $id
     * @param $field
     * @return bool
     */
    static function getHightlightedText($id, $field) {
        if(isset(self::$_solrArticles[$id]) && self::$_solrArticles[$id][$field]) {
            return self::$_solrArticles[$id][$field];
        }
        return false;
    }

    /**
     * @param $result
     * @param string $sort
     * @return array
     */
    public static function sortResult($result, $sort = '') {
        $ret = array();

        foreach($result as $r) {
            $type = $r->type();

            if(!isset($ret[$type])) {
                $ret[$type] = (object) array(
                    'type' => $type,
                    'title' => Helper_Message::get("article_config.{$type}.title.plurality"),
                    'articles' => array(),
                    );
            }
            $ret[$type]->articles[$r->id] = $r;
        }

        if($sort == "study_start" && isset($ret['study'])) {
            usort($ret['study']->articles, function($a, $b) {
                return floatval($a->start_year . str_pad($a->start_month, 2, '0', STR_PAD_LEFT)) < floatval($b->start_year . str_pad($b->start_month, 2, '0', STR_PAD_LEFT)) ? 1 : 0;
            });
        }

        // Sort the result by types (as defined in Model_Article)
        // so the category order won't change on /article/index
        $sortedResult = array();
        foreach(Model_Article::$articleTypes as $t) {
            if(isset($ret[$t])) {
                $sortedResult[(string)$t] = $ret[$t];
            }
        }

        return $sortedResult;
    }

    /**
     * @param $type
     * @param array $excludeIds
     * @return array
     */
    public static function getArticles($type, array $excludeIds = array()) {
        return $query = Doctrine::instance()->createQueryBuilder()
        ->select('a')
        ->from('Model_Article', 'a')
        ->where("a INSTANCE OF Model_Article_" . ucfirst($type))
        ->andWhere('a.active = 1')
        ->andWhere('a.deleted = 0')
        ->orderBy('a.title', 'ASC')
        ->getQuery()
        ->getResult();
    }

    /**
     * @param $type
     * @return array
     */
    public static function getAllArticles($type) {
        return $query = Doctrine::instance()->createQueryBuilder()
        ->select('a')
        ->from('Model_Article', 'a')
        ->where("a INSTANCE OF Model_Article_" . ucfirst($type))
        ->andWhere('a.deleted = 0')
        ->orderBy('a.title', 'ASC')
        ->getQuery()
        ->getResult();
    }

    /**
     * @param Validation $validation
     * @param $field
     * @param $other_field
     */
    public static function validate_depends(Validation $validation, $field, $other_field) {
        if(!$validation[$field] AND $validation[$other_field]) {
            $validation->error($field, 'validate_depends');
        }
    }

    /**
     * @param Validation $validation
     * @param $field
     * @param $other_field
     */
    public static function validate_depends_empty(Validation $validation, $field, $other_field) {
        if(!$validation[$field] && !$validation[$other_field]) {
            $validation->error($field, 'validate_depends_empty');
        }
    }

    /**
     * @param Validation $validation
     * @param $field
     * @param $other_field
     */
    public static function validate_datetime(Validation $validation, $field, $other_field) {
        $datetime = strtotime($validation[$field]);
        $datetime_other = strtotime($validation[$other_field]);

        if($datetime == false || $datetime_other < $datetime) {
            $validation->error($field, 'validate_datetime');
        }
    }

    /**
     * @param Validation $validation
     * @param $end_month
     * @param $end_year
     * @param $start_month
     * @param $start_year
     * @return bool
     */
    public static function validate_date_before(Validation $validation, $end_month, $end_year, $start_month, $start_year) {
        if($validation[$end_month] ==0 ) return true;
        if($validation[$start_month] AND $validation[$start_year]) {
            $end = strtotime("00:00:00 00.{$validation[$end_month]}.{$validation[$end_year]}");
            $start = strtotime("00:00:00 00.{$validation[$start_month]}.{$validation[$start_year]}");
            if($start > $end) {
                $validation->error($end_month, 'validate_date_before');
            }
        }
    }

    /**
     * @return array
     */
    public static function getParticipationList() {
        return array_map(
            function($key) {
                return Helper_Message::get('participation.' . $key);
            }
            , Kohana::$config->load('project.participation')
            );
    }

    /**
     * @param $criterion
     * @param $articleType
     * @return bool
     */
    public static function isHiddenCriteria($criterion,$articleType) {
        if($criterion->discriminator === null) {
            return false;
        }
        $config = Yaml::loadFile(APPPATH . 'data/articleconfig.yaml');
        $hidden = array();

        foreach($config as $type => $article) {
            if($type == $articleType) {
                foreach($article['form'] as $areaId => $area) {
                    if(isset($area['hiddencriteria'])) {
                        foreach($area['hiddencriteria'] as $field) {
                            $hidden[] = $field;
                        }
                    }
                }
            }
        }
        return in_array($criterion->discriminator, $hidden);
    }

    /**
     * @param $type
     * @param $articleData
     * @return Model_Article_Event|Model_Article_Expert|Model_Article_Instrument|Model_Article_Method|Model_Article_News|Model_Article_Qa|Model_Article_Study
     * @throws Kohana_Exception
     */
    public static function createNewTransientArticleOfTypeWithData($type,$articleData) {
        $article = null;

        switch ($type) {
            case Model_Article::CaseStudy:
                $article = new Model_Article_Study();
                break;
            case Model_Article::ParticipationInstrument:
                $article = new Model_Article_Instrument();
                break;
            case Model_Article::ParticipationMethod:
                $article = new Model_Article_Method();
                break;
            case Model_Article::QuestionAndAnswer:
                $article = new Model_Article_Qa();
                break;
            case Model_Article::Expert:
                $article = new Model_Article_Expert();
                break;
            case Model_Article::News:
                $article = new Model_Article_News();
                break;
            case Model_Article::Event:
                $article = new Model_Article_Event();
                break;
            default:
                throw new Kohana_Exception('Invalid article type given in request.', null, 400);
                break;
        }

        // exclude these fields from automatic assignment inside the loop
        $doNotHandleInLoop = array("criteriaOptions","linked_articles","active");

        // these fields contain timestamps and need to be converted to a date string
        $dateFields = array("start_date","end_date","date","deadline");
        $timezone = new DateTimeZone('CEST');

        foreach ($articleData as $name => $value) {
            if(in_array($name, $doNotHandleInLoop)) {
                // guard block:
                // these fields will not be handled automatically inside this loop
                continue;
            }

            // special treatment for external links
            if($name === "external_links") {
                $tmp = array();
                foreach($value as $link) {
                    $tmp[] = array("url" => $link);
                }
                $value = $tmp;
            }

            if(in_array($name, $dateFields) && $value !== "") {
                $value = DateTime::createFromFormat('Y-d-m H:i:s', date('Y-d-m H:i:s', substr((string)$value, 0, 10)), new DateTimeZone('Europe/London'));
            }

            if($value !== "") {
                $article->$name = $value;
            }
        }

        $article->criteria = $articleData->criteriaOptions;
        $article->linked_articles = $articleData->linked_articles;

        // Handle images
        $imagearray = array();
        foreach ($articleData->images as $img) {
            $ext = mb_substr($img->name, mb_strrpos($img->name, '.'));
            $imagename = Helper_Utility::slug($article->title) . $ext;
            $imagefile = Helper_Article::addImageWithNameForArticleFromBase64Encoding($imagename, $img->content);
            $imagearray[] = array("id" => $imagefile->id, "description" => $img->name);
        }
        $article->images = $imagearray;

        return $article;
    }

    /**
     * @param $targetDir
     * @param $filename
     * @return string
     */
    public static function getUniqueFilename($targetDir,$filename) {
        if(file_exists($targetDir . DIRECTORY_SEPARATOR . $filename)) {
            $ext = mb_strrpos($filename, '.');
            $filename_a = mb_substr($filename, 0, $ext);
            $filename_b = mb_substr($filename, $ext);

            $count = 1;
            while (file_exists($targetDir . DIRECTORY_SEPARATOR . $filename_a . '_' . $count . $filename_b)) {
                $count++;
            }

            $filename = $filename_a . '_' . $count . $filename_b;
        }
        return $filename;
    }

    /**
     * @param $name
     * @param $base64EncodedImage
     * @return Model_File
     * @throws Kohana_Exception
     */
    public static function addImageWithNameForArticleFromBase64Encoding($name, $base64EncodedImage) {
        $image = base64_decode($base64EncodedImage);
        if(!$image) {
            throw new Kohana_Exception('Base64-encoded image could not be decoded.', null, 400);
        }
        $targetDir = Kohana::$config->load('project.upload_dir');
        $filename = Helper_Article::getUniqueFilename($targetDir,$name);
        $target = $targetDir . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($target,$image);

        $file = new Model_File($target);
        Doctrine::instance()->persist($file);
        Doctrine::instance()->flush($file);

        return $file;
    }

    /**
     * @param $favorites
     * @return array
     */
    static function getArticleGroups($favorites) {
        $types = array();

        foreach($favorites as $type => $fav) {
            if(get_class($fav) != 'stdClass') {
                $type = $fav->type();
                $types[$fav->type()] = Helper_Message::get("article_config.{$type}.title.plurality");
            } else {
                $types[$type] = Helper_Message::get("article_config.{$type}.title.plurality");
            }
        }
        return $types;
    }
}