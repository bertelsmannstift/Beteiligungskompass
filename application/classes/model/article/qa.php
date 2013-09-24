<?php

/**
 * @Entity
 */
class Model_Article_Qa extends Model_Article {
	/**
	 * @Column(type="text", name="question")
	 */
	protected $question = '';

	/**
	 * @Column(type="text", name="answer")
	 */
	protected $answer = '';

	/**
	 * @Column(type="text", name="author_answer")
	 */
	protected $author_answer = '';

	/**
	 * @Column(type="text", name="publisher")
	 */
	protected $publisher = '';

	/**
	 * @Column(type="integer", name="year")
	 */
	protected $year = '';

	public function description() {
		return $this->question;
	}

	public function canLink() {
    	return array(
    		Model_Article::CaseStudy,
    		Model_Article::ParticipationMethod,
    		Model_Article::ParticipationInstrument
    	);
   	}
}