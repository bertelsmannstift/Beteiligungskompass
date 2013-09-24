<?php

/**
 * @Entity
 * @Table(name="articles")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"study"="Model_Article_Study", "method"="Model_Article_Method","instrument"="Model_Article_Instrument", "qa" = "Model_Article_Qa", "expert" = "Model_Article_Expert", "news" = "Model_Article_News", "event" = "Model_Article_Event"})
 * @HasLifecycleCallbacks
 */
abstract class Model_Article extends Model_Base {

	const CaseStudy = "study";
	const ParticipationMethod = "method";
	const ParticipationInstrument = "instrument";
	const QuestionAndAnswer = "qa";
	const Expert = "expert";
	const News = "news";
	const Event = "event";

	public static $articleTypes = array(
		Model_Article::CaseStudy,
		Model_Article::ParticipationInstrument,
		Model_Article::ParticipationMethod,
		Model_Article::QuestionAndAnswer,
		Model_Article::Expert,
		Model_Article::News,
		Model_Article::Event
	);

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

	public function getCountry() {
		foreach($this->criteria as $option) {
			if($option->criterion->discriminator == "country" && !$option->default) {
				return $option->title;
			}
		}
		return "";
	}

	public function getCountryId() {
		foreach($this->criteria as $option) {
			if($option->criterion->discriminator == "country" && !$option->default) {
				return $option->id;
			}
		}
		return false;
	}

	public function getTypeOfEvent() {
		foreach($this->criteria as $option) {
			if($option->criterion->discriminator == "type_of_event" && !$option->default) {
				return $option->title;
			}
		}
		return "";
	}

	/**
	 * @Column(type="integer", nullable=true)
	 */
	protected $involveid = null;

	/**
	 * @Column(type="text", name="short_description")
	 */
	protected $short_description = '';

	/**
	 * @Column(type="string", name="title", length=255, unique=false, nullable=true)
	 */
	protected $title = null;

	public function getTitle() {
		return $this->title;
	}

	function getShortTitle($maxLen = 15) {
		if(strlen($this->getTitle()) > $maxLen) {
			return substr($this->getTitle(), 0, $maxLen) . '...';
		} else {
			return $this->getTitle();
		}
	}

	/**
	 * @Column(type="text", name="author")
	 */
	protected $author = '';

	/**
	 * @Column(type="array", name="videos", nullable=true)
	 */
	protected $videos = array();

	public function setVideos($data) {
		if(!$data) return;
		if(!is_array($data)) $data = array('url' => $data, 'featured' => false, 'description' => '');

		$data = array_filter($data, function($val){
			return (bool) strlen($val['url']);
		});

		$this->videos = $data;
	}

    function setVideo($data) {

        if(!is_array($data)) $data = array('url' => $data, 'featured' => false, 'description' => '');

        $videos = $this->getVideos();
        $updated = false;

        foreach($videos as &$video) {
            if($video['url'] == $data['url']) {
                $video = $data;
                $updated = true;
                break;
            }
        }

        if(!$updated) {
            $videos = array_merge($videos, $data);
        }

        $this->videos = $videos;
    }

    function getVideos() {
        $videos = array();
        foreach($this->videos as $video) {
            if(is_array($video)) {
                $videos[] = $video;
            } else {
                $videos[] = array('url' => $video, 'featured' => false, 'description' => '');
            }
        }
        return $videos;
    }

	/**
	 * @Column(type="array", name="external_links", nullable=true)
	 */
	protected $external_links = array();

	public function setExternal_links($data) {

		if(!$data) return;
		if(!is_array($data)) $data = array(array('url' => $data));

		$links = array();

		foreach($data as $link) {
			$links[] = array("url"=>$link["url"],"show_link" => isset($link["show_link"]));
		}

		$data = array_filter($links, function($val){
			return (bool) strlen($val["url"]);
		});

		$this->external_links = $data;
	}

    function getExternal_links() {
        $links = array();
        foreach($this->external_links as $link) {
            if(is_array($link)) {
                $links[] = $link;
            } else {
                $links[] = array('url' => $link, 'show_link' => true);
            }
        }

        return $links;
    }

	/**
	 * @Column(type="boolean", name="deleted", nullable=false)
	 */
	protected $deleted = false;

