<?php

/**
 * @Entity
 * @Table(name="partnerlinks")
 */
class Model_Partnerlink extends Model_Base {
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
   	 * @Column(type="text", name="content", unique=false, nullable=false)
   	 */
   	protected $content = null;
}