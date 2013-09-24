<?php

/**
 * @Entity
 * @Table(name="favorite_articles")
 */
class Model_Favorite extends Model_Base {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

    /**
   	 * @ManyToOne(targetEntity="Model_Article", inversedBy="favedBy")
   	 */
   	protected $article;

    /**
   	 * @ManyToOne(targetEntity="Model_User", inversedBy="favorites")
   	 */
   	protected $user = null;

    /**
   	 * @ManyToMany(targetEntity="Model_Favoritegroup", inversedBy="favorites", cascade={"ALL"})
     * @JoinTable(name="favorites_groups",
     *      joinColumns={@JoinColumn(name="favorite_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="favoritegroup_id", referencedColumnName="id")}
     *      )
   	 **/
   	protected $favoriteGroups;


    public function __construct() {
   		$this->favoriteGroups = new Doctrine\Common\Collections\ArrayCollection;
   		$this->created = new DateTime();
   	}

    /**
   	* @Column(type="datetime", name="created", nullable=true)
   	*/
   	protected $created = null;
}