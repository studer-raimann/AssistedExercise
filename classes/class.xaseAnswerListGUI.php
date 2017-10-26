<?php
/**
 * Class xaseAnswerListGUI
 * @author: Benjamin Seglias   <bs@studer-raimann.ch>
 */

class xaseAnswerListGUI
{
    const CMD_STANDARD = 'edit';
    const CMD_UPDATE = 'update';
    const CMD_CANCEL = 'cancel';

    /**
     * @var ilObjAssistedExercise
     */
    public $assisted_exercise;
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
     * @var xaseItem
     */
    protected $xase_item;

    public function __construct(ilObjAssistedExercise $assisted_exericse)
    {
        global $DIC;
        $this->dic = $DIC;
        $this->tpl = $this->dic['tpl'];
        $this->tabs = $DIC->tabs();
        $this->ctrl = $this->dic->ctrl();
        $this->access = new ilObjAssistedExerciseAccess();
        $this->pl = ilAssistedExercisePlugin::getInstance();
        $this->assisted_exercise = $assisted_exericse;
        $this->xase_item = new xaseItem($_GET['item_id']);

        $this->tpl->addJavaScript('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/templates/js/answerformlist.js');
        //$this->initAnswerList();

        //parent::__construct();
    }

    public function executeCommand()
    {
        $nextClass = $this->ctrl->getNextClass();
        switch ($nextClass) {
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
                $this->tabs->activateTab(xaseItemGUI::CMD_STANDARD);
                $this->{$cmd}();
        }
    }

    protected function performCommand()
    {
        $cmd = $this->ctrl->getCmd(self::CMD_STANDARD);
        switch ($cmd) {
            case self::CMD_STANDARD:
            case self::CMD_UPDATE:
            case self::CMD_CANCEL:
                if ($this->access->hasWriteAccess()) {
                    $this->{$cmd}();
                    break;
                } else {
                    ilUtil::sendFailure(ilAssistedExercisePlugin::getInstance()->txt('permission_denied'), true);
                    break;
                }
        }
    }

    public function edit()
    {
        $this->ctrl->saveParameterByClass(xaseAnswerFormListGUI::class, xaseItemGUI::ITEM_IDENTIFIER);
        $this->tabs->activateTab(xaseItemGUI::CMD_STANDARD);
        $xaseAnswerFormListGUI = new xaseAnswerFormListGUI($this->assisted_exercise, $this);
        $xaseAnswerFormListGUI->fillForm();
        $this->tpl->setContent($xaseAnswerFormListGUI->getHTML());
        $this->tpl->show();
    }

    public function update()
    {
        $this->ctrl->saveParameterByClass(xaseAnswerFormListGUI::class, xaseItemGUI::ITEM_IDENTIFIER);
        $this->tabs->activateTab(xaseItemGUI::CMD_STANDARD);
        $xaseAnswerFormListGUI = new xaseAnswerFormListGUI($this->assisted_exercise, $this);
        if ($xaseAnswerFormListGUI->updateObject()) {
            ilUtil::sendSuccess($this->pl->txt('changes_saved_success'), true);
            $this->ctrl->redirectByClass(xaseItemGUI::class, xaseItemGUI::CMD_STANDARD);
        }
        $xaseAnswerFormListGUI->setValuesByPost();
        $this->tpl->setContent($xaseAnswerFormListGUI->getHTML());
        $this->tpl->show();
    }

    protected function cancel() {
        $this->ctrl->redirectByClass('xaseitemgui', xaseItemGUI::CMD_STANDARD);
    }
}