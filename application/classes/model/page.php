<?php

/**
 * @Entity
 * @Table(name="pages")
 */
class Model_Page extends Model_Base {
	/**
    * @Id
    * @Column(type="integer")
    * @GeneratedValue
    */
	protected $id = null;

	/**
	 * @Column(type="text", name="title", unique=false, nullable=false)
	 */
	protected $title = null;

	/**
	 * @Column(type="text", name="short_title", unique=false, nullable=false)
	 */
	protected $shortTitle = null;

	/**
	 * @Column(type="text", name="content", unique=false, nullable=false)
	 */
	protected $content = null;

	/**
	 * @Column(type="string", name="type", length=20, unique=true, nullable=false)
	 */
	protected $type = null;

    /**
   	 * @Column(type="array", name="fields", unique=false, nullable=true)
   	 */
   	protected $fields = null;

    /**
   	 * @Column(type="array", name="fields_wysiwyg", unique=false, nullable=true)
   	 */
   	protected $fieldsWYSIWYG = null;

    /**
   	 * @Column(type="boolean", name="show_in_menu", unique=false)
   	 */
   	protected $showInMenu = false;

    /**
   	 * @Column(type="boolean", name="active", unique=false)
   	 */
   	protected $active = true;

    function getFields() {
        return $this->fields ? $this->fields : array();
    }

    function setFields($fields) {
        $this->fields = $fields;
    }

    function getField($field) {
        $fields = $this->getFields();
        return isset($fields[$field]) ? $fields[$field] : null;
    }

    function getWYSIWYGField($field) {
        $fields = $this->fieldsWYSIWYG;
        return isset($fields[$field]) ? $fields[$field] : null;
    }
}