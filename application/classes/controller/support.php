<?php

class Controller_Support extends Controller {

	private $casestudiesFile = "data/involve_casestudies.xml";
	private $methodsFile = "data/involve_methods.xml";

	function action_updatearticles() {
	    $articles = Doctrine::instance()->getRepository('Model_Article')->findAll();
	    $selects = array('country', 'projectstatus');
	    foreach($articles as $article) {

	        $fields = array();

	        foreach($article->criteria as $c) {
	            foreach($selects as $field) {
	                if($c->criterion->discriminator == $field) {
	                    $fields[] = $field;
	                }
	            }
	        }

	        $fields = array_diff($selects, $fields);

	        if(count($fields) > 0) {
	            foreach($fields as $field) {
	                try {
	                    $defaultOpt = Doctrine::instance()
	                                ->createQueryBuilder()
	                                ->select('c')
	                                ->from('Model_Criterion_Option', 'c')
	                                ->join('c.criterion', 'cr')
	                                ->where("cr.discriminator = '{$field}' AND c.default = 1")
	                                ->getQuery()
	                                ->getSingleResult();

	                    $article->criteria->add($defaultOpt);
	                    Doctrine::instance()->persist($article);
	                    Doctrine::instance()->flush();
	                } catch(Exception $e) {
	                    echo $e->getMessage();
	                    die('Default field not found:'.$field);
	                }
	            }
	        }
	    }
	    die("updated all articles");
	}

	public function action_insertcriteria() {
		$data = Yaml::loadFile(APPPATH . 'data/testcriteria.yaml');
		$em = Doctrine::instance();
		foreach($data as $crit => $options) {
			$criterion = new Model_Criterion();
			$criterion->title = $crit;

			foreach($options as $opt) {
				$option = new Model_Criterion_Option();
				$option->title = $opt;
				$criterion->options->add($option);
				$option->criterion = $criterion;
			}

			$em->persist($criterion);
		}

		$em->flush();
	}

