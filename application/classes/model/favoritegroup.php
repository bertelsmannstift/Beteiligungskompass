<?php

/**
 * @Entity
 * @Table(name="favoritegroup")
 */
class Model_Favoritegroup extends Model_Base {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

    /**
   	 * @Column(type="string", name="name", length=255, unique=false, nullable=false)
   	 */
   	protected $name = null;

    /**
   	 * @ManyToOne(targetEntity="Model_User", inversedBy="favoritegroups")
   	 */
   	protected $user;

    /**
   	 * @ManyToMany(targetEntity="Model_Favorite", mappedBy="favoriteGroups")
   	 **/
  	protected $favorites = null;

    function getArticles($own_entries = false) {
        $articles = array();

        foreach($this->favorites as $fav) {
            $article = $fav->article;
            if(!$article->deleted && (($own_entries == true && $article->isOwnedByCurrentUser()) || (!$article->isOwnedByCurrentUser() && $own_entries == false  && $article->active))) {
                $articles[$article->id] = $article;
            }
        }
        return $articles;
    }

    /**
   	* @Column(type="datetime", name="created", nullable=false)
   	*/
   	protected $created = null;

    public function __construct() {
   		$this->favorites = new Doctrine\Common\Collections\ArrayCollection;
   		$this->created = new DateTime();
   		$this->shared = false;
   		$this->isUserArticleGroup = false;
   	}

    public function getSharehash() {
      return md5($this->id . ($this->created ? $this->created->format('U') : ''));
    }

    /**
   	 * @Column(type="boolean", name="shared", nullable=false)
   	 */
   	protected $shared = false;

    /**
   	 * @Column(type="boolean", name="is_user_article_group", nullable=false)
   	 */
   	protected $isUserArticleGroup = false;
}