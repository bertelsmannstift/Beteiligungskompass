<?php

/**
 * @Entity
 * @Table(name="users")
 * @@HasLifecycleCallbacks
 */
class Model_User extends Model_Base {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

	/**
	 * @Column(type="boolean", name="is_active", nullable=false)
	 */
	protected $is_active = true;

	/**
	 * @Column(type="boolean", name="is_deleted", nullable=false)
	 */
	protected $is_deleted = false;

	/**
	 * @Column(type="string", name="email", length=255, unique=true, nullable=false)
	 */
	protected $email = null;

	/**
	 * @Column(type="string", name="first_name", length=255, unique=false, nullable=false)
	 */
	protected $first_name = null;

	/**
	 * @Column(type="string", name="last_name", length=255, unique=false, nullable=false)
	 */
	protected $last_name = null;

	public function getName() {
		return $this->first_name . " " . $this->last_name;
	}

	/**
	 * @Column(type="string", name="password", length=255, unique=false, nullable=false)
	 */
	protected $password = null;

	public function setPassword($value) {
		$this->password = sha1($value . $this->salt);
	}

    public function comparePassword($pw) {
        if($this->password == sha1($pw . $this->salt)) {
            return true;
        }
        return false;
    }

	/**
	 * @Column(type="string", name="salt", length=255, unique=false, nullable=false)
	 */
	protected $salt = null;

	/**
	 * @Column(type="string", name="hash", length=8, unique=true, nullable=false)
	 */
	protected $hash = null;

	/**
	 * @Column(type="string", name="api_token", length=40, unique=false, nullable=true)
	 */
	protected $token = null;

	/**
	 * @Column(type="boolean", name="is_editor", nullable=false)
	 */
	protected $is_editor = false;

	/**
	 * @Column(type="boolean", name="is_admin", nullable=false)
	 */
	protected $is_admin = false;

	/**
	 * @Column(type="boolean", name="dbloptin", nullable=false)
	 */
	protected $dbloptin = false;

	/**
	 * @OneToMany(targetEntity="Model_Article", mappedBy="user")
	 */
	protected $articles = null;

    /**
   	 * @OneToMany(targetEntity="Model_Favorite", mappedBy="user")
   	 */
	protected $favorites = null;

    /**
   	 * @OneToMany(targetEntity="Model_Favoritegroup", mappedBy="user")
     * @OrderBy({"name" = "ASC"})
   	 */
   	protected $favoritegroups = null;

    function getFavoritegroups() {
        $favoritegroups = array();
        foreach($this->favoritegroups as $group) {
            if($group->isUserArticleGroup === false) {
                $favoritegroups[] = $group;
            }
        }
        return $favoritegroups;
    }

    function getArticlegroups() {
        $articlegroups = array();
        foreach($this->favoritegroups as $group) {
            if($group->isUserArticleGroup === true) {
                $articlegroups[] = $group;
            }
        }
        return $articlegroups;
    }

    /**
   	 * @OneToMany(targetEntity="Model_Rssfeed", mappedBy="user")
   	 */
   	protected $rssfeeds = null;

	public function __construct() {
		$this->salt = sha1(serialize($_SERVER . microtime()));
		$this->hash = substr(md5($this->salt), 0, 8);
		$this->articles = new Doctrine\Common\Collections\ArrayCollection;
		$this->favorites = new Doctrine\Common\Collections\ArrayCollection;
		$this->favoritegroups = new Doctrine\Common\Collections\ArrayCollection;
	}

	/** @PrePersist */
	public function checkBeforeInsert() {
		$validation = Validation::factory($this->to_array())
			->addRule('password', 'not_empty')
			->addRule('first_name', 'not_empty')
			->addRule('email', 'not_empty')
			->addRule('email', 'email')
			->rule('email', 'Helper_User::validate_email_unique', array(':validation', 'email', $this->email))
			->addRule('last_name', 'not_empty');

		if($validation->check()) return true;

		throw new Validation_Exception($validation, "Failed to validate Document " . get_class($this));
	}

	public function sendActivationMail() {
		if($this->dbloptin) {
			throw new Kohana_Exception('This account is allready activated');
		}

		$mail = SmartyView::factory('email/activation', array(
			'link' => Url::base() . Url::get("route:activation hash:{$this->hash}")
		))->render();

		return mail($this->email, Helper_Message::get('email.activation.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Kohana::$config->load('project.email.activation.from'));
	}

	public function sendNewPassword() {
        $newPassword = strtolower(substr(md5(microtime()), 0, 6));
        $this->salt = sha1(serialize($_SERVER . time()));
        $this->hash = substr(md5($this->salt), 0, 8);
        $this->setPassword($newPassword);

        Doctrine::instance()->persist($this);
        Doctrine::instance()->flush();

		$mail = SmartyView::factory('email/new_password', array(
			'password' => $newPassword
		))->render();

		return mail($this->email, Helper_Message::get('email.new_password.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Kohana::$config->load('project.email.new_password.from'));
	}

	public function sendRemoveAccEmail() {
		$mail = SmartyView::factory('email/removeacc', array(
			'link' => Url::base() . Url::get("route:removeaccconfirm hash:{$this->hash}")
		))->render();

		return mail($this->email, Helper_Message::get('email.remove_account.subject'), $mail, "Content-type: text/plain; charset=utf-8\r\n" . "From: " . Kohana::$config->load('project.email.remove_account.from'));
	}

	public function getFavoritesList($allNotOwned = true, $order = 'title', $sort = true) {
		$direction = ($order === "title") ? "ASC" : "DESC";

		$query = Doctrine::instance()
			->createQueryBuilder()
				->select('a')
				->from('Model_Article', 'a')
				->where('a.deleted != 1');
				//->andWhere("a.title != ''");

		if($allNotOwned) {
		    $query = $query->join('a.favedBy', 'f')
				->andWhere("(a.user != :user OR a.user IS NULL) AND a.active = 1 AND f.user = :user");
		} else {
            $query = $query->andWhere("(a.user = :user)");
        }


        if($sort) {
            if($order == 'created') {
                if($allNotOwned) {
                    $query = $query->orderby('f.created', "DESC");
                } else {
                    $query = $query->orderby('a.created', "DESC");
                }
            } else {
                $query = $query->orderby('a.' . $order, $direction);
            }
        }

		$result = $query->groupBy('a.id')->getQuery()
			->setParameter('user', $this)
			->getResult();

        if($order != 'created') {
            $result = Helper_Article::sortResult($result);
        }

		return $result;
	}

    /**
     * Return the user favorite count
     *
     * @return int
     */
    public function getFavoritesCount() {
        $favs = $this->getFavoritesList(true, '', false);
        $count = 0;
        foreach($favs as $f) {
            $count += count($f->articles);
        }

        return $count;
    }

    /**
     * Return the nummer of articles from the user
     *
     * @return int
     */
    public function getMyArticleCount() {
        $favs = $this->getFavoritesList(false, '', false);
        $count = 0;
        foreach($favs as $f) {
            $count += count($f->articles);
        }
        return $count;
    }

	public function isEditor() {
		return ($this->is_editor OR $this->is_admin);
	}

	public function isAdmin() {
		return ($this->is_admin);
	}

	public function isAdminAsString() {
		return ($this->isAdmin()) ? Helper_Message::get("global.yes") : Helper_Message::get("global.no");
	}

	public function isActiveAsString() {
		return ($this->is_active) ? Helper_Message::get("global.yes") : Helper_Message::get("global.no");
	}
}