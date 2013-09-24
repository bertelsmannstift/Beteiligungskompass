<?php

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class Model_Article_Study extends Model_Article {

	/**
	 * @Column(type="string", name="city", length=255, unique=false, nullable=true)
	 */
	protected $city = null;

	/**
	 * @Column(type="string", name="projectstatus", length=255, unique=false, nullable=true)
	 */
	protected $projectstatus = null;

	public function getProjectstatus() {
		foreach($this->criteria as $option) {
			if($option->criterion->discriminator == "projectstatus" && !$option->default) {
				return $option->title;
			}
		}
		return "";
	}

	/**
	 * @Column(type="integer", name="start_month", unique=false, nullable=true)
	 */
	protected $start_month = null;

	/**
	 * @Column(type="integer", name="start_year", unique=false, nullable=true)
	 */
	protected $start_year = null;

	/**
	 * @Column(type="integer", name="end_month", unique=false, nullable=true)
	 */
	protected $end_month = null;

	/**
	 * @Column(type="integer", name="end_year", unique=false, nullable=true)
	 */
	protected $end_year = null;

	/**
	 * @Column(type="text", name="short_description")
	 */
	protected $short_description = '';

	/**
	 * @Column(type="text", name="background")
	 */
	protected $background = '';

	/**
	 * @Column(type="text", name="aim")
	 */
	protected $aim = '';

	/**
	 * @Column(type="text", name="process")
	 */
	protected $process = '';

	/**
	 * @Column(type="text", name="results")
	 */
	protected $results = '';

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
    		Model_Article::ParticipationMethod,
    		Model_Article::ParticipationInstrument
    	);
   	}

}