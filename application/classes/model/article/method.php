<?php

/**
 * @Entity
 */
class Model_Article_Method extends Model_Article {
	/**
	 * @Column(type="text", name="description")
	 */
	protected $description = '';

	/**
	 * @Column(type="text", name="process")
	 */
	protected $process = '';

	/**
	 * @Column(type="text", name="used_for")
	 */
	protected $used_for = '';

	/**
	 * @Column(type="text", name="participants", unique=false)
	 */
	protected $participants = '';

	/**
	 * @Column(type="text", name="costs", unique=false)
	 */
	protected $costs = '';

	/**
	 * @Column(type="text", name="time_expense", unique=false)
	 */
	protected $time_expense = '';

	/**
	 * @Column(type="text", name="when_to_use")
	 */
	protected $when_to_use = '';

	/**
	 * @Column(type="text", name="when_not_to_use")
	 */
	protected $when_not_to_use = '';

	/**
	 * @Column(type="text", name="strengths")
	 */
	protected $strengths = '';

	/**
	 * @Column(type="text", name="weaknesses")
	 */
	protected $weaknesses = '';

	/**
	 * @Column(type="text", name="origin")
	 */
	protected $origin = '';

	/**
	 * @Column(type="text", name="restrictions")
	 */
	protected $restrictions = '';

	/**
	 * @Column(type="text", name="contact")
	 */
	protected $contact = '';

	public function description() {
		if($this->short_description) return $this->short_description;
		return null;
	}

	public function canLink() {
    	return array(
    		Model_Article::CaseStudy,
    		Model_Article::ParticipationMethod
    	);
   	}

}