	public function action_import() {
				set_time_limit(60 * 60);
		$connect_to_db = @mysql_connect("localhost:3306", "root", "root");
		$select_db = @mysql_select_db("involve");
		mysql_set_charset("UTF8");

		$result = mysql_query("
			SELECT c.*, e.*
			FROM CONTENT c

			left join OS_PROPERTYENTRY e on c.contentid = e.entity_id

			inner join (
				select entity_id, max(replace(entity_key, '~metadata.', '') + 0) as v
				from OS_PROPERTYENTRY
				group by entity_id
			) as b
			ON e.entity_id = b.entity_id AND (replace(e.entity_key, '~metadata.', '') + 0) = b.v

			WHERE c.SPACEID = 1310721
			AND c.content_status = 'current'"
		);

		$em = Doctrine::instance();
		$user = $em->getRepository('Model_User')->findOneById(1);

		$caseStudies = array();
		$caseStudiesToMethod = array();

		while($row = mysql_fetch_array($result)) {
			if($row['entity_key'] == 'scaffolding') continue;

			$item = new Model_Article_Study();
			$ids = array();
			$xml = simplexml_load_string($row['text_val']);
			foreach($xml->entry as $entry) {
				$type = (string) $entry->string[0];
				switch($type) {
					// Title
					case 'CaseStudyProjectName':
						$item->title = $this->stripXml($entry->string);
						break;
					// Country
					case 'ProjectLocation':
						$lang = (string) $entry->WikiReference->nonWiki;
						if(!$lang) continue;
						switch(strtolower($lang)) {
							case 'england':
							case 'united kingdom':
							case 'wales':
							case 'scotland':
							case 'northern ireland':
								$item->country = 'uk';
								break;
							case 'australia':
								$item->country = 'au';
								break;
							case 'malaysia':
								$item->country = 'ml';
								break;
							case 'abkhazia':
								$item->country = 'ab';
								break;
							case 'andorra':
								$item->country = 'an';
								break;
							case 'austria':
								$item->country = 'at';
								break;
							case 'united states of america':
								$item->country = 'us';
								break;
							case 'finland';
								$item->country = 'fi';
								break;
							case 'france':
								$item->country = 'fr';
								break;
							case '-----':
								break;
							case 'india':
								$item->country = 'in';
								break;
							default:
								die($lang);
						}
						break;
					// start mont / year
					case 'ProjectStartDate':
						$date = $entry->date;
						if(!$date) continue;
						$date = strtotime($date);
						$item->start_month = date('n');
						$item->start_year = date('Y');
						break;
					// end month / year
					case 'ProjectEndDate':
						$date = $entry->date;
						if(!$date) continue;
						$date = strtotime($date);
						$item->end_month = date('n');
						$item->end_year = date('Y');
						break;
					// Short description
					case 'CaseStudyProjectDescription':
						$item->short_description = $this->stripXml($entry->string);
						break;
					// background
					case 'BackgroundToProject':
						$item->background = $this->stripXml($entry->string);
						break;
					// aim
					case 'PurposeOfProject':
						$item->aim = $this->stripXml($entry->string);
						break;
					// process
					case 'ProjectActivities':
						$item->process = $this->stripXml($entry->string);
						break;
					// results
					case 'ProjectResults':
						$item->results = $this->stripXml($entry->string);
						break;
					// contact
					case 'ProjectPosterName':
					case 'ProjectPosterDescription':
						$contact = array();
						$contact[] = $this->stripXml($entry->string);
						if($item->contact) $contact[] = $item->contact;
						$item->contact = implode("\n", $contact);
						break;
					// linked articles
					case 'MethodUsed':
						$ids = array_merge($ids, $this->getReferenceFromXml($entry));
						break;
					// more_information
					case 'MoreInformation':
						$item->more_information = $this->stripXml($entry->string);
						break;
					default:
						die($type);
				}
			}

			$item->title = $row['TITLE'];

			if(!$item->title) {
				//$item->title = 'No title found in import data';
				continue;
			}

			if(count($ids)) {
				$caseStudiesToMethod[$row['entity_id']] = $ids;
			}

			$item->involveid = $row['entity_id'];

			$item->ready_for_publish = true;
			$item->active = true;
			$item->user = $user;

			$em->persist($item);
			$em->flush();

			echo "Added case study {$item->involveid} {$item->title}<br />";
		}

		$methodToMethod = array();
		$methodToStudy = array();

		$result = mysql_query("
			SELECT a.*,  (replace(a.entity_key, '~metadata.', '') + 0) as version from OS_PROPERTYENTRY as a
			inner join (
				select entity_id, max(replace(entity_key, '~metadata.', '') + 0) as v
				from OS_PROPERTYENTRY
				group by entity_id
			) as b
			ON a.entity_id = b.entity_id AND (replace(entity_key, '~metadata.', '') + 0) = b.v 
			WHERE a.text_val LIKE '%MethodName%'
			group by a.entity_id
			order by version desc"
		);

		while($row = mysql_fetch_array($result)) {

			$item = new Model_Article_Method();
			echo "Added new method {$row['entity_id']} <br/>";

			$methodIds = array();
			$caseStudyIds = array();

			$xml = simplexml_load_string($row['text_val']);
			foreach($xml->entry as $entry) {
				$type = (string) $entry->string[0];
				if($type == 'Scaffold') continue 2;
				switch($type) {
					//title
					case 'MethodName':
						$item->title = $this->stripXml($entry->string);
						break;
        			//short_description
        			case 'BriefDescription':
        				$item->short_description = $this->stripXml($entry->string);
        				break;
        			// description
        			case 'Description';
        				$item->description = $this->stripXml($entry->string);
        				break;
          			//used_for
          			case 'Used For':
          				$item->used_for = $this->stripXml($entry->string);
						break;
          			//participants
          			case 'SuitableParticipants';
          				$item->participants = $this->stripXml($entry->string);
						break;
          			//costs
          			case 'Cost':
          				$item->costs = $this->stripXml($entry->string);
          				break;
          			//time_expense
          			case 'TimeRequirements';
          				$item->time_expense = $this->stripXml($entry->string);
          				break;
          			//when_to_use
          			case 'WhenToUseOrWhatItCanDeliver';
          				$item->when_to_use = $this->stripXml($entry->string);
          				break;
          			//when_not_to_use
          			case 'WhenNoToUseOrWhatItCantDeliver';
          				$item->when_not_to_use = $this->stripXml($entry->string);
          				break;
          			//strengths
          			case 'Strengths';
          				$item->strengths = $this->stripXml($entry->string);
          				break;
          			//weaknesses
          			case 'Weaknesses';
          				$item->weaknesses = $this->stripXml($entry->string);
          				break;
          			//origin
          			case 'Origin':
          				$item->origin = $this->stripXml($entry->string);
          				break;
          			//restrictions
          			case 'Restrictionsinuse';
          				$item->restrictions = $this->stripXml($entry->string);
          				break;
          			// contact
          			case 'ContactDetails';
          				$item->contact = $this->stripXml($entry->string);
          				break;
          			// linked articles
          			case 'CaseStudies';
          				$caseStudyIds = array_merge($caseStudyIds, $this->getReferenceFromXml($entry));
          				break;
          			case 'MethodUsed';
          				$methodIds = array_merge($methodIds, $this->getReferenceFromXml($entry));
          				break;
          			// more_information
          			case 'FurtherInformation';
					case 'Furtherinformation';
						$text = array();
						$text[] = $this->stripXml($entry->string);
						if($item->more_information) $text[] = $item->more_information;
						$item->more_information = implode("\n", $text);
						break;
          			// process
					default:
						//var_dump($type);
				}
			}

			if(!$item->title) {
				$item->title = 'No title found in import data';
			}

			if(count($methodIds)) {
				$methodToMethod[$row['entity_id']] = $methodIds;
			}

			if(count($caseStudyIds)) {
				$methodToStudy[$row['entity_id']] = $caseStudyIds;
			}

			$item->involveid = $row['entity_id'];

			$item->ready_for_publish = true;
			$item->active = true;
			$item->user = $user;

			$em->persist($item);
			$em->flush();

			echo "Added case study {$item->involveid} {$item->title}<br />";
		}

	}