	/**
	* @Column(type="datetime", name="created", nullable=false)
	*/
	protected $created = null;

	/**
	 * @Column(type="datetime", name="updated", nullable=false)
	 */
	protected $updated = null;

	/**
    	* @ManyToMany(targetEntity="Model_Article", inversedBy="linked_from_articles", cascade={"remove"})
    	* @JoinTable(name="article_links",
    	*      joinColumns={@JoinColumn(name="article_id", referencedColumnName="id")},
    	*      inverseJoinColumns={@JoinColumn(name="article_linked_id", referencedColumnName="id")}
    	*      )
    **/
    protected $linked_articles = array();

    public function setLinked_articles($data) {

        foreach($this->linked_articles as $art) {
            if($art->linked_articles->contains($this)) {
                $art->linked_articles->removeElement($this);
                Doctrine::instance()->persist($art);
            }
        }

        $this->linked_articles->clear();

        if(!$data) return true;

        foreach($data as $id) {
            if($article = Doctrine::instance()->getRepository('Model_Article')->findOneById($id) AND !$article->deleted) {
                $this->linked_articles->add($article);
                if(!$article->linked_articles->contains($this)) {
                    $article->linked_articles->add($this);
                }

                Doctrine::instance()->persist($article);
            }
        }
        return true;
    }

    public function getDuration() {

    	$all_dates = $this->start_month . ' ' . $this->start_year . ' ' .  $this->end_month . ' ' . $this->end_year;
    	// var_dump($all_dates);

    	if (empty($this->start_year) && empty($this->end_year)) {
    		return false;
    	}

    	$duration = '';

    	if ( ! empty($this->start_year) ) {
    		$start = (empty($this->start_month) ? '' : $this->getMonthString($this->start_month) . ' ');
    		$start .= $this->start_year;
    		$duration = $start;
    	}

    	if ( ! empty($this->end_year) ) {
    		$end = (empty($this->end_month) ? '' : $this->getMonthString($this->end_month) . ' ');
    		$end .= $this->end_year;

    		if ( empty($duration)) {
    			$duration .= Helper_Message::get('global.till') . ' ' . $end;
    		}
    		else {
    			$duration .= ' &ndash; ' . $end;
    		}
    	}
    	else {
    		$duration = Helper_Message::get('global.since') . ' ' . $duration;
    	}

    	return $duration;
    }

    function getMonthString($n)
    {
    	if (empty($n)) {
    		return false;
    	}

        $timestamp = mktime(0, 0, 0, $n, 1, 2012);

        return strftime("%B", $timestamp);
    }

	/**
 	* @Column(type="text", name="more_information")
 	*/
 	protected $more_information = '';

	/**
	 * @ManyToMany(targetEntity="Model_Article", mappedBy="linked_articles", cascade={"remove"})
	 **/
	protected $linked_from_articles = array();

	public function getLinkedArticles() {
		$articles = array();

		foreach($this->linked_articles as $art) {
			$articles[$art->id] = $art;
		}

		foreach($this->linked_from_articles as $art) {
			$articles[$art->id] = $art;
		}

		return $this->sortArticlesByCountry($articles);
	}

    private function sortArticlesByCountry($articles) {
        $config = Helper_Message::loadFile(APPPATH . 'config/base.config');
        $countries = array_reverse(explode('|', $config["country.sort"]));
        $articles = array_values($articles);

        usort($articles, function($a, $b) use($countries) {
            $aPos = array_search($a->getCountryId(), $countries);
            $bPos = array_search($b->getCountryId(), $countries);

            if(intval($aPos) < intval($bPos)) {
                return 1;
            }
        });
        return $articles;
    }

	/**
	* @ManyToMany(targetEntity="Model_Criterion_Option", inversedBy="articles")
	* @JoinTable(name="articles_options")
    * @OrderBy({"orderindex" = "ASC", "title" = "ASC"})
	**/
	protected $criteria = null;
	public function setCriteria($data) {
		if(!$data) return;
		if(is_array($data)) {
			$this->criteria->clear();
			foreach($data as $optionId) {
				if($optionId > 0) {
					$this->criteria->add(Doctrine::instance()->getRepository('Model_Criterion_Option')->findOneById($optionId));
				}
			}
		} else {
			$this->criteria = $data;
		}
	}

