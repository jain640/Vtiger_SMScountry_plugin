<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
ini_set('display_errors', '1');

require_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once(dirname(__FILE__) . '/../src/Controllers/MessagesController.php');
include_once(dirname(__FILE__) . '/../src/Configuration.php');
include_once(dirname(__FILE__) . '/../src/Models/Message.php');
include_once(dirname(__FILE__) . '/../src/APIHelper.php');
include_once(dirname(__FILE__) . '/../src/APIException.php');

use SMSCountryMessagingLib\Controllers\MessagesController;
use SMSCountryMessagingLib\Models\Message;

class SMSNotifier_SMScountry_Provider implements SMSNotifier_ISMSProvider_Model {

	private $userName;
	private $password;
	private $parameters = array();

	const SERVICE_URI = 'https://restapi.smscountry.com/v0.1/Accounts';
	private static $REQUIRED_PARAMETERS = array('host');

	/**
	 * Function to get provider name
	 * @return <String> provider name
	 */
	public function getName() {
		return 'SMScountry';
	}

	/**
	 * Function to get required parameters other than (userName, password)
	 * @return <array> required parameters list
	 */
	public function getRequiredParams() {
		return self::$REQUIRED_PARAMETERS;
	}

	/**
	 * Function to get service URL to use for a given type
	 * @param <String> $type like SEND, PING, QUERY
	 */
	public function getServiceURL($type = false) {
		if($type) {
			switch(strtoupper($type)) {
				case self::SERVICE_AUTH: return  self::SERVICE_URI . '/'.$this->userName.'/auth';
				case self::SERVICE_SEND: return  self::SERVICE_URI . '/'.$this->userName.'/SMSes';
				case self::SERVICE_QUERY: return self::SERVICE_URI . '/'.$this->userName.'/querymsg';
			}
		}
		return false;
	}

	/**
	 * Function to set authentication parameters
	 * @param <String> $userName
	 * @param <String> $password
	 */
	public function setAuthParameters($userName, $password) {
		$this->userName = $userName;
		$this->password = $password;
	}