	public function action_import2() {
		set_time_limit(60 * 60);
		$connect_to_db = @mysql_connect("localhost:3306", "root", "root");
		$select_db = @mysql_select_db("involve");
		mysql_set_charset("UTF8");

		$result = mysql_query("
			SELECT c.*, e.*
			FROM CONTENT c

			left join OS_PROPERTYENTRY e on c.contentid = e.entity_id

			inner join (
				select entity_id, max(replace(entity_key, '~metadata.', '') + 0) as v
				from OS_PROPERTYENTRY
				group by entity_id
			) as b
			ON e.entity_id = b.entity_id AND (replace(e.entity_key, '~metadata.', '') + 0) = b.v

			WHERE c.SPACEID = 1310721
			AND c.content_status = 'current'"
		);

		$em = Doctrine::instance();

		$caseStudies = array();
		$caseStudiesToMethod = array();

		while($row = mysql_fetch_array($result)) {
			if($row['entity_key'] == 'scaffolding') continue;

			if($item = $em->getRepository('Model_Article')->findOneByInvolveid($row['entity_id'])) {
				echo "Update case study {$row['entity_id']} {$item->title} <br />";
				$ids = array();
				$xml = simplexml_load_string($row['text_val']);
				foreach($xml->entry as $entry) {
					$type = (string) $entry->string[0];
					switch($type) {
						case 'MethodUsed':
							$ids = array_merge($ids, $this->getReferenceFromXml($entry));
							break;
						default:
							continue;
					}
				}

				if(count($ids)) {
					$caseStudiesToMethod[$row['entity_id']] = $ids;
				}
			} else {
				$item = new Model_Article_Study();
				echo "Added new case study {$row['entity_id']} <br/>";

				$ids = array();
				$xml = simplexml_load_string($row['text_val']);
				foreach($xml->entry as $entry) {
					$type = (string) $entry->string[0];
					switch($type) {
						// Title
						case 'CaseStudyProjectName':
							$item->title = $this->stripXml($entry->string);
							break;
						// Country
						case 'ProjectLocation':
							$lang = (string) $entry->WikiReference->nonWiki;
							if(!$lang) continue;
							switch(strtolower($lang)) {
								case 'england':
								case 'united kingdom':
								case 'wales':
								case 'scotland':
								case 'northern ireland':
									$item->country = 'uk';
									break;
								case 'australia':
									$item->country = 'au';
									break;
								case 'malaysia':
									$item->country = 'ml';
									break;
								case 'abkhazia':
									$item->country = 'ab';
									break;
								case 'andorra':
									$item->country = 'an';
									break;
								case 'austria':
									$item->country = 'at';
									break;
								case 'united states of america':
									$item->country = 'us';
									break;
								case 'finland';
									$item->country = 'fi';
									break;
								case 'france':
									$item->country = 'fr';
									break;
								case '-----':
									break;
								case 'india':
									$item->country = 'in';
									break;
								default:
									die($lang);
							}
							break;
						// start mont / year
						case 'ProjectStartDate':
							$date = $entry->date;
							if(!$date) continue;
							$date = strtotime($date);
							$item->start_month = date('n');
							$item->start_year = date('Y');
							break;
						// end month / year
						case 'ProjectEndDate':
							$date = $entry->date;
							if(!$date) continue;
							$date = strtotime($date);
							$item->end_month = date('n');
							$item->end_year = date('Y');
							break;
						// Short description
						case 'CaseStudyProjectDescription':
							$item->short_description = $this->stripXml($entry->string);
							break;
						// background
						case 'BackgroundToProject':
							$item->background = $this->stripXml($entry->string);
							break;
						// aim
						case 'PurposeOfProject':
							$item->aim = $this->stripXml($entry->string);
							break;
						// process
						case 'ProjectActivities':
							$item->process = $this->stripXml($entry->string);
							break;
						// results
						case 'ProjectResults':
							$item->results = $this->stripXml($entry->string);
							break;
						// contact
						case 'ProjectPosterName':
						case 'ProjectPosterDescription':
							$contact = array();
							$contact[] = $this->stripXml($entry->string);
							if($item->contact) $contact[] = $item->contact;
							$item->contact = implode("\n", $contact);
							break;
						// linked articles
						case 'MethodUsed':
							$ids = array_merge($ids, $this->getReferenceFromXml($entry));
							break;
						// more_information
						case 'MoreInformation':
							$item->more_information = $this->stripXml($entry->string);
							break;
						default:
							die($type);
					}
				}

				if(!$item->title) {
					$item->title = 'No title found in import data';
				}

				if(count($ids)) {
					$caseStudiesToMethod[$row['entity_id']] = $ids;
				}

				$item->involveid = $row['entity_id'];

				$caseStudies[$row['entity_id']] = $item;
			}

			$item->title = $row['TITLE'];

			$em->persist($item);
			$em->flush();
		}

		foreach($caseStudiesToMethod as $itemId => $refIds) {
			if(!$item1 = $em->getRepository('Model_Article')->findOneByInvolveid($itemId)) {
				echo sprintf("can't find case study with id %s<br />", $itemId);
				continue;
			} else {
				foreach($refIds as $refId) {
					if(!$item2 = $em->getRepository('Model_Article')->findOneByInvolveid($refId)) {
						echo sprintf("can't find method with id %s<br />", $refId);
						continue;
					} else {
						if(!$item1->linked_articles->contains($item2)) {
							$item1->linked_articles->add($item2);
						}

						if(!$item2->linked_articles->contains($item1)) {
							$item2->linked_articles->add($item1);
						}
					}

					$em->persist($item1);
					$em->persist($item2);
					$em->flush();
					echo "Linked {$item1->type()} {$item1->title} > {$item2->type()} {$item2->title}<br />";
				}
			}
		}
	}

