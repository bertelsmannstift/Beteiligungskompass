<?php

/**
 * @Entity
 * @Table(name="rss_feeds")
 */
class Model_Rssfeed extends Model_Base {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

	/**
	 * @Column(type="string", name="url", length=255, unique=true, nullable=false)
	 */
	protected $url = null;

    /**
     * @Column(type="string", name="author", length=255, nullable=false)
   	 */
   	protected $author;

    /**
     * @Column(type="string", name="type", length=255, nullable=false)
   	 */
   	protected $type = 'news';

	function setType($type) {
		if(in_array($type, array('news', 'event'))) {
			$this->type = $type;
			return true;
		}
		return false;
	}

    /**
   	 * @ManyToOne(targetEntity="Model_File")
     * @JoinColumn(name="logo", referencedColumnName="id")
   	 */
   	protected $logo;

    /**
   	 * @OneToMany(targetEntity="Model_Article", mappedBy="rssfeed", cascade={"detach"})
   	 */
   	protected $articles = null;

    public function __construct() {
   		$this->articles = new Doctrine\Common\Collections\ArrayCollection;
   	}
}