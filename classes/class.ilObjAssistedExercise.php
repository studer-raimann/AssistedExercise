<?php

/**
 * Class    ilObjAssistedExercise
 *
 * @author  Benjamin Seglias <bs@studer-raimann.ch>
 */

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/AssistedExercise/classes/Setting/class.xaseSettingFactory.php');

class ilObjAssistedExercise extends ilObjectPlugin {

	/**
	 * @var \ILIAS\DI\Container
	 */
	protected $dic;


	/**
	 * Constructor
	 *
	 * @access        public
	 *
	 * @param int $a_ref_id
	 */
	function __construct($a_ref_id = 0) {
		parent::__construct($a_ref_id);
	}


	public final function initType() {
		$this->setType(ilAssistedExercisePlugin::PLUGIN_PREFIX);
	}


	public function doCreate() {
		/*       $this->dic->database()->manipulate('INSERT INTO xase_settings (id, is_online, is_time_limited, always_visible, modus) VALUES(
					' . $this->dic->database()->quote($this->getId(), 'integer') . ', ' . $this->dic->database()->quote(1, 'integer') . ', '
				   . $this->dic->database()->quote(0, 'integer') . ', ' . $this->dic->database()->quote(0, 'integer') . ', ' . $this->dic->database()->quote(1, 'integer') . ')');*/
	}


	public function doRead() {
		parent::doRead(); // TODO: Change the autogenerated stub
	}


	public function doUpdate() {
		parent::doUpdate(); // TODO: Change the autogenerated stub
	}


	public function doDelete() {
		/**
		 * @var $xaseSetting xaseSetting
		 */
		/*
		$xaseSetting = xaseSetting::getCollection()->where(array( 'assisted_exercise_object_id' => $this->getId() ), '=')->first();
		if ($xaseSetting->getModus() == 1
			&& xaseSettingM1::getCollection()->where(array( 'settings_id' => $xaseSetting->getId() ), '=')->hasSets()) {
			$xaseSettingM1 = xaseSettingM1::getCollection()->where(array( 'settings_id' => $xaseSetting->getId() ), '=')->first();
			$xaseSettingM1->delete();
		} elseif ($xaseSetting->getModus() == 3 && xaseSettingM3::getCollection()->where(array( 'settings_id' => $xaseSetting->getId() ), '=')) {
			$xaseSettingM3 = xaseSettingM3::getCollection()->where(array( 'settings_id' => $xaseSetting->getId() ), '=')->first();
			$xaseSettingM3->delete();
		} elseif ($xaseSetting->getModus() == 2 && xaseSettingM2::getCollection()->where(array( 'settings_id' => $xaseSetting->getId() ), '=')) {
			$xaseSettingM2 = xaseSettingM2::getCollection()->where(array( 'settings_id' => $xaseSetting->getId() ), '=')->first();
			$xaseSettingM2->delete();
		}
		$xaseSetting->delete();*/
		//parent::doDelete(); // TODO: Change the autogenerated stub
	}

	//TODO implement method (settings, items, no user data)


	/**
	 * @param ilObjAssistedExercise $new_obj Instance of
	 * @param int                   $a_target_id obj_id of the new created object
	 * @param int                   $a_copy_id
	 *
	 * @return bool|void
	 */
	public function doCloneObject($new_obj, $a_target_id, $a_copy_id = NULL) {
		assert(is_a($new_obj, ilObjAssistedExercise::class));
	}

	/**
	 * @return ilAssistedExercisePlugin
	 */
	public function returnPlugin() {
		return ilAssistedExercisePlugin::getInstance();
	}

	/**
	 * @return xaseSettingM1|xaseSettingM2|xaseSettingM3
	 */
	public function returnSetting() {
		return xaseSettingFactory::findOrGetInstanceByRefId($this->getRefId());
	}
}