	public function action_updateimport() {

		set_time_limit(60 * 60);
		$connect_to_db = @mysql_connect("localhost:3306", "root", "root");
		$select_db = @mysql_select_db("involve");
		mysql_set_charset("UTF8");

		$result = mysql_query("
			SELECT a.*,  (replace(a.entity_key, '~metadata.', '') + 0) as version from OS_PROPERTYENTRY as a
			inner join (
				select entity_id, max(replace(entity_key, '~metadata.', '') + 0) as v
				from OS_PROPERTYENTRY
				group by entity_id
			) as b
			ON a.entity_id = b.entity_id AND (replace(entity_key, '~metadata.', '') + 0) = b.v 
			WHERE a.text_val LIKE '%CaseStudyProjectName%'
			group by a.entity_id
			order by version desc"
		);

		$em = Doctrine::instance();

		$caseStudies = array();
		$caseStudiesToMethod = array();

		while($row = mysql_fetch_array($result)) {
			if($item = $em->getRepository('Model_Article')->findOneByInvolveid($row['entity_id'])) {
				if($item->updated->getTimestamp() > ($item->created->getTimestamp() + 10)) {
					echo "Skiped update of case study {$row['entity_id']} {$item->title} <br />";
					continue;
				} else {
					echo "Update case study {$row['entity_id']} {$item->title} <br />";
				}
			} else {
				$item = new Model_Article_Study();
				echo "Added new case study {$row['entity_id']} <br/>";

			}

			$ids = array();
			$xml = simplexml_load_string($row['text_val']);
			foreach($xml->entry as $entry) {
				$type = (string) $entry->string[0];
				switch($type) {
					// Title
					case 'CaseStudyProjectName':
						$item->title = $this->stripXml($entry->string);
						break;
					// Country
					case 'ProjectLocation':
						$lang = (string) $entry->WikiReference->nonWiki;
						if(!$lang) continue;
						switch(strtolower($lang)) {
							case 'england':
							case 'united kingdom':
							case 'wales':
							case 'scotland':
								$item->country = 'uk';
								break;
							case 'australia':
								$item->country = 'au';
								break;
							case 'malaysia':
								$item->country = 'ml';
								break;
							case 'abkhazia':
								$item->country = 'ab';
								break;
							case 'andorra':
								$item->country = 'an';
								break;
							case 'austria':
								$item->country = 'at';
								break;
							case 'united states of america':
								$item->country = 'us';
								break;
							default:
								die($lang);
						}
						break;
					// start mont / year
					case 'ProjectStartDate':
						$date = $entry->date;
						if(!$date) continue;
						$date = strtotime($date);
						$item->start_month = date('n');
						$item->start_year = date('Y');
						break;
					// end month / year
					case 'ProjectEndDate':
						$date = $entry->date;
						if(!$date) continue;
						$date = strtotime($date);
						$item->end_month = date('n');
						$item->end_year = date('Y');
						break;
					// Short description
					case 'CaseStudyProjectDescription':
						$item->short_description = $this->stripXml($entry->string);
						break;
					// background
					case 'BackgroundToProject':
						$item->background = $this->stripXml($entry->string);
						break;
					// aim
					case 'PurposeOfProject':
						$item->aim = $this->stripXml($entry->string);
						break;
					// process
					case 'ProjectActivities':
						$item->process = $this->stripXml($entry->string);
						break;
					// results
					case 'ProjectResults':
						$item->results = $this->stripXml($entry->string);
						break;
					// contact
					case 'ProjectPosterName':
					case 'ProjectPosterDescription':
						$contact = array();
						$contact[] = $this->stripXml($entry->string);
						if($item->contact) $contact[] = $item->contact;
						$item->contact = implode("\n", $contact);
						break;
					// linked articles
					case 'MethodUsed':
						$ids = array_merge($ids, $this->getReferenceFromXml($entry));
						break;
					// more_information
					case 'MoreInformation':
						$item->more_information = $this->stripXml($entry->string);
						break;
					default:
						die($type);
				}
			}

			if(!$item->title) {
				continue;
				$item->title = 'No title found in import data';
			}

			if(count($ids)) {
				$caseStudiesToMethod[$row['entity_id']] = $ids;
			}

			$item->involveid = $row['entity_id'];

			$caseStudies[$row['entity_id']] = $item;
		}

		$user = $em->getRepository('Model_User')->findOneById(1);

		$i = 0;

		foreach($caseStudies as &$s) {
			$i++;
			$s->ready_for_publish = true;
			$s->active = true;
			$s->user = $user;
			$em->persist($s);

			if($i % 10 == 0) {
				$em->flush();
			}
		}

		$em->flush();

		echo sprintf("finished, %s case studies imported<br />", count($caseStudies));

		$methods = array();
		$methodToMethod = array();
		$methodToStudy = array();

		$result = mysql_query("
			SELECT a.*,  (replace(a.entity_key, '~metadata.', '') + 0) as version from OS_PROPERTYENTRY as a
			inner join (
				select entity_id, max(replace(entity_key, '~metadata.', '') + 0) as v
				from OS_PROPERTYENTRY
				group by entity_id
			) as b
			ON a.entity_id = b.entity_id AND (replace(entity_key, '~metadata.', '') + 0) = b.v 
			WHERE a.text_val LIKE '%MethodName%'
			group by a.entity_id
			order by version desc"
		);

		while($row = mysql_fetch_array($result)) {

			if($item = $em->getRepository('Model_Article')->findOneByInvolveid($row['entity_id'])) {
				if($item->updated->getTimestamp() > ($item->created->getTimestamp() + 10)) {
					echo "Skiped update of method {$row['entity_id']} {$item->title} <br />";
					continue;
				} else {
					echo "Update method {$row['entity_id']} {$item->title} <br />";
				}
			} else {
				$item = new Model_Article_Method();
				echo "Added new method {$row['entity_id']} <br/>";
			}

			$methodIds = array();
			$caseStudyIds = array();

			$xml = simplexml_load_string($row['text_val']);
			foreach($xml->entry as $entry) {
				$type = (string) $entry->string[0];
				if($type == 'Scaffold') continue 2;
				switch($type) {
					//title
					case 'MethodName':
						$item->title = $this->stripXml($entry->string);
						break;
        			//short_description
        			case 'BriefDescription':
        				$item->short_description = $this->stripXml($entry->string);
        				break;
        			// description
        			case 'Description';
        				$item->description = $this->stripXml($entry->string);
        				break;
          			//used_for
          			case 'Used For':
          				$item->used_for = $this->stripXml($entry->string);
						break;
          			//participants
          			case 'SuitableParticipants';
          				$item->participants = $this->stripXml($entry->string);
						break;
          			//costs
          			case 'Cost':
          				$item->costs = $this->stripXml($entry->string);
          				break;
          			//time_expense
          			case 'TimeRequirements';
          				$item->time_expense = $this->stripXml($entry->string);
          				break;
          			//when_to_use
          			case 'WhenToUseOrWhatItCanDeliver';
          				$item->when_to_use = $this->stripXml($entry->string);
          				break;
          			//when_not_to_use
          			case 'WhenNoToUseOrWhatItCantDeliver';
          				$item->when_not_to_use = $this->stripXml($entry->string);
          				break;
          			//strengths
          			case 'Strengths';
          				$item->strengths = $this->stripXml($entry->string);
          				break;
          			//weaknesses
          			case 'Weaknesses';
          				$item->weaknesses = $this->stripXml($entry->string);
          				break;
          			//origin
          			case 'Origin':
          				$item->origin = $this->stripXml($entry->string);
          				break;
          			//restrictions
          			case 'Restrictionsinuse';
          				$item->restrictions = $this->stripXml($entry->string);
          				break;
          			// contact
          			case 'ContactDetails';
          				$item->contact = $this->stripXml($entry->string);
          				break;
          			// linked articles
          			case 'CaseStudies';
          				$caseStudyIds = array_merge($caseStudyIds, $this->getReferenceFromXml($entry));
          				break;
          			case 'MethodUsed';
          				$methodIds = array_merge($methodIds, $this->getReferenceFromXml($entry));
          				break;
          			// more_information
          			case 'FurtherInformation';
					case 'Furtherinformation';
						$text = array();
						$text[] = $this->stripXml($entry->string);
						if($item->more_information) $text[] = $item->more_information;
						$item->more_information = implode("\n", $text);
						break;
          			// process
					default:
						//var_dump($type);
				}
			}
			if(!$item->title) {
				$item->title = 'No title found in import data';
			}

			if(count($methodIds)) {
				$methodToMethod[$row['entity_id']] = $methodIds;
			}

			if(count($caseStudyIds)) {
				$methodToStudy[$row['entity_id']] = $caseStudyIds;
			}

			$item->involveid = $row['entity_id'];

			$methods[$row['entity_id']] = $item;
		}

		$em = Doctrine::instance();
		$user = $em->getRepository('Model_User')->findOneById(1);

		$i = 0;

		foreach($methods as &$s) {
			$i++;
			$s->ready_for_publish = true;
			$s->active = true;
			$s->user = $user;
			$em->persist($s);

			if($i % 10 == 0) {
				$em->flush();
			}
		}

		$em->flush();

		echo sprintf("finished, %s methods imported<br />", count($methods));

		// References
		foreach($caseStudiesToMethod as $itemId => $refIds) {
			if(!$item1 = $em->getRepository('Model_Article')->findOneByInvolveid($itemId)) {
				echo sprintf("can't find case study with id %s<br />", $itemId);
				continue;
			} else {
				foreach($refIds as $refId) {
					if(!$item2 = $em->getRepository('Model_Article')->findOneByInvolveid($refId)) {
						echo sprintf("can't find method with id %s<br />", $refId);
						continue;
					} else {
						if(!$item1->linked_articles->contains($item2)) {
							$item1->linked_articles->add($item2);
						}

						if(!$item2->linked_articles->contains($item1)) {
							$item2->linked_articles->add($item1);
						}
					}

					$em->persist($item1);
					$em->persist($item2);
					$em->flush();
					echo "Linked {$item1->type()} {$item1->title} > {$item2->type()} {$item2->title}<br />";
				}
			}
		}

		foreach($methodToMethod as $itemId => $refIds) {
			if(!$item1 = $em->getRepository('Model_Article')->findOneByInvolveid($itemId)) {
				echo sprintf("can't find method with id %s<br />", $itemId);
				continue;
			} else {
				foreach($refIds as $refId) {
					if(!$item2 = $em->getRepository('Model_Article')->findOneByInvolveid($refId)) {
						echo sprintf("can't find method with id %s<br />", $refId);
						continue;
					} else {
						if(!$item1->linked_articles->contains($item2)) {
							$item1->linked_articles->add($item2);
						}

						if(!$item2->linked_articles->contains($item1)) {
							$item2->linked_articles->add($item1);
						}
					}

					$em->persist($item1);
					$em->persist($item2);
					$em->flush();
					echo "Linked {$item1->type()} {$item1->title} > {$item2->type()} {$item2->title}<br />";
				}
			}
		}

		foreach($methodToStudy as $itemId => $refIds) {
			if(!$item1 = $em->getRepository('Model_Article')->findOneByInvolveid($itemId)) {
				echo sprintf("can't find method with id %s<br />", $itemId);
				continue;
			} else {
				foreach($refIds as $refId) {
					if(!$item2 = $em->getRepository('Model_Article')->findOneByInvolveid($refId)) {
						echo sprintf("can't find case study with id %s<br />", $refId);
						continue;
					} else {
						if(!$item1->linked_articles->contains($item2)) {
							$item1->linked_articles->add($item2);
						}

						if(!$item2->linked_articles->contains($item1)) {
							$item2->linked_articles->add($item1);
						}
					}

					$em->persist($item1);
					$em->persist($item2);
					$em->flush();
					echo "Linked {$item1->type()} {$item1->title} > {$item2->type()} {$item2->title}<br />";
				}
			}
		}
	}

