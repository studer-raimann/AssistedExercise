<?php

require_once('./Services/Mail/classes/class.ilMail.php');
require_once('./Services/Mail/classes/class.ilMimeMail.php');
require_once('./Services/Form/classes/class.ilTextInputGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/Comment/class.xaseComment.php');
require_once('./Services/Link/classes/class.ilLink.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * Class xaseAssessmentFormGUI
 *
 * @author       : Benjamin Seglias   <bs@studer-raimann.ch>
 * @ilCtrl_Calls xaseAssessmentFormGUI: xaseQuestionGUI
 */
class xaseAssessmentFormGUI extends ilPropertyFormGUI {

	const M1 = "1";
	const M2 = "2";
	const M3 = "3";

	/**
	 * @var xaseQuestion
	 */
	public $xase_question;
	/**
	 * @var xaseAnswer
	 */
	public $xase_answer;
	/**
	 * @var xaseAssessment
	 */
	public $xase_assessment;
	/**
	 * @var xasePoint
	 */
	public $xase_point;
	/**
	 * @var xaseComment
	 */
	public $xase_comment;
	/**
	 * @var xaseSetting
	 */
	public $xase_settings;
	/**
	 * @var xaseAssessmentGUI
	 */
	protected $parent_gui;
	/**
	 * @var \ILIAS\DI\Container
	 */
	protected $dic;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilAssistedExercisePlugin
	 */
	protected $pl;
	/**
	 * @var ilObjAssistedExerciseAccess
	 */
	protected $access;
	/**
	 * @var ilCheckboxInputGUI
	 */
	protected $toogle_hint_checkbox;
	/**
	 * @var int
	 */
	protected $minus_points;
	/**
	 * @var int
	 */
	protected $max_assignable_points;
	/**
	 * @var int
	 */
	protected $is_student;
	/**
	 * @var ilHTTPS
	 */
	protected $https;
	/**
	 * @var ILIAS
	 */
	protected $ilias;

	/**
	 * @var ilObjAssistedExerciseFacade
	 */
	protected $obj_facade;



	public function __construct(xaseAssessmentGUI $xase_assessment_gui, $is_student = false) {
		$this->obj_facade = ilObjAssistedExerciseFacade::getInstance($_GET['ref_id']);


		global $DIC;
		$this->dic = $DIC;
		$this->tpl = $this->dic['tpl'];
		$this->tabs = $DIC->tabs();
		$this->ctrl = $this->dic->ctrl();
		$this->access = ilObjAssistedExerciseAccess::getInstance($this->obj_facade,$this->obj_facade->getUser()->getId());
		$this->pl = ilAssistedExercisePlugin::getInstance();


		$this->xase_answer = new xaseAnswer($_GET['answer_id']);


		$this->xase_question = $this->getItem();
		$this->xase_assessment = $this->getAssessment();

		$this->xase_comment = $this->getComment();
		$this->parent_gui = $xase_assessment_gui;
		$this->is_student = $is_student;
		$this->xase_settings = xaseSetting::where([ 'assisted_exercise_object_id' => $this->obj_facade->getIlObjObId() ])->first();
		//$this->mode_settings = $this->getModeSetting($this->xase_settings->getModus());
		$this->https = $this->dic['https'];
		$this->ilias = $this->dic['ilias'];
		parent::__construct();

		$this->obj_facade->getTpl()->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/templates/js/assessment.js');
		$this->initForm();
	}


	//TODO
	protected function getAnswer() {
		$xaseAnswer = xaseAnswer::where(array(
			'question_id' => $this->xase_question->getId(),
			'id' => $_GET['answer_id']
		), array( 'question_id' => '=', 'id' => '=' ))->first();
		if (empty($xaseAnswer)) {
			$xaseAnswer = new xaseAnswer();
		}

		return $xaseAnswer;
	}


	protected function getItem() {
		$xase_question = xaseQuestion::where(array( 'id' => $this->xase_answer->getQuestionId() ))->first();

		return $xase_question;
	}


	protected function getAssessment() {
		$xaseAssessment = xaseAssessment::where(array( 'answer_id' => $this->xase_answer->getId() ), array( 'answer_id' => '=' ))->first();
		if (empty($xaseAssessment)) {
			$xaseAssessment = new xaseAssessment();
		}

		return $xaseAssessment;
	}


	protected function getPoints() {
		$xase_point = xasePoint::where(array( 'id' => $this->xase_answer->getPointId() ))->first();
		if (empty($xase_point)) {
			$xase_point = new xasePoint();
		}

		return $xase_point;
	}


	protected function getComment() {
		$xase_comment = xaseComment::where(array( 'answer_id' => $this->xase_answer->getId() ))->first();
		if (empty($xase_comment)) {
			$xase_comment = new xaseComment();
		}

		return $xase_comment;
	}


	public function initForm() {
		$this->setTarget('_top');
		$this->obj_facade->getCtrl()->setParameter($this->parent_gui, xaseQuestionGUI::ITEM_IDENTIFIER, $_GET['question_id']);
		$this->setFormAction($this->obj_facade->getCtrl()->getFormAction($this->parent_gui));

		$student_user = $this->getStudentUser();
		$this->setTitle($this->obj_facade->getLanguageValue('assessment_for_task') . " " . $this->xase_question->getTitle() . " " . $this->obj_facade->getLanguageValue('submitted_by') . " "
			. $student_user->getFirstName() . " " . $student_user->getLastName());

		/*if (!$this->is_student) {
			$this->toogle_hint_checkbox = new ilCheckboxInputGUI($this->obj_facade->getLanguageValue('show_used_hints'), 'show_used_hints');
			$this->toogle_hint_checkbox->setChecked(true);
			$this->toogle_hint_checkbox->setValue(1);
			$this->addItem($this->toogle_hint_checkbox);
		}*/

		$item = new ilNonEditableValueGUI($this->obj_facade->getLanguageValue('item') . " " . $this->xase_question->getTitle(), 'item', true);
		$item->setValue($this->xase_question->getQuestiontext());
		$this->addItem($item);

		$answer = new ilNonEditableValueGUI($this->obj_facade->getLanguageValue('answer'), 'answer', true);
		$answer->setValue($this->xase_answer->getAnswertext());
		$this->addItem($answer);

		if (!$this->is_student) {
			$comment = new ilTextAreaInputGUI($this->obj_facade->getLanguageValue('comment'), 'comment');
			$comment->setRows(10);
		} else {
			$comment = new ilNonEditableValueGUI($this->obj_facade->getLanguageValue('comment'), 'comment');
			$comment->setValue($this->xase_comment->getBody());
		}
		$this->addItem($comment);

		$this->initUsedHintsForm();

		$this->initPointsForm();

		if (!$this->is_student) {
			$this->addCommandButton(xaseAssessmentGUI::CMD_UPDATE, $this->obj_facade->getLanguageValue('save'));
		}
		$this->addCommandButton(xaseAssessmentGUI::CMD_CANCEL, $this->obj_facade->getLanguageValue("cancel"));
	}


	public function initPointsForm() {
		$max_points = new ilNonEditableValueGUI($this->obj_facade->getLanguageValue('max_points'));
		$max_points->setValue($this->xase_question->getMaxPoints());
		$this->addItem($max_points);

		if (!$this->is_student) {
			$points_input = new ilTextInputGUI($this->obj_facade->getLanguageValue("points"), "points");
			$points_input->setRequired(true);
			$this->addItem($points_input);
		} else {
			$points = new ilNonEditableValueGUI($this->obj_facade->getLanguageValue('points'));
			$points->setValue($this->xase_assessment->getPointsTeacher());
			$this->addItem($points);
		}

		$max_assignable_points_input = new ilNonEditableValueGUI($this->obj_facade->getLanguageValue('max_assignable_points'));
		$this->max_assignable_points = $this->xase_question->getMaxPoints() - $this->getTotalMinusPoints();
		$max_assignable_points_input->setValue($this->max_assignable_points);
		$this->addItem($max_assignable_points_input);
	}

	protected function getTotalMinusPoints() {
		/**
		 * @var $ilDB \ilDBInterface
		 */
		$ilDB = $GLOBALS['DIC']->database();

		$sql = "SELECT SUM(minus_points) as minus_points FROM xase_used_hint_level as used
				inner join xase_hint_level as hint_level on hint_level.id = used.hint_level_id
				where used.question_id = ".$ilDB->quote($this->xase_question->getId(),'integer')." and used.user_id = ".$ilDB->quote($this->getAnswer()->getUserId(),'integer');

		$result = $ilDB->query($sql);

		while ($row = $ilDB->fetchAssoc($result)) {
			return $row['minus_points'];
		}
		return 0;
	}


	protected function checkLevel($hint_array) {
		$is_level_1 = false;
		$is_level_2 = false;
		$array_keys = array_keys($hint_array);
		foreach ($array_keys as $array_key) {
			if (is_array($array_key)) {
				if (in_array('1', $array_key)) {
					$is_level_1 = true;
				} elseif (in_array('2', $array_key)) {
					$is_level_2 = true;
				}
			} else {
				if (strpos($array_key, '1') !== false) {
					$is_level_1 = true;
				} elseif (strpos($array_key, '2') !== false) {
					$is_level_2 = true;
				}
			}
		}

		return array(
			'is_level_1' => $is_level_1,
			'is_level_2' => $is_level_2
		);
	}


	protected function getListingArray($hint_object, $check_level_array, $listing_array) {
		if ($check_level_array['is_level_1']) {
			$level_1_object = xaseHintLevel::where(array( 'hint_id' => $hint_object->getId(), 'hint_level' => 1 ))->first();
			$level_1_hint_data = $level_1_object->getHint();
			$level_1_minus_points = xasePoint::where(array( 'id' => $level_1_object->getPointId() ))->first();
			$level_1_minus_points_data = $level_1_minus_points->getMinusPoints();
			$this->minus_points += $level_1_minus_points_data;
		}
		if ($check_level_array['is_level_2']) {
			$level_2_object = xaseHintLevel::where(array( 'hint_id' => $hint_object->getId(), 'hint_level' => 2 ))->first();
			$level_2_hint_data = $level_2_object->getHint();
			$level_2_minus_points = xasePoint::where(array( 'id' => $level_2_object->getPointId() ))->first();
			$level_2_minus_points_data = $level_2_minus_points->getMinusPoints();
			$this->minus_points += $level_2_minus_points_data;
		}
		if (!empty($level_1_hint_data) || !empty($level_2_hint_data)) {
			if (!empty($level_1_hint_data) && !empty($level_2_hint_data)) {
				$listing_array[$hint_object->getLabel()] = $level_1_hint_data . " Minus Points: " . $level_1_minus_points_data . " "
					. $level_2_hint_data . " Minus Points: " . $level_2_minus_points_data;

				return $listing_array;
			}
			if (!empty($level_1_hint_data)) {
				$listing_array[$hint_object->getLabel()] = $level_1_hint_data . " Minus Points: " . $level_1_minus_points_data;
			}
			if (!empty($level_2_hint_data)) {
				$listing_array[$hint_object->getLabel()] = $level_2_hint_data . " Minus Points: " . $level_2_minus_points_data;
			}
		}

		return $listing_array;
	}


	public function createListing() {
		$f = $this->dic->ui()->factory();
		$renderer = $this->dic->ui()->renderer();

		$used_hints = json_decode($this->xase_answer->getUsedHints(), true);

		if ($used_hints === NULL) {
			$listing_array = [];
			$unordered = $f->listing()->descriptive($listing_array);

			return $renderer->render($unordered);
		}

		$hint_ids = array_keys($used_hints);
		$hint_objects = [];
		foreach ($hint_ids as $hint_id) {
			$hint_object = xaseHint::where([ 'id' => $hint_id ])->first();
			$hint_objects[] = $hint_object;
		}
		$listing_array = [];
		if (is_array($hint_objects)) {
			foreach ($hint_objects as $hint_object) {
				$hint_array = $used_hints[$hint_object->getId()];
				$check_level_array = $this->checkLevel($hint_array);
				$listing_array = $this->getListingArray($hint_object, $check_level_array, $listing_array);
			}
		} else {
			$hint_array = $used_hints[$hint_objects->getId()];
			$check_level_array = $this->checkLevel($hint_array);
			$listing_array = $this->getListingArray($hint_objects, $check_level_array, $listing_array);
		}

		$unordered = $f->listing()->descriptive($listing_array);

		return $renderer->render($unordered);
	}


	public function initUsedHintsForm() {

		//TODO Refactor
		$number_of_used_hints = xaseUsedHintLevel::where(array('question_id' => $this->getAnswer()->getQuestionId(), 'user_id' => $this->getAnswer()->getUserId()))->count();

		$item = new ilNonEditableValueGUI($this->obj_facade->getLanguageValue('used_hints'), 'number_of_used_hints');
		$item->setValue($number_of_used_hints);
		$this->addItem($item);

	/*$custom_input_gui = new ilCustomInputGUI($this->obj_facade->getLanguageValue('used_hints'), 'number_of_used_hints');
		$custom_input_gui->setHtml($this->createListing());
		$this->addItem($custom_input_gui);*/
	}


	public function fillForm() {
		$array = array(
			'comment' => $this->xase_comment->getBody(),
			'points' => $this->xase_assessment->getPointsTeacher()
		);
		$this->setValuesByArray($array, true);
	}

	/* 1) get all answers for the current item
	 * 2) save the id of the answer which got the highest points from teacher
	 * 3) loop through each votings for the current item
	 * 3) if the answer id from the votings record is equal to the answer id which has the highest points from teacher
	 *  a) yes
	 *      -get max points for the item
	 *      -get in the mode 3 settings the number of percentage
	 *      -calculate additional points
	 *      -get answer from user
 *          -get corresponding points entry
	 *      -set the calculated additional points
	 *      -save object
	 *  b) no
	 *      -get answer from user
	 *      -set the calculated additional points with value 0
	 *      -save object
	 */
	protected function setAdditionalPointsForStudents() {
		global $ilDB;

		//TODO Refactor - the points table should contains the answers_id!
		$sql = "SELECT answers.id as answer_id 
				FROM xase_answer as answers where answers.id in 
				(SELECT answer_id FROM (SELECT * FROM xase_assessm as assess where assess.points_teacher = 
				(SELECT MAX(points_teacher) FROM xase_assessm as assess_max WHERE assess_max.question_id = ".$ilDB->quote($this->xase_question->getId(),'integer').") and assess.question_id = ".$ilDB->quote($this->xase_question->getId(),'integer').") as maxvalues)";


		$set = $ilDB->query($sql);

		$arr_answer_id_highest_teacher_points = array();
		while($row = $ilDB->fetchAssoc($set)) {
			$arr_answer_id_highest_teacher_points[] = $row['answer_id'];
		}


		foreach($arr_answer_id_highest_teacher_points as $answer_id_highest_teacher_points) {
			$votings = xaseVoting::where(array('answer_id' => $answer_id_highest_teacher_points, 'voting_type' => xaseVoting::VOTING_TYPE_UP))->get();

			foreach($votings as $voting) {
				/**
				 * @var xaseVoting $voting
				 */
				$user_id = $voting->getUserId();
				/**
				 * @var xaseAssessment $assessment
				 */
				$assessment = xaseAssessment::where(array('user_id' => $user_id, 'question_id' => $this->xase_question->getId()))->first();


				if(is_object($assessment)) {
					$answer_higest_teacher_points = new xaseAnswer($answer_id_highest_teacher_points);
					$point_higest_teacher_points = new xasePoint($answer_higest_teacher_points->getPointId());

					$percentage_additiona_points = $this->obj_facade->getSetting()->getVotingPointsPercentage();
					$additional_points = $point_higest_teacher_points->getPointsTeacher() * ($percentage_additiona_points / 100);

					$assessment->setAdditionalPoints($additional_points);
					$assessment->setTotalPoints($assessment->getPointsTeacher() + $assessment->getAdditionalPoints());
					$assessment->store();
				}
			}

			$votings = xaseVoting::where(array('answer_id' => $answer_id_highest_teacher_points, 'voting_type' => xaseVoting::VOTING_TYPE_DOWN))->get();

			foreach($votings as $voting) {

				//mehre antworten wurden vom lehrer mit gleicher punktzahl ausgestattet. punkte nicht auf 0 setzen!
				if(in_array($voting->getCompAnswerId(), $arr_answer_id_highest_teacher_points) ) {
					continue;
				}

				$user_id = $voting->getUserId();
				$assessment = xaseAssessment::where(array('user_id' => $user_id, 'question_id' => $this->xase_question->getId()))->first();
				if($assessment) {
					$assessment->setAdditionalPoints(0);
					$assessment->setTotalPoints($assessment->getPointsTeacher() + $assessment->getAdditionalPoints());
					$assessment->store();
				}

			}
		}
	}



	public function fillObject() {
		if (!$this->checkInput()) {
			return false;
		}
		if ($_POST['points'] > $this->max_assignable_points) {
			ilUtil::sendFailure($this->obj_facade->getLanguageValue('msg_input_max_assignable_points') . " " . $this->max_assignable_points);

			return false;
		}

		$this->xase_answer->setIsAssessed(1);
		$this->xase_answer->store();


		if (!empty($this->getInput('points'))) {

			$this->xase_assessment->setPointsTeacher($this->getInput('points'));
			$this->xase_assessment->setAssessmentComment($this->getInput('comment'));
			$this->xase_assessment->setAnswerId($this->getAnswer()->getId());
			$this->xase_assessment->setUserId($this->getAnswer()->getUserId());
			$this->xase_assessment->setQuestionId($this->getAnswer()->getQuestionId());
			$this->xase_assessment->setTotalPoints($this->getInput('points'));
			$this->xase_assessment->setPointsTeacher($this->getInput('points'));
			$this->xase_assessment->store();

			if ($this->obj_facade->getSetting()->getVotingEnabled()) {
				$this->setAdditionalPointsForStudents();
			}

		}

		return true;
	}


	/**
	 * @return bool|string
	 */
	public function updateObject() {
		if (!$this->fillObject()) {
			return false;
		}

		//$this->notifyUserAboutAssessment();

		return true;
	}


	protected function getStudentUser() {

		return xaseilUser::where(array( 'usr_id' => $this->xase_answer->getUserId() ))->first();

	}


	// TODO: Prüfen, ob eine Variante implementier werden muss, die direkt auf eine Bewertung führt. ilobjassistedexercisegui
	/*public function notifyUserAboutAssessment() {
		$protocol = $this->https->isDetected() ? 'https://' : 'http://';
		$server_url = $protocol . $_SERVER['HTTP_HOST'] . substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')) . '/';
		$contact_address = ilMail::getIliasMailerAddress();

		$mm = new ilMimeMail();
		$mm->Subject($this->obj_facade->getLanguageValue('your_answer_of_the_task') . " " . $this->xase_question->getTitle() . " " . $this->obj_facade->getLanguageValue("was_assessed"));
		$mm->From($contact_address);
		$mm->To($this->getStudentUser()->getEmail());

		$assessment_url = ilLink::_getStaticLink($_GET['ref_id'], 'xase', true);

<<<<<<< HEAD:classes/class.xaseAssessmentFormGUI.php
		$body = $this->pl->txt('the_following_link_leads_to_the_list_view_of_the_items') . "\n" . $assessment_url . "\n"
			. $this->pl->txt('click_on_actions_view_assessment');
=======
		/*        $mm->Body
				(
					str_replace
					(
						array("\\n", "\\t"),
						array("\n", "\t"),
						sprintf
						(
							$this->obj_facade->getLanguageValue('pleas_click_on_the_following_link_to_view_the_assessment'),
							$assessment_url,
							$server_url,
							$_SERVER['REMOTE_ADDR'],
							'mailto:' . $contact_address[0]
						)
					)
				);*/

		/*$body = $this->obj_facade->getLanguageValue('the_following_link_leads_to_the_list_view_of_the_items') . "\n" . $assessment_url . "\n"
			. $this->obj_facade->getLanguageValue('click_on_actions_view_assessment');
>>>>>>> develop:classes/Assessment/class.xaseAssessmentFormGUI.php

		$mm->Body($body);
		$mm->Send();
	}*/
}