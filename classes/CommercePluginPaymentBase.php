<?php
/**
 * @package bitcommerce
 * @author spiderr <spiderr@bitweaver.org>
 *
 * Copyright (c) 2013 bitweaver.org
 * All Rights Reserved. See below for details and a complete list of authors.
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE. See http://www.gnu.org/licenses/gpl.html for details
 */

require_once( BITCOMMERCE_PKG_PATH.'classes/CommercePluginBase.php' );

abstract class CommercePluginPaymentBase extends CommercePluginBase {

	var $paymentOrderId;
	var $mPaymentReference;

	public function __construct() {
		parent::__construct();
	}

	protected function getModuleType() {
		return 'payment';
	}

	protected function clearSessionDetails() {
		foreach( $this->getSessionVars() as $var ) {
			$_SESSION[$var] = $this->$var;
		}	
	}

	protected function saveSessionDetails() {
		foreach( $this->getSessionVars() as $var ) {
			$_SESSION[$var] = $this->$var;
		}	
	}

	protected function logTransaction( $pResponseHash, $pOrder ) {
		global $messageStack, $gBitUser;
		$this->mDb->query( "INSERT INTO " . TABLE_PUBS_CREDIT_CARD_LOG . " (orders_id, customers_id, ref_id, trans_result, trans_auth_code, trans_message, trans_amount, trans_date) values ( ?, ?, ?, ?, '-', ?, ?, 'NOW' )", array( $pResponseHash['orders_id'], $gBitUser->mUserId, BitBase::getParameter( $pResponseHash, 'ref_id' ), (int)BitBase::getParameter( $pResponseHash, 'trans_result' ), 'cust_id: '.$gBitUser->mUserId.' - '.$pOrder->customer['email_address'].':'.BitBase::getParameter( $pResponseHash, 'trans_message' ), number_format($pOrder->info['total'], 2,'.','') ) );
	}

	function getTransactionReference() {
		// default implementation
		return NULL;
	}

	// Default methods that should be overridden in derived classes
	protected function getSessionVars() {
		return array();
	}

	function selection() {
		return array( 'id' => $this->code, 'module' => $this->title );
	}

	function processPayment( &$pPaymentParameters, &$pOrder ) {
		$this->mErrors['process_payment'] = 'This modules has not implemented the ::processPayment method. ('.$this->code.')';
		return FALSE;
	}

	function confirmation( $pPaymentParameters ) {
		return false;
	}

	function process_button( $pPaymentParameters ) {
		return false;
	}

	function verifyPayment( &$pPaymentParameters, &$pOrder ) {
		return false;
	}

	function get_error() {
		return false;
	}

	function javascript_validation() {
		return false;
	}

	/**
	* rows for com_configuration table as associative array of column => value
	*/
	protected function config() {
		$i = 10;
		return array_merge( parent::config(), array( 
			$this->getModuleKeyTrunk().'_ORDER_STATUS_ID' => array(
				'configuration_title' => 'Initial Order Status',
				'configuration_description' => 'Orders made with this payment module will be set to the status.',
				'sort_order' => $i++,
				'configuration_value' => '20',
				'set_function' => 'zen_cfg_pull_down_order_statuses(',
				'use_function' => 'zen_get_order_status_name',
			),
			$this->getModuleKeyTrunk().'_ZONE' => array(
				'configuration_title' => 'Payment Zone',
				'configuration_description' => 'If a zone is selected, only enable this payment method for that zone.',
				'sort_order' => $i++,
				'set_function' => 'zen_cfg_pull_down_zone_classes(',
				'use_function' => 'zen_get_zone_class_title',
			),
		) );
	}

}
