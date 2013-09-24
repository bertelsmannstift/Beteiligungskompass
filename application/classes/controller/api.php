<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Api extends Controller_Base {

    public function action_index() {

    }

    private function checkParameterAndReturnUserForProtectedApiMethod($requiredParams = array()) {
        if(empty($_GET['token'])) {
            throw new Kohana_Exception('Missing API token', null, 403);
        }
        foreach ($requiredParams as $p) {
            if(empty($_GET[$p])) {
                throw new Kohana_Exception('Missing parameter ' . $p, null, 400);
            }
        }

        if(!$user = Helper_User::getUserByToken($_GET["token"])) {
            throw new Kohana_Exception('Invalid API token', null, 403);
        }
        return $user;
    }

    /**
     * Get a message string
     *
     * Parameter: api_key
     * http://example.org/api/get_static_page?api_key=xyz
     *
     * @return bool
     */
    function action_get_string() {
        if (!is_array($_GET['param'])) {
            return false;
        }

        $code = $_GET['param'][0];
        unset($_GET['param'][0]);

        $params = $_GET['param'];
        $this->jsonResponse(array('response' => Helper_Message::get($code, $params)));
    }

    /**
     * Add a new article
     *
     * Parameter: api_key
     * http://example.org/api/add_article?api_key=xyz
     */
    function action_add_article() {
        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new Kohana_Exception(sprintf('HTTP method %s not supported',$_SERVER["REQUEST_METHOD"]), null, 400);
        }
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array());
        $jsonArticle = json_decode(file_get_contents('php://input'))->article;
        if(!$jsonArticle) {
            throw new Kohana_Exception('No POST data found in request', null, 400);
        }
        $success = false;
        $articleId = null;
        try {
            $article = Helper_Article::createNewTransientArticleOfTypeWithData($jsonArticle->type,$jsonArticle);
            $article->user = $user;
            Doctrine::instance()->persist($article);
            Doctrine::instance()->flush();
            $success = true;
            $articleId = $article->id;
        } catch(Exception $e) {
            // Do nothing... false is returned
        }

        $imagearray = array();
        foreach ($article->images as $image) {
            $imagearray[] = $image["id"];
        }
        $this->jsonResponse(array('response' => array(
            "success" => $success,
            "article_id" => $articleId,
            "images" => $imagearray)));
    }

    /**
     * Get website terms
     *
     * Parameter: api_key
     * http://example.org/api/get_terms?api_key=xyz
     */
    function action_get_terms() {
        $privacy = Doctrine::instance()->getRepository('Model_Page')->findOneByType("privacy");
        if(!$privacy) {
            throw new Kohana_Exception('Privacy page could not be found', null, 404);
        }
        $this->jsonResponse(array('response' => array("pagecontent" => $privacy->content)));
    }

    /**
     * Get static page contents
     *
     * Parameter: api_key
     * http://example.org/api/get_static_page?api_key=xyz
     */
    function action_get_static_page() {
        $page = Doctrine::instance()->getRepository('Model_Page')->findOneByType('about');

        if($page) {
            $replace = function($matches){
                  if(stripos($matches[0], 'data:') === false) {
                      $src = '.' . str_ireplace('src="', '', $matches[0]);
                      if(is_file($src)) {
                          $fileContent = file_get_contents($src);
                          $ext = pathinfo($src, PATHINFO_EXTENSION);

                          return 'src="data:image/' . $ext . ';base64,' . base64_encode($fileContent);
                      }
                      return '';
                  }
                  return $matches[0];
              };

            $fieldsWYSIWYG = array();
            foreach($page->fieldsWYSIWYG as $key => $field) {
                $field = preg_replace_callback('#(src)=("|\')[^"\'>]+#is', $replace, $field);
                $fieldsWYSIWYG[$key] = $field;
            }

            $content = preg_replace_callback('#(src)=("|\')[^"\'>]+#is', $replace, $page->content);

            $this->jsonResponse(array('response' =>
                array(
                    "title" => $page->title,
                    "shortTitle" => $page->shortTitle,
                    "showInMenu" => $page->showInMenu,
                    "active" => $page->active,
                    "fields" => $page->fields,
                    "fieldsWYSIWYG" => $fieldsWYSIWYG,
                    "content" => $content,
                    "type" => $page->type,
                )
            ));
            return;
        }
        $this->jsonResponse(array('response' => array()));
    }

    /**
     * Get favorite articles
     *
     * Parameter: api_key, token
     * http://example.org/api/get_favorites?api_key=xyz&token=abc
     */
    function action_get_favorites() {
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array());

        $favIds = array();
        foreach($user->favorites as $favorite) {
            $favIds[] = $favorite->article->id;
        }
        $favgroups = array();
        foreach($user->favoritegroups as $favgroup) {
            $favgroups[$favgroup->name] = array();
            $favgroups[$favgroup->name]["id"] = $favgroup->id;
            $favgroups[$favgroup->name]["articles"] = array();
            $favgroups[$favgroup->name]["sharelink"] = Url::get(array(
                                        'route' => 'default',
                                        'controller' => 'favorites',
                                        'action' => 'showgroup',
                                        'id' => $favgroup->id,
                                        'param' => $favgroup->getSharehash()
                                    ));

            foreach($favgroup->favorites as $favorite) {
                $favgroups[$favgroup->name]["articles"][] = $favorite->article->id;
            }
        }
        $favoriteInfo = array(
            "allFavorites" => $favIds,
            "favoriteGroups" => (object)$favgroups
        );
        $this->jsonResponse(array('response' => array("favoriteInfo" => $favoriteInfo)));
    }

    /**
     * Add favorite article
     *
     * Parameter: api_key, token, article_id
     * http://example.org/api/add_favorite?api_key=xyz&token=abc&article_id=1
     */
    function action_add_favorite() {
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array('article_id'));
        $success = Helper_User::addArticleToUsersFavorites($_GET['article_id'],$user);
        $this->jsonResponse(array('response' => array("success" => $success)));
    }

    /**
     * Remove favorite article
     *
     * Parameter: api_key, token, article_id
     * http://example.org/api/remove_favorite?api_key=xyz&token=abc&article_id=1
     */
    function action_remove_favorite() {
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array('article_id'));
        $success = Helper_User::removeArticleFromUsersFavorites($_GET['article_id'],$user);
        $this->jsonResponse(array('response' => array("success" => $success)));
    }

    /**
     * Add a favorite group
     *
     * Parameter: api_key, token, title
     * http://example.org/api/add_favorite_group?api_key=xyz&token=abc&title=Hallo%20Welt
     */
    function action_add_favorite_group() {
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array('title'));
        $group = Helper_User::addFavoriteGroupForUser($_GET['title'],$user);
        if(!$group) {
            $this->jsonResponse(array('response' => array("success" => false)));
        } else {
            $this->jsonResponse(array('response' => array("success" => true, "group_id" => $group->id)));
        }
    }

    /**
     * Remove a favorite group
     *
     * Parameter: api_key, token, group_id
     * http://example.org/api/remove_favorite_group?api_key=xyz&token=abc&group_id=1
     */
    function action_remove_favorite_group() {
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array('group_id'));
        $success = Helper_User::removeFavoriteGroupForUser($_GET['group_id'],$user);
        $this->jsonResponse(array('response' => array("success" => $success)));
    }

    /**
     * Add a article to a favorite group
     *
     * Parameter: api_key, token, group_id, article_id
     * http://example.org/api/add_article_to_favorite_group?api_key=xyz&token=abc&group_id=1&article_id=1
     */
    function action_add_article_to_favorite_group() {
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array('group_id','article_id'));
        $success = Helper_User::addArticleToFavoriteGroupForUser($_GET['article_id'],$_GET['group_id'],$user);
        $this->jsonResponse(array('response' => array("success" => $success)));
    }

    /**
     * Remove a article from a favorite group
     *
     * Parameter: api_key, token, group_id, article_id
     * http://example.org/api/remove_article_from_favorite_group?api_key=xyz&token=abc&group_id=1&article_id=1
     */
    function action_remove_article_from_favorite_group() {
        $user = $this->checkParameterAndReturnUserForProtectedApiMethod(array('group_id','article_id'));
        $success = Helper_User::removeArticleFromFavoriteGroupForUser($_GET['article_id'],$_GET['group_id'],$user);
        $this->jsonResponse(array('response' => array("success" => $success)));
    }

    /**
     * User login
     *
     * Parameter: api_key, email, password
     * http://example.org/api/login?api_key=xyz&email=abc@example.org&password=test1234
     */
    function action_login() {
        if(empty($_GET['email']) || empty($_GET['password'])) {
            throw new Kohana_Exception('Missing parameters', null, 400);
        }
        $usertoken = Helper_User::getUserToken($_GET['email'],$_GET['password']);
        if(empty($usertoken)) {
            throw new Kohana_Exception('User token could not be generated', null, 500);
        }
        $user = Helper_User::getUserByToken($usertoken);
        $this->jsonResponse(array('response' => array("token" => $usertoken,"userid" => $user->id)));
    }


    /**
     * Get mobile messages
     *
     * Parameter: api_key
     * http://example.org/api/get_strings?api_key=xyz
     */
    function action_get_strings() {
        $this->jsonResponse(array('response' => Kohana::$config->load('project.mobile_messages')));
    }

    /**
     * Get module active state
     *
     * Parameter: api_key
     * http://example.org/api/is_module_active?api_key=xyz
     */
    function action_is_module_active() {
        if (!isset($_GET['param'])) {
            return false;
        }

        $this->jsonResponse(array('response' => Helper_Module::isActive($_GET['param'])));
    }

    /**
     * Get active modules
     *
     * Parameter: api_key
     * http://example.org/api/get_module_state?api_key=xyz
     */
    function action_get_module_state() {
        $this->jsonResponse(array('response' =>
            array(
            'show_study' => Helper_Module::isActive('study'),
            'show_method' => Helper_Module::isActive('method'),
            'show_qa' => Helper_Module::isActive('qa'),
            'show_instrument' => Helper_Module::isActive('instrument'),
            'show_event' => Helper_Module::isActive('event'),
            'show_expert' => Helper_Module::isActive('expert'),
            'show_news' => Helper_Module::isActive('news'),
            'show_planning' => Helper_Module::isActive('planning'),
            'show_about' => Helper_Module::isActive('about'),
        )));
    }

    /**
     * Get the base configurations
     *
     * Parameter: api_key
     * http://example.org/api/get_base_config?api_key=xyz
     */
    function action_get_base_config() {
        $this->jsonResponse(array('response' => Helper_Message::loadFile(APPPATH . 'config/base.config')));
    }

	/**
     * Update the cached mobile files
	 * Start cron "php index.php --uri=api/update_export"
	*/
	function action_update_export() {
		$this->create_export(true);
		$this->jsonResponse(array('response' => array("success" => true)));
	}

	/**
     * Update cached mobile thumbnails
	 * Start cron "php index.php --uri=api/update_thumbs"
	 * Run all 30 Minutes!
	*/
	function action_update_thumbs() {
		$this->action_get_thumbnails(true);
		$this->jsonResponse(array('response' => array("success" => true)));
	}

    /**
     * Get the file hashes to determine a update
     *
     * Parameter: api_key
     * http://example.org/api/get_file_hashes?api_key=xyz
     */
    function action_get_file_hashes() {
        $sqldb = Kohana::$config->load('project.file_dir') . DIRECTORY_SEPARATOR . 'api_temp' . DIRECTORY_SEPARATOR . 'data.sqlite';
        $zipFile = Kohana::$config->load('project.file_dir') . DIRECTORY_SEPARATOR . 'api_temp' . DIRECTORY_SEPARATOR . 'thumb.zip';
        $fileHashes = array('data.sqlite' => '', 'thumb.zip' => '');
        if(is_file($sqldb)) {
            $fileHashes['data.sqlite'] = md5_file($sqldb);
        }
        if(is_file($zipFile)) {
            $fileHashes['thumb.zip'] = md5_file($zipFile);
        }
        return $this->jsonResponse(array('response' => $fileHashes));
    }

	/**
	 * Return sqlite export file
	 * @param bool $force_update
	 * @return string
	 */
	private function create_export($force_update = false) {

		$sqldb = Kohana::$config->load('project.file_dir') . DIRECTORY_SEPARATOR . 'api_temp' . DIRECTORY_SEPARATOR . 'data.sqlite';
		// nach 20 Min. wird die Datei neu erstellt
        if(is_file($sqldb) && (time()-filemtime($sqldb)) <= 1200 && $force_update == false) {
			return $sqldb;
		}

		$createTables = array();
        $pdo = Doctrine::instance()->getConnection();
        $inserts = array();
        $userId = isset($_GET['id']) ? intval($_GET['id']) : false;

        $userFields = array('favoritegroup' => 'user_id',
            'favorite_articles' => 'user_id');

        foreach ($pdo->query('SHOW TABLES') as $table) {
            $columns = array();
            $tableName = $table[0];
            $resultColumns = $pdo->query("SHOW FULL COLUMNS FROM {$tableName}");
            $resultColumns->setFetchMode(PDO::FETCH_ASSOC);
            $resultColumnsArray = array();

            foreach ($resultColumns as $k => $c) {
                $resultColumnsArray[$c['Field']] = $c;
            }

            if ($tableName == 'articles_options') {
                $rowResult = $pdo->query("SELECT o.* FROM {$tableName} o JOIN articles a ON a.id = o.model_article_id WHERE a.deleted = 0 AND a.active = 1");
            } elseif ($tableName == 'article_links') {
                $rowResult = $pdo->query("SELECT o.* FROM {$tableName} o JOIN articles a ON a.id = o.article_id JOIN articles a2 ON a2.id = o.article_linked_id WHERE a.deleted = 0 AND a.active = 1 AND a2.deleted = 0 AND a2.active = 1");
            } else {
                $rowResult = $pdo->query("SELECT * FROM {$tableName}");
            }

            $rowResult->setFetchMode(PDO::FETCH_ASSOC);

            foreach ($rowResult as $rowKey => $row) {

                $article = null;
                $rowId = null;

                if($tableName == 'articles') {
                    $article = Doctrine::instance()->getRepository('Model_Article')->findOneById($row['id']);
                }

                foreach ($row as $field => &$val) {

                    $cinfo = $this->extractColumnInfo($field, $resultColumnsArray);
                    $defaultValue = $cinfo['Default'] == null && $cinfo['Null'] != 'NO' ? 'NULL' : "''";

                    // clean sensitive fields & deleted rows
                    if (($field == 'deleted' && $val == '1') || ($field == 'is_deleted' && $val == '1') || ($tableName == 'articles' && $field == 'active' && $val != '1')) {
                        continue 2;
                    } elseif ($field == 'password' || $field == 'salt' || $field == 'hash' || ($field == 'email' && $tableName != 'articles')) {
                        $val = '';
                    } elseif ($field == 'is_admin' || $field == 'is_editor') {
                        $val = '0';
                    } elseif(($cinfo['Type'] == 'datetime' || $cinfo['Type'] == 'date') && $val == '') {
                        $val = 'NULL';
                    }

                    // get only data from the user
                    if (isset($userFields[$tableName]) && $userFields[$tableName] === $field) {
                        if (!($userId !== false && $userId == $val)) {
                            continue 2;
                        }
                    }

                    if($tableName == 'partnerlinks' && $field == 'content') {
                        $replace = function($matches){
                            if(stripos($matches[0], 'data:') === false) {
                                $src = '.' . str_ireplace('src="', '', $matches[0]);
                                if(is_file($src)) {
                                    $fileContent = file_get_contents($src);
                                    $ext = pathinfo($src, PATHINFO_EXTENSION);

                                    return 'src="data:image/' . $ext . ';base64,' . base64_encode($fileContent);
                                }
                                return '';
                            }
                            return $matches[0];
                        };
                        $val = preg_replace_callback('#(src)=("|\')[^"\'>]+#is', $replace, $val);
                    }

                    if (isset($cinfo['Comment']) && $cinfo['Comment'] == '(DC2Type:array)') {
                        if ($val !== null) {
                            $val = unserialize($val);

                            if ($field == 'videos') {

                                $links = array();
                                foreach ($val as $link) {
                                    if (is_array($link)) {
                                        $links[] = $link;
                                    } else {
                                        $links[] = array('url' => $link, 'featured' => false, 'description' => '');
                                    }
                                }
                                $val = $links;
                            } elseif ($field == 'external_links') {

                                $links = array();
                                foreach ($val as $link) {
                                    if (is_array($link)) {
                                        $links[] = $link;
                                    } else {
                                        $links[] = array('url' => $link, 'show_link' => true);
                                    }
                                }
                                $val = $links;
                            }
                            elseif ($field == 'images') {
                                $links = array();
                                if(is_array($val)) {
                                    foreach ($val as $link) {
                                        $links[] = $link;
                                    }
                                } else {
                                    $links[] = $val;
                                }

                                $val = $links;
                            }

                            if (count($val) === 0) {
                                $val = $defaultValue;
                            } elseif (is_array($val)) {
                                $val = json_encode($val);
                            }
                        }
                    }

                    $val = $this->escapeSQLite($val);

                    if ($val != $defaultValue && $val !== null) {
                        $val = "'{$val}'";
                    } else {
                        $val = $defaultValue;
                    }
                }


                if($tableName == 'articles') {
                    $resultColumnsArray['countrycode'] = $cinfo;
                    $resultColumnsArray['countrycode']['Field'] = 'countrycode';
                    $resultColumnsArray['countrycode']['Type'] = 'longtext';
                    $countyname = $article->getCountry();
                    $countyCode = Helper_Country::getCountryCodeByName($countyname);
                    $str = $countyCode ? $this->escapeSQLite($countyCode) : '';
                    $row[] = "'{$str}'";

                    $resultColumnsArray['listdescription'] = $cinfo;
                    $resultColumnsArray['listdescription']['Field'] = 'listdescription';
                    $resultColumnsArray['listdescription']['Type'] = 'longtext';
                    $str = $this->escapeSQLite($article->description());
                    $row[] = "'{$str}'";

                    $resultColumnsArray['listdescription_plaintext'] = $cinfo;
                    $resultColumnsArray['listdescription_plaintext']['Field'] = 'listdescription_plaintext';
                    $resultColumnsArray['listdescription_plaintext']['Type'] = 'longtext';
                    $str = $this->escapeSQLite(str_replace(array("\n", "\t"), '', trim(html_entity_decode(strip_tags($article->description()),ENT_COMPAT,'utf-8'))));
                    $row[] = "'{$str}'";

                    $rowId = $row['id'];
                }

                if($rowId) {
                    $inserts[$rowId] = "REPLACE INTO {$tableName} (`" . implode('`,`', array_keys($resultColumnsArray)) . "`) VALUES (" . implode(",", $row) . ");";
                } else {
                    $inserts[] = "REPLACE INTO {$tableName} (`" . implode('`,`', array_keys($resultColumnsArray)) . "`) VALUES (" . implode(",", $row) . ");";
                }
            }

            foreach ($resultColumnsArray as $c) {
                $columns[] = "`{$c['Field']}` {$c['Type']}";
            }

            $createTables[] = "CREATE TABLE `{$tableName}` (" . implode(', ', $columns) . ");";
        }

        $content = "BEGIN TRANSACTION;" . implode("\n", $createTables) . implode("\n", $inserts) . "COMMIT;";

        $sql = tempnam("/tmp", "FOO");
        file_put_contents($sql, $content);
        if(is_file($sqldb)) {
            @unlink($sqldb);
        }
        shell_exec("/usr/bin/sqlite3 {$sqldb} < {$sql} 2>&1");
		@unlink($sql);
		return $sqldb;
	}

    /**
     * Send sql lite file
     *
     * Parameter: api_key
     * http://example.org/api/export?api_key=xyz
     */
    function action_export() {
	    $sqldb = $this->create_export();
        $this->response->send_file($sqldb, "data.sqlite");
    }

	/**
	 * @param $val
	 * @return string
	 */
	private function escapeSQLite($val) {
        if (function_exists('sqlite_escape_string')) {
            $val = sqlite_escape_string($val);
        } else {
            $val = SQLite3::escapeString($val);
        }
        return $val;
    }

	/**
	 * @param $field
	 * @param $resultColumns
	 * @return bool
	 */
	private function extractColumnInfo($field, $resultColumns) {
        foreach ($resultColumns as $c) {
            if ($field == $c['Field']) {
                return $c;
            }
        }
        return false;
    }

    /**
     * Get server timezone
     *
     * Parameter: api_key
     * http://example.org/api/get_timezone?api_key=xyz
     */
    function action_get_timezone() {
        $this->jsonResponse(array('response' => date_default_timezone_get()));
    }

    /**
     * Send thumbnails
     *
     * Parameter: api_key
     * http://example.org/api/get_thumbnails?api_key=xyz
     *
     * @param bool $force_update
     */
	function action_get_thumbnails($force_update = false) {
        $size = '200x';
        $zipFile = Kohana::$config->load('project.file_dir') . DIRECTORY_SEPARATOR . 'api_temp' . DIRECTORY_SEPARATOR . 'thumb.zip';

        if(!is_file($zipFile) || $force_update === true) {
            if(is_file($zipFile)) {
                unlink($zipFile);
            }
            $this->create_thumbs($size);
            $tempdir = $this->tempdir();
            $videoTemp = Kohana::$config->load('project.file_dir') . DIRECTORY_SEPARATOR . 'api_temp' . DIRECTORY_SEPARATOR . 'video';
            $this->create_video_thumbs($videoTemp);

            // copy article thumbs to temp dir
            $path = Kohana::$config->load('project.public_preview_path') . DIRECTORY_SEPARATOR . $size;
            shell_exec("cp -R {$path} {$tempdir} 2>&1");
            shell_exec("cp -R {$videoTemp} {$tempdir} 2>&1");

            // zip the temp dir
            $zip = tempnam("/tmp", "ZIP");
            $dir = basename($tempdir);
            chdir($tempdir . DIRECTORY_SEPARATOR);
            shell_exec("zip -qr \"{$zip}\" \"{$size}\" \"video\" 2>&1");
            copy($zip . '.zip', $zipFile);
            @unlink($zip);
            @unlink($zip . '.zip');
            $this->delDir($tempdir);
        }

	    if($force_update === false) {
	        // send and delete the zip
	        $this->response->send_file($zipFile, "thumb.zip");
	    }
    }

    /**
     * @param $videoTemp
     * @return bool
     */
    private function create_video_thumbs($videoTemp) {
        $articles = Doctrine::instance()->getRepository('Model_Article')->findBy(array('deleted' => 0, 'active' => 1));

        foreach ($articles as $article) {
            $videos = $article->getVideos();

            foreach ($videos as $vid) {
                if(preg_match('/^http:\/\/vimeo\.com\/(\d+)$/i', $vid['url'], $match)) {
                    $imgid = $match[1];
                    $hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/{$imgid}.php"));
                    if(isset($hash[0]) && isset($hash[0]['thumbnail_large']) && !is_file($videoTemp . DIRECTORY_SEPARATOR . $imgid . '_vimeo.jpg')) {
                       $img = file_get_contents($hash[0]['thumbnail_large']);
                       file_put_contents($videoTemp . DIRECTORY_SEPARATOR . $imgid . '_vimeo.jpg', $img);
                   }

               } elseif(preg_match('/^(http|https):\/\/www\.youtube\.com\/watch.*?v=([a-zA-Z0-9\-_]+).*$/i' , $vid['url'], $match)) {
                    if(!is_file($videoTemp . DIRECTORY_SEPARATOR . $match[2] . '_youtube.jpg')) {
                        $img = file_get_contents("http://img.youtube.com/vi/{$match[2]}/0.jpg");
                        file_put_contents($videoTemp . DIRECTORY_SEPARATOR . $match[2] . '_youtube.jpg', $img);
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param $size
     */
    private function create_thumbs($size) {
        $files = Doctrine::instance()->getRepository('Model_File')->findAll();

        foreach ($files as $file) {

            $info = pathinfo($file->filename);
	        if(isset($info['extension'])) {
		          $filename = basename($file->filename, '.' . $info['extension']);

		          if (!is_dir(Kohana::$config->load('project.public_preview_path') . DIRECTORY_SEPARATOR . $size)) {
		              mkdir(Kohana::$config->load('project.public_preview_path') . DIRECTORY_SEPARATOR . $size, 0777);
		          }

		          $dst = Kohana::$config->load('project.public_preview_path') . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $file->id . '-' . $filename . '.' . $file->ext;

		          if (!is_file($dst) && is_file($file->path())) {
		                  //image
		              if (substr(strtolower($file->mime), 0, 5) == 'image') {
		                  $cmd = Kohana::$config->load('project.convert_path') . " {$file->path()} -resize {$size} -gravity center -background white -extent {$size} {$dst}";
		                      // pdfs
		              } elseif ($file->ext == 'pdf') {
		                  $cmd = Kohana::$config->load('project.convert_path') . " '{$file->path()}[0]' -background '#ffffff' -resize {$size} -colorspace RGB -quality 80 {$dst}";
		              } else {
		                  error_log('Error: Thumbnail creation failed, file type not supported: ' . $file->path());
		              }
		              $result = system($cmd . ' 2>&1');
		              //$f[] = array('cmd' => $cmd, 'result' => $cmd);
		              @chmod($dst, 0777);
		          }
	        }
        }
    }

    /**
     * creates a temp dir
     *
     * @return string
     */
    function tempdir() {
        $tempfile = tempnam(sys_get_temp_dir(), '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        mkdir($tempfile);
        if (is_dir($tempfile)) {
            return $tempfile;
        }
    }

    /**
     * Del a folder recursive
     *
     * @param $dir
     */
    function delDir($dir) {
        $it = new RecursiveDirectoryIterator($dir);
        $files = new RecursiveIteratorIterator($it,
                     RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