    /**
   	 * @OneToMany(targetEntity="Model_Favorite", mappedBy="article")
   	 */
   	protected $favedBy = null;

	/**
	 * @Column(type="boolean", name="ready_for_publish", nullable=false)
	 */
	protected $ready_for_publish = false;

	public function setReady_for_publish($value) {
		if($value === true AND !$this->active AND $this->ready_for_publish !== true) {
			$this->sendReadyForPublishMail();
		}

		$this->ready_for_publish = $value;
	}

	public function sendReadyForPublishMail() {

		$mail = SmartyView::factory('email/readyforpublish', array(
			'link' => Kohana::$is_cli ? '- RSS Import -' : Url::base() . Route::get('default')->uri(array('controller' => 'article', 'action' => 'show', 'id' => $this->id)),
			'title' => $this->getTitle(),
		))->render();

		return mail(Kohana::$config->load('project.email.readforpublish.to'), Helper_Message::get('email.readforpublish.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Kohana::$config->load('project.email.readforpublish.from'));
	}

	/**
	 * @Column(type="boolean", name="active", nullable=false)
	 */
	protected $active = false;
    private $msgActive = false;

	public function isActiveAsString() {
        if(!$this->msgActive) {
            $this->msgActive = ($this->active) ? Helper_Message::get("global.yes") : Helper_Message::get("global.no");
        }

		return $this->msgActive;
	}

	/**
	 * @Column(type="array", name="images", nullable=true)
	 */
	protected $images = array();

	public function setImages($data) {
		if(!$data) {
			return $this->images = array();
		}

		if(!is_array($data)) $data = array($data);

		$this->images = $data;
	}

    private function getFileList(array $filetype) {
        $images = array();
        if(!$this->images) return $images;

        foreach($this->images as $img) {

            if(!$id = Arr::get($img, 'id') OR !$file = Doctrine::instance()->createQuery('SELECT f FROM Model_File f WHERE f.id = :id')->setParameter('id', $id)->useResultCache(true, RESULT_CACHE_LIFETIME)->getOneOrNullResult()) {
                continue;
            }

            if(!in_array($file->ext, $filetype)) {
                continue;
            }

            $images[] = (object) array(
                'file' => $file,
                'description' => Arr::get($img, 'description')
                );
        }

        return $images;
    }

    /**
   	 * @Column(type="integer", name="logo", nullable=true)
   	 */
   	protected $logo;

    public function getLogo() {
        return $this->logo ? Doctrine::instance()->getRepository('Model_File')->findOneById($this->logo) : false;
    }

    public function getLogoFilename() {
        if(!$this->logo) {
            return false;
        }
        $file = Doctrine::instance()->getRepository('Model_File')->findOneById($this->logo);
        return $file ? Kohana::$config->load('project.upload_dir') . $file->filename : '';
    }

	public function imageList() {
		return $this->getFileList(array('png', 'jpg', 'gif'));
	}

	public function fileList() {
		return $this->getFileList(array('pdf'));
	}

	/**
	 * @ManyToOne(targetEntity="Model_User", inversedBy="articles")
	 */
	protected $user;

	/**
	 * @ManyToOne(targetEntity="Model_Rssfeed", inversedBy="rssfeed")
	 */
	protected $rssfeed;

	public function __construct() {
		$this->linked_articles = new Doctrine\Common\Collections\ArrayCollection;
		$this->linked_from_articles = new Doctrine\Common\Collections\ArrayCollection;
		$this->criteria = new Doctrine\Common\Collections\ArrayCollection;
		$this->favedBy = new Doctrine\Common\Collections\ArrayCollection;

		$this->created = new DateTime("now");
		$this->updated = new DateTime("now");
	}


    public function getMedium() {
   		foreach($this->criteria as $option) {
   			if($option->criterion->discriminator == "limit_search" && !$option->default) {
   				return $option->title;
   			}
   		}
   		return "";
   	}

    public function getCostsString() {
   		foreach($this->criteria as $option) {
   			if($option->criterion->discriminator == "ressources" && !$option->default) {
   				return $option->title;
   			}
   		}
   		return "";
   	}


	public function type() {
		return Doctrine::instance()->getMetadataFactory()->getMetadataFor(get_class($this))->discriminatorValue;
	}

	public function getForm() {
		return Helper_Article::getForm($this->type());
	}

	public function getCriteriaList() {
		$criteria = array();
        $items = Doctrine::instance()
      			->createQuery("SELECT o
                     FROM Model_Criterion_Option as o

      				 JOIN o.criterion as c LEFT JOIN o.articles a
      				WHERE a.id = {$this->id} AND c.deleted = false AND o.deleted = false
      					AND o.default = false
      				ORDER BY c.orderindex ASC, c.title ASC, o.orderindex ASC, o.title ASC")->useResultCache(false)
      			->getResult();
		foreach($items as $option) {
            if($option->criterion->isArticleTypeAllowed($this->type())) {
                $criteria[(string) $option->criterion][] = (string) $option;
            }
		}
		return $criteria;
	}

    public function getTypeName() {
       return Helper_Message::get('article_config.' . $this->type() . '.title');
    }

	public function getSidebar() {
		return Helper_Article::getSidebar($this->type());
	}

	public function getMain() {
		return Helper_Article::getMain($this->type());
	}


	public function isEditable() {
		if($this->ready_for_publish) return false;
		if($this->active) return false;
		if(!$currentUser = Helper_User::getUser()) return false;
		if($currentUser->id != $this->user->id) return false;

		return true;
	}

	public function isFavedByCurrentUser() {

        $currentUser = Helper_User::getUser();
        if($this->user && $this->user->id && $this->user->id == $currentUser->id) {
            return true;
        }

		if(!$currentUser && is_object($this->stickyDate))  {
            return true;
        } elseif(!$currentUser) {
            return false;
        }

        foreach($this->favedBy as $u) {
            if($u->user->id == $currentUser->id) {
                return true;
            }
        }

		return false;
	}

	public function isOwnedByCurrentUser() {
		if($currentUser = Helper_User::getUser() AND $currentUser AND $this->user AND $currentUser->id == $this->user->id) return true;
		return false;
	}

	public function getFavoriteCount() {
		$result =  Doctrine::instance()
		->createQueryBuilder()
		->select('COUNT(f.id)')
		->from('Model_User', 'u')
		->join('u.favorites', 'f')
		->where('f.id = :id')
		->setParameter('id', $this->id)
		->getQuery()
		->getSingleScalarResult();

		return intval($result);
	}

	public function canLink() {
		return Model_Article::$articleTypes;
	}

	/** @preUpdate */
	public function _update() {
		$this->updated = new DateTime("now");
	}

    function getFavoriteGroups($from_user = true, $isUserArticleGroup = false) {
        $groups = array();
        $user = Helper_User::getUser();

        foreach($this->favedBy as $favorite) {
            foreach($favorite->favoriteGroups as $group) {
                if($group->isUserArticleGroup == $isUserArticleGroup) {

                    if($from_user == false || $user->id == $group->user->id) {
                        $groups[$group->id] = $group->name;
                    }
                }
            }
        }
        return $groups;
    }

    function removeFromFavorites() {
        $user = Helper_User::getUser();

        foreach($user->favorites as $favorite) {
            if($favorite->article->id == $this->id) {
                Doctrine::instance()->remove($favorite);
            }
        }

        Doctrine::instance()->flush();
        Doctrine::getCache()->delete('my_favorites');
        return true;
    }

    function isFavorite() {
        $user = Helper_User::getUser();

        if($user) {
            foreach($user->favorites as $favorite) {
                if($favorite->article->id == $this->id) {
                    return true;
                }
            }
        }

        return false;
    }

    function addToFavorites() {
        $user = Helper_User::getUser();

        $newFav = Doctrine::instance()->getRepository('Model_Favorite')->findOneBy(array('user' => $user->id, 'article' => $this->id));

        if(!$newFav) {
            $newFav = new Model_Favorite();
            $newFav->user = $user;
            $newFav->article = $this;
            Doctrine::instance()->persist($newFav);
            Doctrine::instance()->flush();
        }

        Doctrine::getCache()->delete('my_favorites');
        Session::instance()->delete('type_results');
        return $newFav;
    }

    /**
   	* @Column(type="datetime", name="sticky", nullable=true)
   	*/
   	protected $stickyDate = null;
}
