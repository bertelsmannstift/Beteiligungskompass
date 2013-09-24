<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Backend_Rss extends Controller_Backend_Base {

    /**
     * List all rss feeds
     */
    public function action_index()
	{
        $rssfeed = new Model_Rssfeed();
        $values = $rssfeed->to_array();

        if($_POST) {
            $validate = Validation::factory($_POST)
         				->rule('url', 'url');

            if($validate->check()) {
                try {
                    if(isset($_FILES)) {
                        $newLogoFile = $this->handleLogoUpload($_FILES);
                        $rssfeed->logo = $newLogoFile;
                    }
                    $rssfeed->from_array($validate->as_array());
                    Doctrine::instance()->persist($rssfeed);
                    Doctrine::instance()->flush();

                    $this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
                    $this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'rss', 'action' => 'index')));
                } catch(Exception $e) {
                    $this->msg(Helper_Message::get('backend.add_error'), 'error');
                }
            } else {
                $this->msg(Helper_Message::get("backend.check_input"), 'error');
            }

             $this->view->errors = $validate->errors('validation');
             $values = $validate->as_array();
        }

        $this->view->values = $values;
        $this->view->feeds = Doctrine::instance()->getRepository('Model_Rssfeed')->findAll();
    }

    /**
     * Edit a rss feed entry
     *
     * @throws Kohana_Exception
     */
    public function action_edit()
	{
        if(!$id = $this->request->param('id') OR !$rssfeed = Doctrine::instance()->getRepository('Model_Rssfeed')->findOneById($id)) {
      			throw new Kohana_Exception('feed not found', null, 404);
        }

        $values = $rssfeed->to_array();

        if($_POST) {
            $validate = Validation::factory($_POST)
         				->rule('url', 'url');

            if($validate->check()) {
                try {

                    if(isset($_FILES) && $_FILES['logo']['error'] == 0) {
                        $newLogoFile = $this->handleLogoUpload($_FILES);
                        $rssfeed->logo = $newLogoFile;
                    } else {
                        $rssfeed->logo = null;
                    }

                    $rssfeed->from_array($validate->as_array());

                    foreach($rssfeed->articles as $article) {
                        $article->logo = $rssfeed->logo->id;
                        Doctrine::instance()->persist($article);
                    }

                    Doctrine::instance()->persist($rssfeed);
                    Doctrine::instance()->flush();

                    $this->flashMsg(Helper_Message::get('backend.add_success'), 'success');
                    $this->request->redirect(Url::get(array('route' => 'backend-default', 'directory' => 'backend', 'controller' => 'rss', 'action' => 'index')));
                } catch(Exception $e) {
                    $this->msg(Helper_Message::get('backend.add_error'), 'error');
                }
            } else {
                $this->msg(Helper_Message::get("backend.check_input"), 'error');
            }

             $this->view->errors = $validate->errors('validation');
             $values = $validate->as_array();
        }

        $this->view->values = $rssfeed;
        $this->view->feed = $rssfeed;
    }

    /**
     * Handle rss feed logo upload
     *
     * @param $file
     * @return Model_File
     */
    private function handleLogoUpload($file) {
        $targetDir = Kohana::$config->load('project.upload_dir');
        $fileName = $file['logo']['name'];

		// Clean the fileName for security reasons
		$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        move_uploaded_file($file['logo']['tmp_name'], $filePath);
        $file = new Model_File($filePath);
        Doctrine::instance()->persist($file);
        Doctrine::instance()->flush($file);
        return $file;
    }

    /**
     * Delete a rss feed entry
     *
     * @throws Kohana_Exception
     */
    public function action_delete() {
   		if(!$id = $this->request->param('id') OR !$rssfeed = Doctrine::instance()->getRepository('Model_Rssfeed')->findOneById($id)) {
   			throw new Kohana_Exception('rss feed not found', null, 404);
   		}

   		try {
   			Doctrine::instance()->remove($rssfeed);
   			Doctrine::instance()->flush();
   			$this->flashMsg(Helper_Message::get('backend.delete_success'), 'success');
   		} catch(Exception $e) {
   			$this->flashMsg(Helper_Message::get('backend.delete_error'), 'error');
   		}

   		$this->redirectBack();
   	}

    /**
     * Start cron "php index.php --uri=backend/rss/importrss"
     */
    function action_importrss() {

        $this->auto_render = false;
        $feeds = Doctrine::instance()->getRepository('Model_Rssfeed')->findAll();
	    $imported = 0;

        foreach($feeds as $feed) {
            $rsscontent = file_get_contents($feed->url);
            if(!empty($rsscontent)) {
                $xml = simplexml_load_string($rsscontent, 'SimpleXMLElement', LIBXML_NOCDATA);

                if($xml === false) {
                    $errorLogString = date('d.m.Y H:i:s') . " - XML String cannot loaded: \n\n" . $rsscontent;
                    // on error
                    error_log($errorLogString);
                    // next rss feed
                    continue;
                } elseif($feed->type == 'news') {
	                $imported += $this->createNewsFromRss($feed, $xml);
                } elseif($feed->type == 'event') {
	                $imported += $this->createEventsFromRss($feed, $xml);
                } else {
                    error_log('Model_Rssfeed event type not defined.');
                }
            }
        }
        Doctrine::instance()->flush();
        echo sprintf("%s - Es wurden %d neue Einträge hinzugefügt.\n",date("d.m.Y"), $imported);
    }

    /**
     * Create new events from a rss feed
     *
     * @param $feed
     * @param $xml
     * @return int
     */
    private function createEventsFromRss($feed, $xml) {
		$imported = 0;

        $elementCount = 0;
		$ns = $xml->getNamespaces(true);
		$items = (array)$xml->channel;
        foreach($items['item'] as $k => $item) {
	        $nsChilds = $item->children($ns['event']);
            $itemtitle = (string)$item->title;
            $itemdescription = (string)$item->description;

            $queryParam = array('title' => $itemtitle, 'deleted' => 0, 'email' => trim((string)$nsChilds->email));

            if((string)$nsChilds->date != '') {
                $startdate = $this->getDateTimeFromEventRSS($nsChilds->date);
                $queryParam = array_merge($queryParam, array('start_date' => $startdate));
            }
            if((string)$nsChilds->enddate != '') {
                $enddate = $this->getDateTimeFromEventRSS($nsChilds->enddate);
                $queryParam = array_merge($queryParam, array('end_date' => $enddate));
            }

            $exists = Doctrine::instance()->getRepository('Model_Article_Event')->findOneBy($queryParam);

            if(!$exists) {
                $event = new Model_Article_Event();
            } else {
	            continue;
            }

            $event->title = $itemtitle;
            $event->description = $itemdescription;

	        if((string)$nsChilds->zip != '') {
		        $event->zip = trim((string)$nsChilds->zip);
	        }
	        if((string)$nsChilds->city != '') {
		        $event->city = trim((string)$nsChilds->city);
	        }
	        if((string)$nsChilds->city != '') {
		        $event->venue = trim((string)$nsChilds->city);
	        }
	        if((string)$nsChilds->organizer != '') {
		        $event->organized_by = trim((string)$nsChilds->organizer);
	        }
	        if((string)$nsChilds->contact_name != '') {
		        $event->contact_person = trim((string)$nsChilds->contact_forename . ' ' . (string)$nsChilds->contact_name);
	        }
	        if((string)$nsChilds->fax != '') {
		        $event->fax = trim((string)$nsChilds->fax);
	        }
	        if((string)$nsChilds->telephone != '') {
		        $event->phone = trim((string)$nsChilds->telephone);
	        }
	        if((string)$nsChilds->email != '') {
		        $event->email = trim((string)$nsChilds->email);
	        }

            $event->author = $feed->author;


            if($startdate !== false) {
	            $event->setStart_date($startdate);
            }

            if((string)$nsChilds->enddate != '') {
               if($enddate !== false) {
                $event->setEnd_date($enddate);
               }
            } else {
                $event->setEnd_date($startdate);
            }

            $event->ready_for_publish = true;
            $event->active = true;
            $event->rssfeed = $feed;

            $link = (string) $item->link;
	        if($link != '') {
		        $event->setExternal_links(array(array('url' => $link, 'show_link' => true)));
	        }

            if($feed->logo) {
                $event->logo = $feed->logo->id;
            }

            Doctrine::instance()->persist($event);
            $imported++;

            $elementCount++;
        }
		return $imported;
	}

    /**
     * Create datetime from rss
     *
     * @param $date
     * @return bool|DateTime
     */
    private function getDateTimeFromEventRSS($date) {
		$de = array('Januar','Februar','März', 'April','Mai','Juni', 'Juli','August','September', 'Oktober','November','Dezember');
		$en = array("January", "February", "March", "April", "May", "June",
		            "July", "August", "September", "October", "November", "December");

        // remove double spaces
        $date = preg_replace('/\s+/', ' ', $date);

		$date = explode(' ', $date);

		if(count($date) == 4) {
			unset($date[0]);
			$date[2] = str_ireplace($de, $en, $date[2]);
			return DateTime::createFromFormat('d.m.Y H:i:s', date('d.m.Y 00:00:00', strtotime(implode(' ', $date))));
		}
		return false;
	}

    /**
     * Create new news entries from a rss feed
     *
     * @param $feed
     * @param $xml
     * @return int
     */
    private function createNewsFromRss($feed, $xml) {
		$imported = 0;
        $ns = $xml->getNamespaces(true);

        $elementCount = 0;
        foreach($xml->channel->item as $item) {
            $nsChilds = $item->children($ns['content']);
            if((string)$nsChilds->encoded != '') {
                $desc = trim((string)$nsChilds->encoded);
   	        }

            $itemtitle = (string)$item->title;
            $itemdate = DateTime::CreateFromFormat(DateTime::RSS, (string)$item->pubDate);

            $exists = Doctrine::instance()->getRepository('Model_Article_News')->findOneBy(array('title' => $itemtitle, 'date' => $itemdate, 'deleted' => 0));

            if(!$exists) {
                $news = new Model_Article_News();
                $news->title = $itemtitle;

                $news->date = $itemdate;

                // Extract the author of the item
                $author = $item->xpath("*[local-name()='creator']/text()");

                if($author !== false && count($author) > 0 && !empty($author[0])) {
                    // If an author/creator was found
                    $news->author = $feed->author . " | " . (string)$author[0];
                } else {
                    $news->author = $feed->author;
                }

                $news->ready_for_publish = true;
                $news->rssfeed = $feed;
                $news->text = (string)$desc;

                $link = (string) $item->link;
                $news->setExternal_links(array(array('url' => $link, 'show_link' => true)));

                if($feed->logo) {
                    $news->logo = $feed->logo->id;
                }

                $news->active = true;

                Doctrine::instance()->persist($news);
                $imported++;
            }
            $elementCount++;
        }
		return $imported;
	}
}