	/**
	 * Function to set non-auth parameter.
	 * @param <String> $key
	 * @param <String> $value
	 */
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}

	/**
	 * Function to get parameter value
	 * @param <String> $key
	 * @param <String> $defaultValue
	 * @return <String> value/$default value
	 */
	public function getParameter($key, $defaultValue = false) {
		if(isset($this->parameters[$key])) {
			return $this->parameters[$key];
		}
		return $defaultValue;
	}

	/**
	 * Function to prepare parameters
	 * @return <Array> parameters
	 */
	protected function prepareParameters() {
		$params = array('user' => $this->userName, 'pwd' => $this->password);
		foreach (self::$REQUIRED_PARAMETERS as $key) {
			$params[$key] = $this->getParameter($key);
		}
		return $params;
	}
	
	/**
	 * Function to handle SMS Send operation
	 * @param <String> $message
	 * @param <Mixed> $toNumbers One or Array of numbers
	 */
	public function getsenderid() {

		$params = $this->prepareParameters();
		$serviceURL = $this->getServiceURL(self::SERVICE_SEND);
		
		$controller = new MessagesController($params['user'], $params['pwd'], $params['host']);
		$response = $controller->getSenderId();
		$results = array();
		foreach($response->SenderIds as $SenderIds)
		{
			$results[]  = $SenderIds->SenderId;
		}
		return $results;
	}

	/**
	 * Function to handle SMS Send operation
	 * @param <String> $message
	 * @param <Mixed> $toNumbers One or Array of numbers
	 */
	public function send($message, $toNumbers) {
		global $adb, $notifierid;
		try{
			$results = array();
			if(!is_array($toNumbers)) {
				$toNumbers = array($toNumbers);
			}
			$params = $this->prepareParameters();
			$selected_ids = json_decode($_REQUEST['selected_ids']);

			$params['Text'] = $message;
			$params['Number'] = $toNumbers;
			$from_number = $_REQUEST['post_id'];
			$controller = new MessagesController($params['user'], $params['pwd'], $params['host']);
			
			$messageObj = new Message($params['Number'], $from_number, $params['Text']);
			
			//getMergedDescription($params['Text'], $selected_ids[0], $_REQUEST['source_module']);
			// Send the message
			if(count($toNumbers)>1)
			{
				$response = $controller->createbulkMessage($messageObj);
			}
			else{
				$response = $controller->createMessage($messageObj);
			}
			$adb->pquery("INSERT INTO vtiger_smscountry_log (message,from_id,request,response, reqdatetime) VALUES('".addslashes(json_encode($message))."', '".$from_number."', '".addslashes(json_encode($controller))."', '".addslashes(json_encode($response))."', '".date("Y-m-d H:i:s")."')");
			if($response->Success!='True')
			{
				$result = array( 'error' => true, 'statusmessage' => $response->Message);
				$results[] = $result;
				return $results;
			}
			$mdr_id1 = $response->MessageUUIDs;
			$Message1 = $response->Message;
			if(!is_array($mdr_id1))
			{
				$mdr_id1 = $response->MessageUUID;
				$result = array( 'error' => false, 'statusmessage' => '' );
				$result['id'] = trim($mdr_id1);
				$result['to'] = $toNumbers[0];
				$result['status'] = self::MSG_STATUS_PROCESSING;
				$result['statusmessage'] = $Message1;
				$mdr_record = $controller->getMessageLookup($mdr_id1); 
				$MessageStatus = $mdr_record->SMS->Status;
				if(trim($MessageStatus)!='')
					$result['status'] = self::MSG_STATUS_DISPATCHED;
					//$result['status'] = $Message;
				$results[] = $result;
				
			}
			else
			{
				foreach($mdr_id1 as $key => $ID) {
					$result = array( 'error' => false, 'statusmessage' => '' );
					$result['id'] = trim($ID);
					$result['to'] = $toNumbers[$key];
					$result['status'] = self::MSG_STATUS_PROCESSING;
					$result['statusmessage'] = $Message1;
					$mdr_record = $controller->getMessageLookup($ID); 
					$MessageStatus = $mdr_record->SMS->Status;
					if(trim($MessageStatus)!='')
						$result['status'] = self::MSG_STATUS_PROCESSING;//$result['status'] = $Message;
					$results[] = $result;
				}	
			}

			$adb->pquery("INSERT INTO vtiger_smscountry_log (message,from_id,request,response, vtiger_response, reqdatetime) VALUES('".addslashes(json_encode($message))."', '".$from_number."', '".addslashes(json_encode($controller))."', '".addslashes(json_encode($response))."', '".addslashes(json_encode($results))."', '".date("Y-m-d H:i:s")."')");
			return $results;
		}
		catch(Exception $e)
		{
			$result = array( 'error' => true, 'statusmessage' => $e->getMessage());
			$results[] = $result;
			return $results;
		}
	}

	/**
	 * Function to get query for status using messgae id
	 * @param <Number> $messageId
	 */
	public function query($messageId) {
		global $adb;
		$params = $this->prepareParameters();
		
		$controller = new MessagesController($params['user'], $params['pwd'], $params['host']);
		$mdr_record = $controller->getMessageLookup($messageId); 
		if(!$mdr_record->Success)
		{
			$result = array( 'error' => true, 'statusmessage' => $mdr_record->Message, 'needlookup' => 1);
			$adb->pquery("INSERT INTO vtiger_smscountry_log (message,request,response, vtiger_response, reqdatetime) VALUES('".addslashes(json_encode($messageId))."','".addslashes(json_encode($controller))."', '".addslashes(json_encode($mdr_record))."', '".addslashes(json_encode($result))."', '".date("Y-m-d H:i:s")."')");
			return $result;
		}
		$Message = $mdr_record->SMS->Status;
		$result = array( 'error' => false, 'needlookup' => 1 );
		$result['status'] = self::MSG_STATUS_DISPATCHED;
		$result['needlookup'] = 0;
		$result['statusmessage'] = $mdr_record->Message;

		$adb->pquery("INSERT INTO vtiger_smscountry_log (message,request,response, vtiger_response, reqdatetime) VALUES('".addslashes(json_encode($messageId))."','".addslashes(json_encode($controller))."', '".addslashes(json_encode($mdr_record))."', '".addslashes(json_encode($result))."', '".date("Y-m-d H:i:s")."')");
		return $result;

		if(preg_match("/ERR: (.*)/", $response, $matches)) {
			$result['error'] = true;
			$result['needlookup'] = 0;
			$result['statusmessage'] = $matches[0];
		} else if(preg_match("/ID: ([^ ]+) Status: ([^ ]+)/", $response, $matches)) {
			$result['id'] = trim($matches[1]);
			$status = trim($matches[2]);

			// Capture the status code as message by default.
			$result['statusmessage'] = "CODE: $status";
			if($status === '1') {
				$result['status'] = self::MSG_STATUS_PROCESSING;
			} else if($status === '2') {
				$result['status'] = self::MSG_STATUS_DISPATCHED;
				$result['needlookup'] = 0;
			}
		}
		return $result;
	}
}
?>
