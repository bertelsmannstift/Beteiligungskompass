<?php

/**
 * @Entity
 */
class Model_Article_News extends Model_Article {

	/**
	 * @Column(type="text", name="subtitle")
	 */
	protected $subtitle = '';

	/**
	 * @Column(type="text", name="author")
	 */
	protected $author = '';

	/**
	 * @Column(type="text", name="intro")
	 */
	protected $intro = '';

	/**
	 * @Column(type="text", name="text")
	 */
	protected $text = '';

    /**
   	 * @Column(type="date", name="date", unique=false, nullable=true)
   	 */
   	protected $date = null;

	public function description() {
		$listdescription = "";

		if(!empty($this->subtitle)) {
			$listdescription = $this->subtitle;
		} elseif (!empty($this->intro)) {
			$listdescription = $this->intro;
		} elseif (!empty($this->text)) {
			$listdescription = $this->text;
		}

		return $listdescription;
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