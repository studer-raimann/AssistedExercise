<?php
/**
 * Class xaseSubmissionTableGUI
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/ActiveRecords/class.xasePoint.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/ActiveRecords/class.xaseAssessment.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/ActiveRecords/class.xaseAnswer.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/ActiveRecords/class.xaseVoting.php');
require_once('./Services/ActiveRecord/_Examples/Message/class.arUser.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/class.xaseAssessmentGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/class.xaseAnswerGUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('./Services/Form/classes/class.ilTextInputGUI.php');

class xaseSubmissionTableGUI extends ilTable2GUI
{
    const TBL_ID = 'tbl_xase_submissions';

    /**
     * @var \ILIAS\DI\Container
     */
    protected $dic;

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var xaseSubmissionGUI
     */
    protected $parent_obj;

    /**
     * @var xaseAnswer
     */
    protected $xase_answer;

    /**
     * @var xaseSettings
     */
    public $xase_settings;

    /**
     * @var xaseItem
     */
    public $xase_item;

    /**
     * @var ilAssistedExercisePlugin
     */
    protected $pl;

    /**
     * @var ilObjAssistedExerciseAccess
     */
    protected $access;
    /**
     * @var ilObjAssistedExercise
     */
    public $assisted_exercise;

    /**
     * ilLocationDataTableGUI constructor.
     * @param xaseSubmissionGUI $a_parent_obj
     * @param string $a_parent_cmd
     */
    function __construct($a_parent_obj, $a_parent_cmd, ilObjAssistedExercise $assisted_exercise)
    {
        global $DIC;
        $this->dic = $DIC;
        $this->parent_obj = $a_parent_obj;
        $this->ctrl = $this->dic->ctrl();
        $this->pl = ilAssistedExercisePlugin::getInstance();
        $this->access = new ilObjAssistedExerciseAccess();

        $this->setId(self::TBL_ID);
        $this->setPrefix(self::TBL_ID);
        $this->setFormName(self::TBL_ID);
        $this->ctrl->saveParameter($a_parent_obj, $this->getNavParameter());
        $this->assisted_exercise = $assisted_exercise;
        //$this->xase_answer = $this->getSubmittedAnswers();
        $this->xase_settings = xaseSettings::where(['assisted_exercise_object_id' => $assisted_exercise->getId()])->first();
        //$this->xase_item = $xase_item;

        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->parent_obj = $a_parent_obj;
        $this->setRowTemplate("tpl.submissions.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise");

        $this->setFormAction($this->ctrl->getFormActionByClass('xasesubmissiongui'));
        $this->setExternalSorting(true);

        $this->setDefaultOrderField("submission_date");
        $this->setDefaultOrderDirection("asc");
        $this->setExternalSegmentation(true);
        $this->setEnableHeader(true);

        $list = $this->createListing();
        $this->tpl->setVariable('LIST', $list);

        $this->initColums();
        $this->addFilterItems();
        $this->parseData();
    }

    protected function addFilterItems()
    {
        $firstname = new ilTextInputGUI($this->pl->txt('firstname'), 'firstname');
        $this->addAndReadFilterItem($firstname);

        $lastname = new ilTextInputGUI($this->pl->txt('lastname'), 'lastname');
        $this->addAndReadFilterItem($lastname);

        $item = new ilTextInputGUI($this->pl->txt('item_title'), 'title');
        $this->addAndReadFilterItem($item);

        //TODO handle Status
        include_once("./Services/Form/classes/class.ilSelectInputGUI.php");
        $option[0] = $this->pl->txt('no');
        $option[1] = $this->pl->txt('yes');
        $assessed = new ilSelectInputGUI($this->pl->txt("assessed"), "assessed");
        $assessed->setOptions($option);
        $this->addAndReadFilterItem($assessed);
    }

    /**
     * @param $item
     */
    protected function addAndReadFilterItem(ilFormPropertyGUI $item)
    {
        $this->addFilterItem($item);
        $item->readFromSession();
        if ($item instanceof ilCheckboxInputGUI) {
            $this->filter[$item->getPostVar()] = $item->getChecked();
        } else {
            $this->filter[$item->getPostVar()] = $item->getValue();
        }
    }

    protected function getAssessment($answer_id) {
        $xaseAssessment = xaseAssessment::where(array('answer_id' => $answer_id))->first();
        if (empty($xaseAssessment)) {
            $xaseAssessment = new xaseAssessment();
        }
        return $xaseAssessment;
    }

    /**
     * @param array $a_set
     */
    public function fillRow($a_set)
    {
        /**
         * @var $xaseAnswer xaseAnswer
         */
        //$a_set contains the items
        $xaseAnswer = xaseAnswer::find($a_set['id']);

        if ($this->isColumnSelected('firstname')) {
            $this->tpl->setCurrentBlock("firstname");
            $this->tpl->setVariable('FIRSTNAME', $this->dic->user()->getFirstname());
            $this->tpl->parseCurrentBlock();
        }
        if ($this->isColumnSelected('lastname')) {
            $this->tpl->setCurrentBlock("lastname");
            $this->tpl->setVariable('LASTNAME', $this->dic->user()->getLastname());
            $this->tpl->parseCurrentBlock();
        }
        if ($this->isColumnSelected('submission_date')) {
            $this->tpl->setCurrentBlock('submissiondate');
            $this->tpl->setVariable('SUBMISSIONDATE', $xaseAnswer->getSubmissionDate());
            $this->tpl->parseCurrentBlock();
        }

        /*
         * @var xaseItem $item
         */
        $item = xaseItem::where(array('id' => $xaseAnswer->getItemId()))->first();
        if ($this->isColumnSelected('item_title')) {
            $this->tpl->setCurrentBlock('itemtitle');
            $this->tpl->setVariable('ITEMTITLE', $item->getItemTitle());
            $this->tpl->parseCurrentBlock();
        }
        if ($this->isColumnSelected('assessed')) {
            $this->tpl->setCurrentBlock('assessed');
            $this->tpl->setVariable('ASSESSED', $xaseAnswer->getisAssessed() ? $this->pl->txt('yes') : $this->pl->txt('no'));
            $this->tpl->parseCurrentBlock();
        }
        if ($this->isColumnSelected('number_of_used_hints')) {
            $this->tpl->setCurrentBlock('numberofusedhints');
            $this->tpl->setVariable('NUMBEROFUSEDHINTS', $xaseAnswer->getNumberOfUsedHints());
            $this->tpl->parseCurrentBlock();
        }

        /**
         * @var $xasePoint xasePoint
         */
        $xasePoint = xasePoint::find($xaseAnswer->getPointId());

        if (!empty($xasePoint)) {
            if ($this->isColumnSelected('max_points')) {
                $this->tpl->setCurrentBlock('maxpoints');
                if(!empty($xasePoint->getMaxPoints())) {
                    $this->tpl->setVariable('MAXPOINTS', $xasePoint->getMaxPoints());
                } else {
                    $this->tpl->setVariable('MAXPOINTS', 0);
                }
                $this->tpl->parseCurrentBlock();
            }
            if($this->xase_settings->getModus() == 3) {
                if ($this->isColumnSelected('points_teacher')) {
                    $this->tpl->setCurrentBlock("pointsteacher");
                    if (!empty($xasePoint->getPointsTeacher())) {
                        $this->tpl->setVariable('POINTSTEACHER', $xasePoint->getPointsTeacher());
                    } else {
                        $this->tpl->setVariable('POINTSTEACHER', 0);
                    }
                    $this->tpl->parseCurrentBlock();
                }
                if ($this->isColumnSelected('additional_points')) {
                    $this->tpl->setCurrentBlock("additionalpointsvoting");
                    if(!empty($xasePoint->getAdditionalPoints())) {
                        $this->tpl->setVariable('ADDITIONALPOINTSVOTING', $xasePoint->getAdditionalPoints());
                    } else {
                        $this->tpl->setVariable('ADDITIONALPOINTSVOTING', 0);
                    }
                    $this->tpl->parseCurrentBlock();
                }
            }
            if ($this->isColumnSelected('points')) {
                $this->tpl->setCurrentBlock("points");
                if (!empty($xasePoint->getTotalPoints())) {
                    $this->tpl->setVariable('TOTALPOINTS', $xasePoint->getTotalPoints());
                } else {
                    $this->tpl->setVariable('TOTALPOINTS', 0);
                }
                $this->tpl->parseCurrentBlock();
            }
        }
        /**
         * @var $xaseVoting xaseVoting
         */
        $xaseVoting = xaseVoting::where(array('answer_id' => $xaseAnswer->getId()))->first();

        /**
         * @var $xaseAnswer xaseAnswer
         */
        if ($this->xase_settings->getModus() == 3 && $this->isColumnSelected('number_of_upvotings')) {
            $this->tpl->setCurrentBlock("number_up_votings");
            if(!empty($xaseVoting)) {
                $this->tpl->setVariable('NUMBERUPVOTINGS', $xaseVoting->getNumberOfUpvotings());
            } else {
                $this->tpl->setVariable('NUMBERUPVOTINGS', 0);
            }
            $this->tpl->parseCurrentBlock();
        }

        $xaseAssessment = $this->getAssessment($xaseAnswer->getId());

        $this->addActionMenu($xaseAnswer, $xaseAssessment);
    }

    protected function initColums()
    {
        $number_of_selected_columns = count($this->getSelectedColumns());
        //add one to the number of columns for the action
        $number_of_selected_columns++;
        $column_width = 100 / $number_of_selected_columns . '%';

        $all_cols = $this->getSelectableColumns();
        foreach ($this->getSelectedColumns() as $col) {
            $this->addColumn($all_cols[$col]['txt'], $col, $column_width);
        }

        $this->addColumn($this->pl->txt('common_actions'), '', $column_width);
    }

    /**
     * @param xaseItem $xaseItem
     */
    protected function addActionMenu(xaseAnswer $xaseAnswer, xaseAssessment $xaseAssessment)
    {
        $current_selection_list = new ilAdvancedSelectionListGUI();
        $current_selection_list->setListTitle($this->pl->txt('common_actions'));
        $current_selection_list->setId('answer_actions' . $xaseAnswer->getId());
        $current_selection_list->setUseImages(false);

        $this->ctrl->setParameter($this->parent_obj, xaseItemGUI::ITEM_IDENTIFIER, $xaseAnswer->getId());
        $this->ctrl->setParameterByClass(xaseAnswerGUI::class, xaseAnswerGUI::ANSWER_IDENTIFIER, $xaseAnswer->getId());
        $this->ctrl->setParameterByClass(xaseAssessmentGUI::class, xaseAnswerGUI::ANSWER_IDENTIFIER, $xaseAnswer->getId());
        //TODO xase_item setzen
        //$this->ctrl->setParameterByClass(xaseAssessmentGUI::class, xaseItemGUI::ITEM_IDENTIFIER, $this->xase_item->getId());
        if ($this->access->hasWriteAccess()) {
            $current_selection_list->addItem($this->pl->txt('assess'), xaseAssessmentGUI::CMD_STANDARD, $this->ctrl->getLinkTargetByClass('xaseassessmentgui', xaseAssessmentGUI::CMD_STANDARD));
        }
        if($this->xase_settings->getModus() == 3) {
            if ($this->access->hasWriteAccess()) {
                $current_selection_list->addItem($this->pl->txt('show_upvotings'), xaseVotingGUI::CMD_STANDARD, $this->ctrl->getLinkTargetByClass('xasevotinggui', xaseVotingGUI::CMD_STANDARD));
            }
        }
        $this->tpl->setVariable('ACTIONS', $current_selection_list->getHTML());
    }

    protected function parseData()
    {
        $this->determineOffsetAndOrder();
        $this->determineLimit();

        $collection = xaseAnswer::getCollection();
        $collection->where(array('answer_status' => array('submitted', 'rated')), array('answer_status' => 'IN'));

        $collection->leftjoin(xaseAssessment::returnDbTableName(), 'id', 'answer_id', array('assessment_comment'));

        $collection->leftjoin(xasePoint::returnDbTableName(), 'point_id', 'id', array('max_points', 'total_points', 'points_teacher', 'additional_points', 'minus_points'));

        $collection->leftjoin(arUser::returnDbTableName(), 'user_id', 'usr_id', array('firstname', 'lastname'));

        $collection->leftjoin(xaseItem::returnDbTableName(), 'item_id', 'id', array('item_title'));

        if($this->xase_settings->getModus() == 3) {
            $collection->leftjoin(xaseVoting::returnDbTableName(), 'id', 'answer_id', array('number_of_upvotings'));
        }

        $sorting_column = $this->getOrderField() ? $this->getOrderField() : 'submission_date';
        $offset = $this->getOffset() ? $this->getOffset() : 0;

        $sorting_direction = $this->getOrderDirection();
        $num = $this->getLimit();

        $collection->orderBy($sorting_column, $sorting_direction);
        $collection->limit($offset, $num);

        foreach ($this->filter as $filter_key => $filter_value) {
            switch ($filter_key) {
                case 'firstname':
                case 'lastname':
                case 'item':
                case 'assessed':
                    if(!empty($filter_value)) {
                        $collection->where(array($filter_key => '%' . $filter_value . '%'), 'LIKE');
                        break;
                    }
            }
        }

        //$collection->debug();

        $this->setData($collection->getArray());
    }

    public function getSelectableColumns()
    {
        $cols["firstname"] = array(
            "txt" => $this->pl->txt("firstname"),
            "default" => true);
        $cols["lastname"] = array(
            "txt" => $this->pl->txt("lastname"),
            "default" => true);
        $cols["submission_date"] = array(
            "txt" => $this->pl->txt("submission_date"),
            "default" => true);
        $cols["item_title"] = array(
            "txt" => $this->pl->txt("item_title"),
            "default" => true);
        $cols["assessed"] = array(
            "txt" => $this->pl->txt("assessed"),
            "default" => true);
        $cols["max_points"] = array(
            "txt" => $this->pl->txt("max_points"),
            "default" => true);
        $cols["number_of_used_hints"] = array(
            "txt" => $this->pl->txt("number_of_used_hints"),
            "default" => true);
        if($this->xase_settings->getModus() == 3) {
            $cols["points_teacher"] = array(
                "txt" => $this->pl->txt("points_teacher"),
                "default" => true);
            $cols["additional_points"] = array(
                "txt" => $this->pl->txt("additional_points"),
                "default" => true);
            $cols["points"] = array(
                "txt" => $this->pl->txt("total_points"),
                "default" => true);
        } elseif($this->xase_settings->getModus() == 1) {
            $cols["points"] = array(
                "txt" => $this->pl->txt("points"),
                "default" => true);
        }
        if($this->xase_settings->getModus() == 3) {
            $cols["number_of_upvotings"] = array(
                "txt" => $this->pl->txt("number_of_upvotings"),
                "default" => false);
        }
        return $cols;
    }

    // TODO change static array values
    // TODO decide if the listing appears in front of the filter
    public function createListing()
    {
        $f = $this->dic->ui()->factory();
        $renderer = $this->dic->ui()->renderer();

        $unordered = $f->listing()->descriptive(
            array
            (
                $this->pl->txt('max_achievable_points') => strval(80),
                $this->pl->txt('average_achieved_points') => strval(64),
                $this->pl->txt('average_used_hints_per_item') => strval(4)
            )
        );

        return $renderer->render($unordered);
    }
}