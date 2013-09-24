<?php

/**
 * @Entity
 * @Table(name="criteria_options")
 */
class Model_Criterion_Option extends Model_Base {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

	/**
	 * @Column(type="integer", nullable=true)
	 */
	protected $involveid = null;

	/**
	 * @Column(type="string", name="title", length=255, unique=false, nullable=false)
	 */
	protected $title = null;

	/**
	 * @Column(type="text", name="description")
	 */
	protected $description = "";

	/**
	 * @Column(type="boolean", name="deleted", nullable=false)
	 */
	protected $deleted = false;

	/**
	 * @Column(type="boolean", name="default_value", nullable=false)
	 */
	protected $default = false;

	/**
	 * @Column(type="integer", name="orderindex", nullable=false)
	 */
	protected $orderindex = 0;

	/**
	 * @ManyToOne(targetEntity="Model_Criterion", inversedBy="options")
	 */
	protected $criterion = null;

    /**
   	 * @OneToMany(targetEntity="Model_Criterion_Option", mappedBy="parentOption", cascade={"all"})
     * @OrderBy({"orderindex" = "ASC", "title" = "ASC"})
   	 */
   	protected $childOptions;

    /**
   	 * @ManyToOne(targetEntity="Model_Criterion_Option", inversedBy="childOptions")
   	 */
   	protected $parentOption = null;

	/**
   * @ManyToMany(targetEntity="Model_Article", mappedBy="criteria")
   **/
 	protected $articles;

	public function __construct() {
		$this->articles = new Doctrine\Common\Collections\ArrayCollection;
		$this->childOptions = new Doctrine\Common\Collections\ArrayCollection;
	}

	public function __toString() {
		return $this->title;
	}
}