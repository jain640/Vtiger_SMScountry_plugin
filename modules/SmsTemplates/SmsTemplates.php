<?php
/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *******************************************************************************/
 
class SmsTemplates {
 	
 	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name = 'vtiger_smsnotifier';
	var $table_index= 'templateid';
	
	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	function __construct() {
		global $log, $currentModule;
		$this->column_fields = getColumnFields($currentModule);
		$this->db = new PearDatabase();
		$this->log = $log;
	}

 	function vtlib_handler($moduleName, $eventType) {
 					
		require_once('include/utils/utils.php');			
		global $adb;

 		
 		if($eventType == 'module.postinstall') {			
			// TODO Handle actions when this module is disabled.
			$adb->pquery("CREATE TABLE if not exists `vtiger_smstemplates`(
`templatename` varchar(100) DEFAULT NULL, 
`message` text, 
`deleted` int(1) NOT NULL DEFAULT '0',
`templatelanguage` char(2) NOT NULL DEFAULT '',
`templateid` int(19) NOT NULL AUTO_INCREMENT,
PRIMARY KEY (`templateid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8");
			$adb->pquery("CREATE TABLE if not exists `vtiger_smstemplates_seq` (
					`id` int(11) NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		} else if($eventType == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($eventType == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
 	}
}
?>
