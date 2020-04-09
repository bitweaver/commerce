<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
// $Id$
//

require_once( BITCOMMERCE_PKG_PATH.'classes/CommercePluginPaymentCardBase.php' );

class cc extends CommercePluginPaymentCardBase {

	public function __construct() {
		parent::__construct();
		$this->adminTitle = tra( 'Generic Credit Card' );
		$this->description = tra( 'This method collects credit card information, but does not process it' );
	}

	// class constructor
	function javascript_validation() {
		$js = '	if (payment_value == "' . $this->code . '") {' . "\n" .
			'		var payment_owner = document.checkout_payment.payment_owner.value;' . "\n" .
			'		var payment_number = document.checkout_payment.payment_number.value;' . "\n";

		if (MODULE_PAYMENT_CC_COLLECT_CVV == 'True')	{
			$js .= '		var cc_cvv = document.checkout_payment.cc_cvv.value;' . "\n";
		}

		$js .= '		if (payment_owner == "" || payment_owner.length < ' . CC_OWNER_MIN_LENGTH . ') {' . "\n" .
			 '			error_message = error_message + "' . MODULE_PAYMENT_CC_TEXT_JS_CC_OWNER . '";' . "\n" .
			 '			error = 1;' . "\n" .
			 '		}' . "\n" .
			 '		if (payment_number == "" || payment_number.length < ' . CC_NUMBER_MIN_LENGTH . ') {' . "\n" .
			 '			error_message = error_message + "' . MODULE_PAYMENT_CC_TEXT_JS_CC_NUMBER . '";' . "\n" .
			 '			error = 1;' . "\n" .
			 '		}' . "\n";

		if (MODULE_PAYMENT_CC_COLLECT_CVV == 'True')	{
			$js .= '		if (cc_cvv == "" || cc_cvv.length < ' . CC_CVV_MIN_LENGTH . ') {' . "\n" .
				 '			error_message = error_message + "' . MODULE_PAYMENT_CC_TEXT_JS_CC_CVV . '";' . "\n" .
				 '			error = 1;' . "\n" .
				 '		}' . "\n";
		}

		$js .= '	}' . "\n";


		return $js;
	}