	public function action_baseimport() {

		set_time_limit(60 * 60);
		$con = Doctrine::instance()->getConnection();
		$con->query("TRUNCATE TABLE criteria_options");
		$con->query("TRUNCATE TABLE criteria");
		$con->query("TRUNCATE TABLE articles_options");
		$con->query("TRUNCATE TABLE article_links");
		$con->query("TRUNCATE TABLE articles");

		$connect_to_db = @mysql_connect("localhost:3306", "root", "root");
		$select_db = @mysql_select_db("involve");
		mysql_set_charset("UTF8");

		// Case Studies
		$caseStudies = array();
		$caseStudiesToMethod = array();

		$result = mysql_query("SELECT * FROM `OS_PROPERTYENTRY` WHERE `text_val` LIKE '%CaseStudyProjectName%' GROUP BY `entity_id` order by `entity_key` DESC");
		while($row = mysql_fetch_array($result)) {
			$item = new Model_Article_Study();
			$ids = array();
			$xml = simplexml_load_string($row['text_val']);
			foreach($xml->entry as $entry) {
				$type = (string) $entry->string[0];
				switch($type) {
					// Title
					case 'CaseStudyProjectName':
						$item->title = $this->stripXml($entry->string);
						break;
					// Country
					case 'ProjectLocation':
						$lang = (string) $entry->WikiReference->nonWiki;
						if(!$lang) continue;
						switch(strtolower($lang)) {
							case 'england':
							case 'united kingdom':
							case 'wales':
							case 'scotland':
								$item->country = 'uk';
								break;
							case 'australia':
								$item->country = 'au';
								break;
							case 'malaysia':
								$item->country = 'ml';
								break;
							case 'abkhazia':
								$item->country = 'ab';
								break;
							case 'andorra':
								$item->country = 'an';
								break;
							case 'austria':
								$item->country = 'at';
								break;
							case 'united states of america':
								$item->country = 'us';
								break;
							default:
								die($lang);
						}
						break;
					// start mont / year
					case 'ProjectStartDate':
						$date = $entry->date;
						if(!$date) continue;
						$date = strtotime($date);
						$item->start_month = date('n');
						$item->start_year = date('Y');
						break;
					// end month / year
					case 'ProjectEndDate':
						$date = $entry->date;
						if(!$date) continue;
						$date = strtotime($date);
						$item->end_month = date('n');
						$item->end_year = date('Y');
						break;
					// Short description
					case 'CaseStudyProjectDescription':
						$item->short_description = $this->stripXml($entry->string);
						break;
					// background
					case 'BackgroundToProject':
						$item->background = $this->stripXml($entry->string);
						break;
					// aim
					case 'PurposeOfProject':
						$item->aim = $this->stripXml($entry->string);
						break;
					// process
					case 'ProjectActivities':
						$item->process = $this->stripXml($entry->string);
						break;
					// results
					case 'ProjectResults':
						$item->results = $this->stripXml($entry->string);
						break;
					// contact
					case 'ProjectPosterName':
					case 'ProjectPosterDescription':
						$contact = array();
						$contact[] = $this->stripXml($entry->string);
						if($item->contact) $contact[] = $item->contact;
						$item->contact = implode("\n", $contact);
						break;
					// linked articles
					case 'MethodUsed':
						$ids = array_merge($ids, $this->getReferenceFromXml($entry));
						break;
					// more_information
					case 'MoreInformation':
						$item->more_information = $this->stripXml($entry->string);
						break;
					default:
						die($type);
				}
			}

			if(!$item->title) {
				$item->title = 'No title found in import data';
			}

			if(count($ids)) {
				$caseStudiesToMethod[$row['entity_id']] = $ids;
			}

			$item->involveid = $row['entity_id'];

			$caseStudies[$row['entity_id']] = $item;


		}

		$em = Doctrine::instance();
		$user = $em->getRepository('Model_User')->findOneById(1);

		$i = 0;

		foreach($caseStudies as &$s) {
			$i++;
			$s->ready_for_publish = true;
			$s->active = true;
			$s->user = $user;
			$em->persist($s);

			if($i % 10 == 0) {
				$em->flush();
			}
		}

		$em->flush();

		echo sprintf("finished, %s case studies imported<br />", count($caseStudies));

		// Methods
		$methods = array();
		$methodToMethod = array();
		$methodToStudy = array();
		$result = mysql_query("SELECT * FROM `OS_PROPERTYENTRY` WHERE `text_val` LIKE '%MethodName%' GROUP BY `entity_id` order by `entity_key` DESC");
		while($row = mysql_fetch_array($result)) {
			$item = new Model_Article_Method();
			$methodIds = array();
			$caseStudyIds = array();

			$xml = simplexml_load_string($row['text_val']);
			foreach($xml->entry as $entry) {
				$type = (string) $entry->string[0];
				if($type == 'Scaffold') continue 2;
				switch($type) {
					//title
					case 'MethodName':
						$item->title = $this->stripXml($entry->string);
						break;
        			//short_description
        			case 'BriefDescription':
        				$item->short_description = $this->stripXml($entry->string);
        				break;
        			// description
        			case 'Description';
        				$item->description = $this->stripXml($entry->string);
        				break;
          			//used_for
          			case 'Used For':
          				$item->used_for = $this->stripXml($entry->string);
						break;
          			//participants
          			case 'SuitableParticipants';
          				$item->participants = $this->stripXml($entry->string);
						break;
          			//costs
          			case 'Cost':
          				$item->costs = $this->stripXml($entry->string);
          				break;
          			//time_expense
          			case 'TimeRequirements';
          				$item->time_expense = $this->stripXml($entry->string);
          				break;
          			//when_to_use
          			case 'WhenToUseOrWhatItCanDeliver';
          				$item->when_to_use = $this->stripXml($entry->string);
          				break;
          			//when_not_to_use
          			case 'WhenNoToUseOrWhatItCantDeliver';
          				$item->when_not_to_use = $this->stripXml($entry->string);
          				break;
          			//strengths
          			case 'Strengths';
          				$item->strengths = $this->stripXml($entry->string);
          				break;
          			//weaknesses
          			case 'Weaknesses';
          				$item->weaknesses = $this->stripXml($entry->string);
          				break;
          			//origin
          			case 'Origin':
          				$item->origin = $this->stripXml($entry->string);
          				break;
          			//restrictions
          			case 'Restrictionsinuse';
          				$item->restrictions = $this->stripXml($entry->string);
          				break;
          			// contact
          			case 'ContactDetails';
          				$item->contact = $this->stripXml($entry->string);
          				break;
          			// linked articles
          			case 'CaseStudies';
          				$caseStudyIds = array_merge($caseStudyIds, $this->getReferenceFromXml($entry));
          				break;
          			case 'MethodUsed';
          				$methodIds = array_merge($methodIds, $this->getReferenceFromXml($entry));
          				break;
          			// more_information
          			case 'FurtherInformation';
					case 'Furtherinformation';
						$text = array();
						$text[] = $this->stripXml($entry->string);
						if($item->more_information) $text[] = $item->more_information;
						$item->more_information = implode("\n", $text);
						break;
          			// process
					default:
						//var_dump($type);
				}
			}
			if(!$item->title) {
				$item->title = 'No title found in import data';
			}

			if(count($methodIds)) {
				$methodToMethod[$row['entity_id']] = $methodIds;
			}

			if(count($caseStudyIds)) {
				$methodToStudy[$row['entity_id']] = $caseStudyIds;
			}

			$item->involveid = $row['entity_id'];

			$methods[$row['entity_id']] = $item;
		}

		$em = Doctrine::instance();
		$user = $em->getRepository('Model_User')->findOneById(1);

		$i = 0;

		foreach($methods as &$s) {
			$i++;
			$s->ready_for_publish = true;
			$s->active = true;
			$s->user = $user;
			$em->persist($s);

			if($i % 10 == 0) {
				$em->flush();
			}
		}

		$em->flush();

		echo sprintf("finished, %s methods imported<br />", count($methods));

		// References
		foreach($caseStudiesToMethod as $itemId => $refIds) {
			if(!isset($caseStudies[$itemId])) {
				echo sprintf("can't find case study with id %s<br />", $itemId);
			} else {
				foreach($refIds as $refId) {
					if(!isset($methods[$refId])) {
						echo sprintf("can't find method with id %s<br />", $refId);
					} else {
						if(!$caseStudies[$itemId]->linked_articles->contains($methods[$refId])) {
							$caseStudies[$itemId]->linked_articles->add($methods[$refId]);
						}

						if(!$methods[$refId]->linked_articles->contains($caseStudies[$itemId])) {
							$methods[$refId]->linked_articles->add($caseStudies[$itemId]);
						}
					}
				}
			}
		}

		foreach($methodToMethod as $itemId => $refIds) {
			if(!isset($methods[$itemId])) {
				echo sprintf("can't find method with id %s<br />", $itemId);
			} else {
				foreach($refIds as $refId) {
					if(!isset($methods[$refId])) {
						echo sprintf("can't find method with id %s<br />", $refId);
					} else {
						if(!$methods[$itemId]->linked_articles->contains($methods[$refId])) {
							$methods[$itemId]->linked_articles->add($methods[$refId]);
						}

						if(!$methods[$refId]->linked_articles->contains($methods[$itemId])) {
							$methods[$refId]->linked_articles->add($methods[$itemId]);
						}
					}
				}
			}
		}

		foreach($methodToStudy as $itemId => $refIds) {
			if(!isset($methods[$itemId])) {
				echo sprintf("can't find method with id %s<br />", $itemId);
			} else {
				foreach($refIds as $refId) {
					if(!isset($caseStudies[$refId])) {
						echo sprintf("can't find case study with id %s<br />", $refId);
					} else {
						if(!$methods[$itemId]->linked_articles->contains($caseStudies[$refId])) {
							$methods[$itemId]->linked_articles->add($caseStudies[$refId]);
						}

						if(!$caseStudies[$refId]->linked_articles->contains($methods[$itemId])) {
							$caseStudies[$refId]->linked_articles->add($methods[$itemId]);
						}
					}
				}
			}
		}

		$i = 0;

		foreach($methods as &$s) {
			$i++;
			$em->persist($s);

			if($i % 10 == 0) {
				$em->flush();
			}
		}

		$em->flush();

		echo sprintf("finished, %s references imported<br />", count($caseStudiesToMethod) + count($methodToMethod) + count($methodToStudy));

		// Criteria
		$result = mysql_query("SELECT * FROM `INVOLVECRITERIA` ORDER BY CRITERIAORDER ASC");

		$criteria = array();
		$options = array();

		while($row = mysql_fetch_array($result)) {
			if(!isset($criteria[$row['QUESTIONID']])) {
				$criterion = new Model_Criterion;
				$criterion->title = str_replace('_', ' ', ucfirst(strtolower($row['QUESTIONID'])));
				$criteria[$row['QUESTIONID']] = $criterion;
			}

			$criterion = $criteria[$row['QUESTIONID']];

			$option = new Model_Criterion_Option();
			$option->title = $row['CRITERIATEXT'];
			$option->criterion = $criterion;
			$option->involveid = $row['CRITERIAID'];
			$criterion->options->add($option);
			$options[$row['CRITERIAID']] = $option;
		}

		$i = 0;

		foreach($criteria as $c) {
			$i++;
			$em->persist($c);

			if($i % 10 == 0) {
				$em->flush();
			}
		}

		$em->flush();

		echo sprintf("finished, %s criteria imported<br />", count($criteria));

		$methodToCriteria = array();

		$result = mysql_query("SELECT * FROM `INVOLVEMETHODCRITERIA`");

		while($row = mysql_fetch_array($result)) {
			$methodToCriteria[$row['METHODID']][] = $row['CRITERIAID'];
		}


		foreach($methodToCriteria as $mId => $crit) {
			if(!$mId) continue;
			if(!isset($methods[$mId])) {
				echo sprintf("can't find method with id %s<br />", $mId);
			} else {
				foreach(array_unique($crit) as $cId) {
					if(!isset($options[$cId])) {
						echo sprintf("can't find option with id %s<br />", $cId);
					} else {
						if(!$methods[$mId]->criteria->contains($options[$cId])) {
							$methods[$mId]->criteria->add($options[$cId]);
						}
					}

				}
			}
		}

		$i = 0;
		foreach($methods as $s) {
			$i++;
			$em->persist($s);

			if($i % 10 == 0) {
				$em->flush();
			}
		}

		$em->flush();
	}

