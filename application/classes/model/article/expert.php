<?php

/**
 * @Entity
 */
class Model_Article_Expert extends Model_Article {

	/**
	 * @Column(type="string", name="email")
	 */
	protected $email = '';

	/**
	 * @Column(type="string", name="phone")
	 */
	protected $phone = '';

	/**
	 * @Column(type="string", name="fax")
	 */
	protected $fax = '';

	/**
	 * @Column(type="text", name="institution")
	 */
	protected $institution = '';

    public function setInstitution($institution) {
        $this->title = $institution;
        $this->institution = $institution;
    }

	/**
	 * @Column(type="text", name="address")
	 */
	protected $address = '';

    /**
     * Unused field to allow formatting of contact information
     * @see application/data/fieldconfig.yaml
     * @see application/views/article/show/expertcontact.html
     */
    protected $expertcontact = '';

	/**
	 * @Column(type="text", name="city")
	 */
	protected $city = '';

	/**
	 * @Column(type="text", name="zip")
	 */
	protected $zip = '';

	/**
	 * @Column(type="text", name="description_institution")
	 */
	protected $description_institution = '';

	/**
	 * @Column(type="text", name="short_description_expert")
	 */
	protected $short_description_expert = '';

    /**
     * @Column(type="text", name="firstname")
     */
    protected $firstname = '';

    /**
     * @Column(type="text", name="lastname")
     */
    protected $lastname = '';

    public function setLastname($lastname) {
        $this->title = $lastname;
        $this->lastname = $lastname;
    }

    public function getTitle() {
    	return $this->getListtitle();
    }

    public function getListtitle() {
    	$name = "";
    	if($this->lastname != "") {
    		if($this->firstname != "") {
    			$name .= $this->firstname . " ";
    		}
    		$name .= $this->lastname;
    	}
    	if($this->institution != "") {
    		if($name != "") {
    			$name .= ", ";
    		}
    		$name .= $this->institution;
    	}
    	return $name;
    }

    public function description() {
    	return $this->short_description_expert;
    }

    public function canLink() {
    	return array(
    		Model_Article::CaseStudy,
    		Model_Article::ParticipationMethod,
    		Model_Article::ParticipationInstrument,
    		Model_Article::QuestionAndAnswer
    		);
    }
}