	function selection() {
		global $order;

		for ($i=1; $i<13; $i++) {
			$expires_month[] = array('id' => sprintf('%02d', $i), 'text' => strftime('%B',mktime(0,0,0,$i,1,2000)));
		}

		$today = getdate();
		for ($i=$today['year']; $i < $today['year']+10; $i++) {
			$expires_year[] = array('id' => strftime('%y',mktime(0,0,0,1,1,$i)), 'text' => strftime('%Y',mktime(0,0,0,1,1,$i)));
		}

		$selection = array('id' => $this->code,
							 'module' => $this->title,
							 'fields' => array(
											array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_OWNER,
											 'field' => zen_draw_input_field('payment_owner', BitBase::getParameter( $_SESSION, 'payment_owner', $order->billing['firstname'] . ' ' . $order->billing['lastname'] ), 'autocomplete="cc-name"' )),
											array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_NUMBER,
											 'field' => zen_draw_input_field('payment_number', BitBase::getParameter( $_SESSION, 'payment_number' ), ' autocomplete="cc-number" ', 'number')),
											array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_EXPIRES,
											 'field' => zen_draw_pull_down_menu('payment_expires_month', $expires_month, ' class="input-small" autocomplete="cc-exp-month" ') . '&nbsp;' . zen_draw_pull_down_menu('payment_expires_year', $expires_year, ' class="input-small" autocomplete="cc-exp-year" '))));

		if( MODULE_PAYMENT_CC_COLLECT_CVV == 'True' ) {
			$selection['fields'][] = array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_CVV,
											 'field' => zen_draw_input_field( 'cc_cvv', BitBase::getParameter( $_SESSION, 'cc_cvv' ), ' autocomplete="cc-csc" ', 'number' ));
		}
		return $selection;
	}

	function verifyPayment( &$pPaymentParameters, &$pOrder ) {
		if( empty( $pPaymentParameters['payment_number'] ) ) {
			$error = tra( 'Please enter a credit card number.' );
		} elseif( $this->verifyCreditCard( $pPaymentParameters['payment_number'], $pPaymentParameters['payment_expires_month'], $pPaymentParameters['payment_expires_year'], $pPaymentParameters['cc_cvv'] ) ) {
			$ret = TRUE;
		} else {
			foreach( array( 'payment_owner', 'payment_number', 'payment_expires_month', 'payment_expires_year', 'cc_cvv' ) as $key ) {
				$_SESSION[$key] = BitBase::getParameter( $pPaymentParameters, $key );
			}
			$_SESSION['pfp_error'] = $this->mErrors;
			zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, NULL, 'SSL', true, false));
		}
		return $ret;
	}


	function confirmation( $pPaymentParameters ) {
		global $_POST;

		$confirmation = array('title' => $this->title . ': ' . $this->payment_type,
								'fields' => array(array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_OWNER,
														'field' => $_POST['payment_owner']),
												array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_NUMBER,
														'field' => substr($this->payment_number, 0, 4) . str_repeat('X', (strlen($this->payment_number) - 8)) . substr($this->payment_number, -4)),
												array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_EXPIRES,
														'field' => strftime('%B, %Y', mktime(0,0,0,$_POST['payment_expires_month'], 1, '20' . $_POST['payment_expires_year'])))));

		if (MODULE_PAYMENT_CC_COLLECT_CVV == 'True')	{
			$confirmation['fields'][] = array('title' => MODULE_PAYMENT_CC_TEXT_CREDIT_CARD_CVV,
											 'field' => $_POST['cc_cvv']);
		}
		return $confirmation;
	}

	function process_button( $pPaymentParameters ) {
		global $_POST;

		$process_button_string = zen_draw_hidden_field('payment_owner', $_POST['payment_owner']) .
								 zen_draw_hidden_field('payment_expires', $_POST['payment_expires_month'] . $_POST['payment_expires_year']) .
								 zen_draw_hidden_field('payment_type', $this->payment_type) .
								 zen_draw_hidden_field('payment_number', $this->payment_number);
		if (MODULE_PAYMENT_CC_COLLECT_CVV == 'True')	{
			$process_button_string .= zen_draw_hidden_field('cc_cvv', $_POST['cc_cvv']);
		}

		return $process_button_string;
	}

	function processPayment( &$pPaymentParameters, &$pOrder ) {
		global $_POST, $order;

		$ret = FALSE;

		$logHash = $this->logTransactionPrep( $pPaymentParams, $pOrder );

		if( $ret = self::verifyPayment ( $pPaymentParams, $pOrder ) ) {

			$ret = TRUE;
			$logHash['is_success'] = 'y';
			$logHash['payment_status'] = 'Success';
			$order->info['payment_expires'] = $p['payment_expires'];
			$order->info['payment_type'] = $_POST['payment_type'];
			$order->info['payment_owner'] = $_POST['payment_owner'];
			$order->info['cc_cvv'] = $_POST['cc_cvv'];

			if (MODULE_PAYMENT_CC_STORE_NUMBER == 'True') {
				$order->info['payment_number'] = $_POST['payment_number'];
			} else {
				$order->info['payment_number'] = $this->privatizeCard( $_POST['payment_number'] );
			}
		}

		if( !empty( $logHash ) ) {
			$this->logTransaction( $logHash );
		}
		return $ret;
	}

	function install() {
		parent::install();
		$this->mDb->query("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `set_function`, `date_added`) values ('Collect & store the CVV number', 'MODULE_PAYMENT_CC_COLLECT_CVV', 'True', 'Do you want to collect the CVV number. Note: If you do the CVV number will be stored in the database in an encoded format.', '6', '0', 'zen_cfg_select_option(array(''True'', ''False''), ', 'NOW')");
		$this->mDb->query("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `set_function`, `date_added`) values ('Store the Credit Card Number', 'MODULE_PAYMENT_CC_STORE_NUMBER', 'True', 'Do you want to store the Credit Card Number. Note: The Credit Card Number will be stored unenecrypted, and as such may represent a security problem', '6', '0', 'zen_cfg_select_option(array(''True'', ''False''), ', 'NOW')");
		$this->mDb->query("insert into " . TABLE_CONFIGURATION . " (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_group_id`, `sort_order`, `use_function`, `set_function`, `date_added`) values ('Payment Zone', 'MODULE_PAYMENT_CC_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', 'NOW')");
		$this->CompleteTrans();
	}

	public function keys() {
		return array_merge( 
					array_keys( $this->config() ), 
					array('MODULE_PAYMENT_CC_COLLECT_CVV', 'MODULE_PAYMENT_CC_STORE_NUMBER', 'MODULE_PAYMENT_CC_ZONE') 
				);
	}
}
