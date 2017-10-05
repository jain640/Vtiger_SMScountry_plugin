<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('modules/Vtiger/CRMEntity.php');

class SMSNotifierBase extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	var $table_name = 'vtiger_smsnotifier';
	var $table_index= 'smsnotifierid';

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_smsnotifiercf', 'smsnotifierid');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_smsnotifier', 'vtiger_smsnotifiercf', 'vtiger_smsnotifier_status');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_smsnotifier' => 'smsnotifierid',
		'vtiger_smsnotifier_status' => 'smsnotifierid',
		'vtiger_smsnotifiercf'=>'smsnotifierid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Message' => Array('smsnotifier', 'message'),
		'Assigned To' => Array('crmentity','smownerid'),
		'Message ID' => Array('smsnotifier_status','smsmessageid'),
		'Phone' => Array('smsnotifier_status','tonumber'),
		'Category' => Array('smsnotifier_status','category')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Message' => 'message',
		'Assigned To' => 'assigned_user_id',
		'Message ID' => 'smsmessageid',
		'Phone' => 'tonumber',
		'Category' => 'category'
	);

	// Make the field link to detail view
	var $list_link_field = 'message';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'Message' => Array('smsnotifier', 'message'),
		'Message ID' => Array('smsnotifier_status', 'smsmessageid'),
		'Phone' => Array('smsnotifier_status', 'tonumber'),
		'Category' => Array('smsnotifier_status', 'category')
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'Message' => 'message',
		'Message ID' => 'smsmessageid',
		'Category' => 'category',
		'Phone' => 'tonumber'
	);

	// For Popup window record selection
	var $popup_fields = Array ('message', 'tonumber');

	// Allow sorting on the following (field column names)
	var $sortby_fields = Array ('message','tonumber');

	// Should contain field labels
	//var $detailview_links = Array ('Message');

	// For Alphabetical search
	var $def_basicsearch_col = 'message';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'message';

	// Required Information for enabling Import feature
	var $required_fields = Array ('assigned_user_id'=>1);

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'crmid';
	var $default_sort_order='DESC';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'message', 'tonumber', 'smsmessageid');

	function __construct() {
		global $log, $currentModule;
		$this->column_fields = getColumnFields($currentModule);
		$this->db = new PearDatabase();
		$this->log = $log;
	}

	function getSortOrder() {
		global $currentModule;

		$sortorder = $this->default_sort_order;
		if($_REQUEST['sorder']) $sortorder = $_REQUEST['sorder'];
		else if($_SESSION[$currentModule.'_Sort_Order'])
			$sortorder = $_SESSION[$currentModule.'_Sort_Order'];

		return $sortorder;
	}

	function getOrderBy() {
		$orderby = $this->default_order_by;
		if($_REQUEST['order_by']) $orderby = $_REQUEST['order_by'];
		else if($_SESSION[$currentModule.'_Order_By'])
			$orderby = $_SESSION[$currentModule.'_Order_By'];
		return $orderby;
	}

	function save_module($module) {
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord) {
		// $srcrecord could be empty
	}

	/**
	 * Get list view query (send more WHERE clause condition if required)
	 */
	function getListQuery($module, $usewhere=false) {
		$query = "SELECT vtiger_crmentity.*, $this->table_name.*";

		// Select Custom Field Table Columns if present
		if(!empty($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$query .= " FROM $this->table_name";

		$query .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}
		$query .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM vtiger_field" .
				" INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid" .
				" WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", array($module));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			checkFileAccessForInclusion("modules/$related_module/$related_module.php");
			require_once("modules/$related_module/$related_module.php");
			$other = new $related_module();
			vtlib_setup_modulevars($related_module, $other);

			$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $this->table_name.$columnname";
		}

		$query .= "	WHERE vtiger_crmentity.deleted = 0 ";
		if($usewhere) {
			$query .= $usewhere;
		}
		$query .= $this->getListViewSecurityParameter($module);
		return $query;
	}

	/**
	 * Apply security restriction (sharing privilege) query part for List view.
	 */
	function getListViewSecurityParameter($module) {
		echo "Reach";
		exit;
		global $current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

		$sec_query = '';
		$tabid = getTabid($module);

		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {

				$sec_query .= " AND (vtiger_crmentity.smownerid in($current_user->id) OR vtiger_crmentity.smownerid IN
					(
						SELECT vtiger_user2role.userid FROM vtiger_user2role
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid
						INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid
						WHERE vtiger_role.parentrole LIKE '".$current_user_parent_role_seq."::%'
					)
					OR vtiger_crmentity.smownerid IN
					(
						SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per
						WHERE userid=".$current_user->id." AND tabid=".$tabid."
					)
					OR
						(";

					// Build the query based on the group association of current user.
					if(sizeof($current_user_groups) > 0) {
						$sec_query .= " vtiger_groups.groupid IN (". implode(",", $current_user_groups) .") OR ";
					}
					$sec_query .= " vtiger_groups.groupid IN
						(
							SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid
							FROM vtiger_tmp_read_group_sharing_per
							WHERE userid=".$current_user->id." and tabid=".$tabid."
						)";
				$sec_query .= ")
				)";
		}
		return $sec_query;
	}

	/**
	 * Create query to export the records.
	 */
	function create_export_query($where)
	{
		global $current_user;
		$thismodule = $_REQUEST['module'];

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery($thismodule, "detail_view");

		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT $fields_list, vtiger_users.user_name AS user_name
					FROM vtiger_crmentity INNER JOIN $this->table_name ON vtiger_crmentity.crmid=$this->table_name.$this->table_index";

		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}

		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id and vtiger_users.status='Active'";

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM vtiger_field" .
				" INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid" .
				" WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", array($thismodule));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			checkFileAccessForInclusion("modules/$related_module/$related_module.php");
			require_once("modules/$related_module/$related_module.php");
			$other = new $related_module();
			vtlib_setup_modulevars($related_module, $other);

			$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $this->table_name.$columnname";
		}

		$where_auto = " vtiger_crmentity.deleted=0";

		if($where != '') $query .= " WHERE ($where) AND $where_auto";
		else $query .= " WHERE $where_auto";

		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

		// Security Check for Field Access
		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1 && $defaultOrgSharingPermission[7] == 3)
		{
			//Added security check to get the permitted records only
			$query = $query." ".getListViewSecurityParameter($thismodule);
		}
		return $query;
	}

	/**
	 * Transform the value while exporting (if required)
	 */
	function transform_export_value($key, $value) {
		return parent::transform_export_value($key, $value);
	}

	/**
	 * Function which will give the basic query to find duplicates
	 */
	function getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr,$select_cols='') {
		$select_clause = "SELECT ". $this->table_name .".".$this->table_index ." AS recordid, vtiger_users_last_import.deleted,".$table_cols;

		// Select Custom Field Table Columns if present
		if(isset($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$from_clause = " FROM $this->table_name";

		$from_clause .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(isset($this->customFieldTable)) {
			$from_clause .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				      " = $this->table_name.$this->table_index";
		}
		$from_clause .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$where_clause = "	WHERE vtiger_crmentity.deleted = 0";
		$where_clause .= $this->getListViewSecurityParameter($module);

		if (isset($select_cols) && trim($select_cols) != '') {
			$sub_query = "SELECT $select_cols FROM  $this->table_name AS t " .
				" INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t.".$this->table_index;
			// Consider custom table join as well.
			if(isset($this->customFieldTable)) {
				$sub_query .= " LEFT JOIN ".$this->customFieldTable[0]." tcf ON tcf.".$this->customFieldTable[1]." = t.$this->table_index";
			}
			$sub_query .= " WHERE crm.deleted=0 GROUP BY $select_cols HAVING COUNT(*)>1";
		} else {
			$sub_query = "SELECT $table_cols $from_clause $where_clause GROUP BY $table_cols HAVING COUNT(*)>1";
		}


		$query = $select_clause . $from_clause .
					" LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=" . $this->table_name .".".$this->table_index .
					" INNER JOIN (" . $sub_query . ") AS temp ON ".get_on_clause($field_values,$ui_type_arr,$module) .
					$where_clause .
					" ORDER BY $table_cols,". $this->table_name .".".$this->table_index ." ASC";

		return $query;
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {

		//adds sharing accsess
        $SMSNotifierModule  = Vtiger_Module::getInstance('SMSNotifier');
        Vtiger_Access::setDefaultSharing($SMSNotifierModule);

		$registerLinks = false;
		$unregisterLinks = false;

		if($event_type == 'module.postinstall') {
			global $adb;
			$unregisterLinks = true;
			$registerLinks = true;

			// Mark the module as Standard module
			$adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', array($modulename));

			$adb->pquery("CREATE TABLE IF NOT EXISTS `vtiger_smscountry_log` (
			  `smscountryid` int(11) NOT NULL AUTO_INCREMENT,
			  `message` text,
			  `status` varchar(100) DEFAULT NULL,
			  `from_id` varchar(20) NOT NULL DEFAULT '',
			  `request` text,
			  `response` text,
			  `vtiger_response` text,
			  `reqdatetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			  PRIMARY KEY (`smscountryid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");
			
			$adb->pquery("CREATE TABLE `vtiger_smsnotifier_status` (
			  `smsnotifierid` int(11) DEFAULT NULL,
			  `tonumber` varchar(20) DEFAULT NULL,
			  `status` varchar(10) DEFAULT NULL,
			  `smsmessageid` varchar(50) DEFAULT NULL,
			  `needlookup` int(1) DEFAULT '1',
			  `statusid` int(11) NOT NULL AUTO_INCREMENT,
			  `statusmessage` varchar(100) DEFAULT NULL,
			  PRIMARY KEY (`statusid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");

			$adb->pquery("ALTER TABLE `vtiger_smsnotifier_status` ADD COLUMN `smsmessageid` varchar(50) DEFAULT NULL");
			$adb->pquery("ALTER TABLE `vtiger_smsnotifier_status` ADD COLUMN `statusmessage` varchar(100) DEFAULT NULL");
			$adb->pquery("ALTER TABLE `vtiger_smsnotifier_status` ADD COLUMN `category` varchar(100) DEFAULT NULL");

			$cvcolumnlistResult = $adb->pquery("SELECT cvid, max(columnindex) as columnindex FROM vtiger_cvcolumnlist WHERE columnname like '%?%'", array('vtiger_smsnotifier'));
			if (!$adb->num_rows($cvcolumnlistResult)) {
				$cvid = $adb->query_result($cvcolumnlistResult, 0, 'cvid');
				$columnindex = $adb->query_result($cvcolumnlistResult, 0, 'columnindex');
				$adb->pquery("insert into vtiger_cvcolumnlist(cvid, columnindex, columnname) values(".$cvid.", ".++$columnindex.", 'vtiger_smsnotifier_status:status:status:SMSNotifier_status:V');");
			}

			$cvcolumnlistResult = $adb->pquery("SELECT cvid, max(columnindex) as columnindex FROM vtiger_cvcolumnlist WHERE columnname like '%?%'", array('vtiger_smsnotifier'));
			if (!$adb->num_rows($cvcolumnlistResult)) {
				$cvid = $adb->query_result($cvcolumnlistResult, 0, 'cvid');
				$columnindex = $adb->query_result($cvcolumnlistResult, 0, 'columnindex');
				$adb->pquery("insert into vtiger_cvcolumnlist(cvid, columnindex, columnname) values(".$cvid.", ".++$columnindex.", 'vtiger_smsnotifier_status:tonumber:tonumber:SMSNotifier_tonumber:V');");
			}

			$cvcolumnlistResult = $adb->pquery("SELECT cvid, max(columnindex) as columnindex FROM vtiger_cvcolumnlist WHERE columnname like '%?%'", array('vtiger_smsnotifier'));
			if (!$adb->num_rows($cvcolumnlistResult)) {
				$cvid = $adb->query_result($cvcolumnlistResult, 0, 'cvid');
				$columnindex = $adb->query_result($cvcolumnlistResult, 0, 'columnindex');
				$adb->pquery("insert into vtiger_cvcolumnlist(cvid, columnindex, columnname) values(".$cvid.", ".++$columnindex.", 'vtiger_smsnotifier_status:category:category:SMSNotifier_category:V');");
			}
			
			$tabResult = $adb->pquery("SELECT tabid FROM vtiger_tab WHERE name=", array($modulename));
			if ($adb->num_rows($tabResult)) {
				$tabid = $adb->query_result($tabResult, 0, 'tabid');
			}

			$fieldResult = $adb->pquery("SELECT tabid FROM vtiger_field WHERE columnname=? and tablename=?", array('statusmessage', 'vtiger_smsnotifier_status'));
			if (!$adb->num_rows($fieldResult)) {
				$fieldid = $adb->getUniqueID('vtiger_settings_field');
				$adb->pquery(" insert into vtiger_field (tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, defaultvalue, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable, helpinfo, summaryfield) values (".$tabid.", ".$fieldid.", 'statusmessage', 'vtiger_smsnotifier_status', 1, 21, 'statusmessage', 'statusmessage', 1, 2, '', 200, 2, 110, 1, 'V~M', 0, 0, 'BAS', 1, '', '1');");
			}
			
			$fieldResult = $adb->pquery("SELECT tabid FROM vtiger_field WHERE columnname=? and tablename=?", array('status', 'vtiger_smsnotifier_status'));
			if (!$adb->num_rows($fieldResult)) {
				$fieldid = $adb->getUniqueID('vtiger_settings_field');
				$adb->pquery(" insert into vtiger_field (tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, defaultvalue, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable, helpinfo, summaryfield) values (".$tabid.", ".$fieldid.", 'status', 'vtiger_smsnotifier_status', 1, 21, 'status', 'status', 1, 2, '', 50, 2, 110, 1, 'V~M', 0, 0, 'BAS', 1, '', '1')");
			}
			
			$fieldResult = $adb->pquery("SELECT tabid FROM vtiger_field WHERE columnname=? and tablename=?", array('smsmessageid', 'vtiger_smsnotifier_status'));
			if (!$adb->num_rows($fieldResult)) {
				$fieldid = $adb->getUniqueID('vtiger_settings_field');
				$adb->pquery(" insert into vtiger_field (tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, defaultvalue, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable, helpinfo, summaryfield) values (".$tabid.", ".$fieldid.", 'smsmessageid', 'vtiger_smsnotifier_status', 1, 21, 'smsmessageid', 'smsmessageid', 1, 2, '', 200, 2, 110, 1, 'V~M', 0, 0, 'BAS', 1, '', '1');");
			}
			
			$fieldResult = $adb->pquery("SELECT tabid FROM vtiger_field WHERE columnname=? and tablename=?", array('tonumber', 'vtiger_smsnotifier_status'));
			if (!$adb->num_rows($fieldResult)) {
				$fieldid = $adb->getUniqueID('vtiger_settings_field');
				$adb->pquery("insert into vtiger_field (tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, defaultvalue, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable, helpinfo, summaryfield) values (".$tabid.", ".$fieldid.", 'tonumber', 'vtiger_smsnotifier_status', 1, 21, 'tonumber', 'tonumber', 1, 2, '', 10, 2, 110, 1, 'V~M', 0, 0, 'BAS', 1, '', '1');");
			}

			$fieldResult = $adb->pquery("SELECT tabid FROM vtiger_field WHERE columnname=? and tablename=?", array('category', 'vtiger_smsnotifier_status'));
			if (!$adb->num_rows($fieldResult)) {
				$fieldid = $adb->getUniqueID('vtiger_settings_field');
				$adb->pquery("insert into vtiger_field (tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, defaultvalue, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable, helpinfo, summaryfield) values (".$tabid.", ".$fieldid.", 'category', 'vtiger_smsnotifier_status', 1, 21, 'category', 'category', 1, 2, '', 10, 2, 110, 1, 'V~M', 0, 0, 'BAS', 1, '', '1');");
			}


		} else if($event_type == 'module.disabled') {
			$unregisterLinks = true;

		} else if($event_type == 'module.enabled') {
			$registerLinks = true;

		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
			$adb->pquery("DELETE FROM vtiger_field WHERE tablename=?", array('vtiger_smsnotifier_status'));
			$adb->pquery("DELETE FROM vtiger_cvcolumnlist WHERE columnname like '%?%'", array('vtiger_smsnotifier_status'));

		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}

		if($unregisterLinks) {

			$smsnotifierModuleInstance = Vtiger_Module::getInstance('SMSNotifier');
			$smsnotifierModuleInstance->deleteLink("HEADERSCRIPT", "SMSNotifierCommonJS", "modules/SMSNotifier/SMSNotifierCommon.js");

			$leadsModuleInstance = Vtiger_Module::getInstance('Leads');
			$leadsModuleInstance->deleteLink('LISTVIEWBASIC', 'Send SMS');
			$leadsModuleInstance->deleteLink('DETAILVIEWBASIC', 'Send SMS');

			$contactsModuleInstance = Vtiger_Module::getInstance('Contacts');
			$contactsModuleInstance->deleteLink('LISTVIEWBASIC', 'Send SMS');
			$contactsModuleInstance->deleteLink('DETAILVIEWBASIC', 'Send SMS');

			$accountsModuleInstance = Vtiger_Module::getInstance('Accounts');
			$accountsModuleInstance->deleteLink('LISTVIEWBASIC', 'Send SMS');
			$accountsModuleInstance->deleteLink('DETAILVIEWBASIC', 'Send SMS');
		}

		if($registerLinks) {

			$smsnotifierModuleInstance = Vtiger_Module::getInstance('SMSNotifier');
			$smsnotifierModuleInstance->addLink("HEADERSCRIPT", "SMSNotifierCommonJS", "modules/SMSNotifier/SMSNotifierCommon.js");

			$leadsModuleInstance = Vtiger_Module::getInstance('Leads');

			$leadsModuleInstance->addLink("LISTVIEWBASIC", "Send SMS", "SMSNotifierCommon.displaySelectWizard(this, '\$MODULE\$');");
			$leadsModuleInstance->addLink("DETAILVIEWBASIC", "Send SMS", "javascript:SMSNotifierCommon.displaySelectWizard_DetailView('\$MODULE\$', '\$RECORD\$');");

			$contactsModuleInstance = Vtiger_Module::getInstance('Contacts');
			$contactsModuleInstance->addLink('LISTVIEWBASIC', 'Send SMS', "SMSNotifierCommon.displaySelectWizard(this, '\$MODULE\$');");
			$contactsModuleInstance->addLink("DETAILVIEWBASIC", "Send SMS", "javascript:SMSNotifierCommon.displaySelectWizard_DetailView('\$MODULE\$', '\$RECORD\$');");

			$accountsModuleInstance = Vtiger_Module::getInstance('Accounts');
			$accountsModuleInstance->addLink('LISTVIEWBASIC', 'Send SMS', "SMSNotifierCommon.displaySelectWizard(this, '\$MODULE\$');");
			$accountsModuleInstance->addLink("DETAILVIEWBASIC", "Send SMS", "javascript:SMSNotifierCommon.displaySelectWizard_DetailView('\$MODULE\$', '\$RECORD\$');");
		}



	}

	function getListButtons($app_strings, $mod_strings = false) {
		$list_buttons = Array();

		if(isPermitted('SMSNotifier','Delete','') == 'yes') $list_buttons['del'] = $app_strings[LBL_MASS_DELETE];

		return $list_buttons;
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
}
?>
