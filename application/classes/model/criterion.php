<?php
/**
 * @Entity
 * @Table(name="criteria")
 */
class Model_Criterion extends Model_Base {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	protected $id = null;

	/**
	 * @Column(type="string", name="title", length=255, unique=false)
	 */
	protected $title = "";

	/**
	 * @Column(type="text", name="description")
	 */
	protected $description = "";

	/**
	 * @Column(type="string", name="type", length=255, unique=false, nullable=false)
	 */
	protected $type = 'radio';

	/**
	 * @Column(type="boolean", name="deleted", nullable=false)
	 */
	protected $deleted = false;

	/**
	 * @Column(type="boolean", name="show_in_planner", nullable=false)
	 */
	protected $showInPlanner = true;

	/**
	 * @Column(type="integer", name="orderindex", nullable=false)
	 */
	protected $orderindex = 0;

	/**
	 * @Column(type="string", name="discriminator", nullable=true)
	 */
	protected $discriminator = null;

    /**
   	 * @Column(type="boolean", name="filter_type_or", nullable=false)
   	 */
   	protected $filterTypeOr = false;

    /**
   	 * @Column(type="array", name="article_types", nullable=true)
   	 */
   	protected $articleTypes = null;

    function getArticleTypes() {
        return $this->articleTypes ? $this->articleTypes : array();
    }

    function getArticleTypesAsString() {
        $realNames = array();
        foreach($this->getArticleTypes() as $type) {
            $realNames[] = Helper_Message::get("global.{$type}");
        }
        return implode(', ', $realNames);
    }

    function setArticleTypes($types) {
        $this->articleTypes = $types;
    }


    function isArticleTypeAllowed($type) {
        return in_array($type, $this->getArticleTypes());
    }

	/**
	 * @OneToMany(targetEntity="Model_Criterion_Option", mappedBy="criterion", cascade={"all"})
     * @OrderBy({"orderindex" = "ASC", "title" = "ASC"})
	 */
	protected $options;

    function getOptions() {
        $options = new Doctrine\Common\Collections\ArrayCollection;
        foreach($this->options as $opt) {
            if(!$opt->deleted) {
                $options->add($opt);
            }
        }
        return $options;
    }

    /**
   	 * @Column(type="array", name="group_article_types", length=255, nullable=true)
   	 */
	protected $group_article_types;

    function setGroupArticleTypes($types) {
        $this->group_article_types = $types;
    }

    function getGroupArticleTypes() {
        return $this->group_article_types ? $this->group_article_types : array();
    }

    /**
     * Checks if the article type is grouped by this criterion. Disable grouping on sort by created.
     *
     * @param $type
     * @return bool
     */
    function isGroupedArticleType($type) {
        $sort = Arr::get(Helper_Article::getFilterParams(), 'sort', Helper_Article::getDefaultSort($type));
        return $sort == 'created' ? false : in_array($type, $this->getGroupArticleTypes());
    }

	public function __construct() {
		$this->options = new Doctrine\Common\Collections\ArrayCollection;
        $this->setArticleTypes(Model_Article::$articleTypes);
        $this->setGroupArticleTypes(array());
	}

	public function __toString() {
		return $this->title;
	}
}