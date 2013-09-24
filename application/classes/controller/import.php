<?php

class Controller_Import extends Controller {
	private function stripXml($items) {
		$text = array();

		for ($i=1; $i < count($items); $i++) {
			$text[] = (string) $items[$i];
		}

		return implode("\n", $text);
	}

    /**
     * @param $entry
     * @return array
     */
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



	public function action_index() {
		set_time_limit(60 * 60);

		$con = Doctrine::instance()->getConnection();
		$con->query("TRUNCATE TABLE articles_options");
		$con->query("TRUNCATE TABLE article_links");
		$con->query("TRUNCATE TABLE articles");

		$connect_to_db = @mysql_connect("localhost:3306", "root", "root");
		$select_db = @mysql_select_db("involve");
		mysql_set_charset("UTF8");
		$this->em = Doctrine::instance();

		$this->import_studys();
		$this->import_methods();
		$this->import_ref();
		$this->import_crit();
	}

	private $caseStudiesToMethod = array();
	private $methodToMethod = array();
	private $methodToStudy = array();
	private $em = null;

	private function import_crit() {
		$methodToCriteria = array();

		$result = mysql_query("SELECT * FROM `INVOLVEMETHODCRITERIA`");

		while($row = mysql_fetch_array($result)) {
			$methodToCriteria[$row['METHODID']][] = $row['CRITERIAID'];
		}


		foreach($methodToCriteria as $mId => $crit) {
			if(!$mId) continue;
			if(!$method = $this->em->getRepository('Model_Article')->findOneByInvolveid($mId)) {
				echo sprintf("can't find method with id %s<br />", $mId);
			} else {
				foreach(array_unique($crit) as $cId) {
					if(!$option = $this->em->getRepository('Model_Criterion_Option')->findOneByInvolveid($cId)) {
						echo sprintf("can't find option with id %s<br />", $cId);
					} else {
						if(!$method->criteria->contains($option)) {
							$method->criteria->add($option);
							echo "Added {$method->title} to {$option->title}<br />";
						}
					}
				}

				$this->em->persist($method);
				$this->em->flush();
			}
		}
	}

	private function import_ref() {
		foreach($this->caseStudiesToMethod as $itemId => $refIds) {
			if(!$item1 = $this->em->getRepository('Model_Article')->findOneByInvolveid($itemId)) {
				echo sprintf("can't find case study with id %s<br />", $itemId);
				continue;
			} else {
				foreach($refIds as $refId) {
					if(!$item2 = $this->em->getRepository('Model_Article')->findOneByInvolveid($refId)) {
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

					$this->em->persist($item1);
					$this->em->persist($item2);
					$this->em->flush();
					echo "Linked {$item1->type()} {$item1->title} > {$item2->type()} {$item2->title}<br />";
				}
			}
		}

		foreach($this->methodToMethod as $itemId => $refIds) {
			if(!$item1 = $this->em->getRepository('Model_Article')->findOneByInvolveid($itemId)) {
				echo sprintf("can't find method with id %s<br />", $itemId);
				continue;
			} else {
				foreach($refIds as $refId) {
					if(!$item2 = $this->em->getRepository('Model_Article')->findOneByInvolveid($refId)) {
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

					$this->em->persist($item1);
					$this->em->persist($item2);
					$this->em->flush();
					echo "Linked {$item1->type()} {$item1->title} > {$item2->type()} {$item2->title}<br />";
				}
			}
		}

		foreach($this->methodToStudy as $itemId => $refIds) {
			if(!$item1 = $this->em->getRepository('Model_Article')->findOneByInvolveid($itemId)) {
				echo sprintf("can't find method with id %s<br />", $itemId);
				continue;
			} else {
				foreach($refIds as $refId) {
					if(!$item2 = $this->em->getRepository('Model_Article')->findOneByInvolveid($refId)) {
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

					$this->em->persist($item1);
					$this->em->persist($item2);
					$this->em->flush();
					echo "Linked {$item1->type()} {$item1->title} > {$item2->type()} {$item2->title}<br />";
				}
			}
		}
	}

	private function import_studys() {
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

		$user = $this->em->getRepository('Model_User')->findOneById(1);

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
				$this->caseStudiesToMethod[$row['entity_id']] = $ids;
			}

			$item->involveid = $row['entity_id'];

			$item->ready_for_publish = true;
			$item->active = true;
			$item->user = $user;

			$this->em->persist($item);
			$this->em->flush();

			echo "Added case study {$item->involveid} {$item->title}<br />";
		}
	}

	private function import_methods() {
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

		$user = $this->em->getRepository('Model_User')->findOneById(1);

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

			if($item->title == 'Method Name' OR $item->title == 'test' OR $item->title == 'test method' OR $item->title == 'Test Method') {
				continue;
			}

			if(!$item->title) {
				$item->title = 'No title found in import data';
			}

			if(count($methodIds)) {
				$this->methodToMethod[$row['entity_id']] = $methodIds;
			}

			if(count($caseStudyIds)) {
				$this->methodToStudy[$row['entity_id']] = $caseStudyIds;
			}

			$item->involveid = $row['entity_id'];

			$item->ready_for_publish = true;
			$item->active = true;
			$item->user = $user;

			$this->em->persist($item);
			$this->em->flush();

			echo "Added method {$item->involveid} {$item->title}<br />";
		}
	}
}