	private function stripXml($items) {
		$text = array();

		for ($i=1; $i < count($items); $i++) {
			$text[] = (string) $items[$i];
		}

		return implode("\n", $text);
	}

	private function getReferenceFromXml($entry) {
		$ids = array();
		if($entry->ContentReference) {
			$ids[] = (string) $entry->ContentReference->id;
		}
		elseif($entry->list) {
			$ids = array_merge($ids, explode(' ', trim(preg_replace("/\s{2,}/msi", ' ', strip_tags($entry->list->asXml())))));
		}
		else {
			var_dump($entry);
		}

		return $ids;
	}

	public function action_import_involve_data() {
		$em = Doctrine::instance();

		//$caseStudyXml = simplexml_load_file(filename)
	}

	public function action_export_involve_data() {
		$connect_to_db = @mysql_connect("localhost:3306", "root", "root");
		$select_db = @mysql_select_db("involve");
		mysql_set_charset("UTF8");

		/*
		 * CASE STUDIES
		 */
		$result = mysql_query("SELECT text_val FROM `OS_PROPERTYENTRY` WHERE `text_val` LIKE '%CaseStudyProjectName%'");
		$this->writeDbResultToFile($result, APPPATH . $this->casestudiesFile);

		/*
		 * METHODS
		 */
		$result = mysql_query("SELECT text_val FROM `OS_PROPERTYENTRY` WHERE `text_val` LIKE '%<string>MethodName</string>%'");
		$this->writeDbResultToFile($result, APPPATH . $this->methodsFile);
	}

	private function writeDbResultToFile($result,$file) {
		if(file_exists($file)) {
			unlink($file);
		}

		$fh = fopen($file, 'a');

		$n = 0;
		while ($row = mysql_fetch_array($result)) {
			fwrite($fh, $row["text_val"]);
			fwrite($fh, "\n\n");
			$n++;
		}
		fclose($fh);
		echo sprintf("finished, %s articles imported<br />",$n);
	}
}