<?php

/**
 * @Entity
 */
class Model_Article_Event extends Model_Article {

    /**
   	 * @Column(type="datetime", name="start_date", unique=false, nullable=true)
   	 */
   	protected $start_date = null;

    /**
   	 * @Column(type="datetime", name="end_date", unique=false, nullable=true)
   	 */
   	protected $end_date = null;

    /**
   	 * @Column(type="date", name="deadline", unique=false, nullable=true)
   	 */
   	protected $deadline = null;

    /**
   	 * @Column(type="string", length=255, name="zip", unique=false, nullable=true)
   	 */
   	protected $zip = null;

    /**
   	 * @Column(type="string", length=255, name="city", unique=false, nullable=true)
   	 */
   	protected $city = null;

    /**
   	 * @Column(type="string", length=255, name="street", unique=false, nullable=true)
   	 */
   	protected $street = null;

    /**
   	 * @Column(type="string", length=255, name="street_nr", unique=false, nullable=true)
   	 */
   	protected $street_nr = null;

    /**
   	 * @Column(type="string", length=255, name="organized_by", unique=false, nullable=true)
   	 */
   	protected $organized_by = null;

    /**
   	 * @Column(type="string", length=255, name="participation", unique=false, nullable=true)
   	 */
   	protected $participation = null;

    /**
   	 * @Column(type="string", length=255, name="link", unique=false, nullable=true)
   	 */
   	protected $link = null;

    /**
   	 * @Column(type="string", length=255, name="email", unique=false, nullable=true)
   	 */
   	protected $email = null;

    /**
   	 * @Column(type="string", length=255, name="phone", unique=false, nullable=true)
   	 */
   	protected $phone = null;

    /**
   	 * @Column(type="string", length=255, name="fax", unique=false, nullable=true)
   	 */
   	protected $fax = null;

    /**
   	 * @Column(type="string", length=255, name="venue", unique=false, nullable=true)
   	 */
   	protected $venue = null;

    /**
   	 * @Column(type="string", length=255, name="fee", unique=false, nullable=true)
   	 */
   	protected $fee = null;

    /**
   	 * @Column(type="string", length=255, name="number_of_participants", unique=false, nullable=true)
   	 */
   	protected $number_of_participants = null;

    /**
   	 * @Column(type="string", length=255, name="contact_person", unique=false, nullable=true)
   	 */
   	protected $contact_person = null;

    /**
   	 * @Column(type="text", name="description")
   	 */
   	protected $description = '';

    function getDate() {
        return $this->start_date;
    }

    function setStart_date($val) {
        $date = $val;
	    if(gettype($val) != 'object' || get_class($val) != 'DateTime') {
            $ts = strtotime($val);
            if($ts == false) {
                return false;
            }
            $date = new DateTime();
            $date->setTimestamp($ts);
        }

        $this->start_date = $date;
    }

    function setDeadline($val) {
        $date = $val;
	    if(gettype($val) != 'object' || get_class($val) != 'DateTime') {
            $ts = strtotime($val);
            if($ts == false) {
                return false;
            }
            $date = new DateTime();
            $date->setTimestamp($ts);
        }

        $this->deadline = $date;
    }

    function setEnd_date($val) {
        $date = $val;
        if(gettype($val) != 'object' || get_class($val) != 'DateTime') {
            $ts = strtotime($val);
            if($ts == false) {
                return false;
            }
            $date = new DateTime();
            $date->setTimestamp($ts);
        }

        $this->end_date = $date;
    }

	public function description() {
		return $this->description;
	}

    public function getParticipation() {
   		$list = Helper_Article::getParticipationList();
   		return $list[$this->participation];
   	}

	public function canLink() {
    	return array(
    		Model_Article::CaseStudy,
    		Model_Article::ParticipationMethod,
    		Model_Article::ParticipationInstrument,
    		Model_Article::QuestionAndAnswer,
    		Model_Article::News
    	);
   	}
}