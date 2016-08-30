<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce																			 |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers													 |
// |																																			|
// | http://www.zen-cart.com/index.php																		|
// |																																			|
// | Portions Copyright (c) 2003 osCommerce															 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,			 |
// | that is bundled with this package in the file LICENSE, and is				|
// | available through the world-wide-web at the following url:					 |
// | http://www.zen-cart.com/license/2_0.txt.														 |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to			 |
// | license@zen-cart.com so we can mail you a copy immediately.					|
// +----------------------------------------------------------------------+
// $Id$
//

require_once(DIR_FS_MODULES . 'require_languages.php');

// if the customer is not logged on, redirect them to the time out page
if (!$_SESSION['customer_id']) {
	zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}

// confirm where link came from
if (!strstr($_SERVER['HTTP_REFERER'], FILENAME_CHECKOUT_CONFIRMATION)) {
//		zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT,'','SSL'));
}

// keep session timeout of payment to top file before any processing
if (!isset($_SESSION['payment']) && !$credit_covers) {
	zen_redirect(zen_href_link(FILENAME_DEFAULT));
}

// load selected payment module
require( BITCOMMERCE_PKG_PATH . 'classes/CommercePaymentManager.php' );
$payment_modules = new CommercePaymentManager($_SESSION['payment']);

// load the selected shipping module
require( BITCOMMERCE_PKG_PATH.'classes/CommerceShipping.php');
$shipping_modules = new CommerceShipping($_SESSION['shipping']);

require(BITCOMMERCE_PKG_PATH.'classes/CommerceOrder.php');
$order = new order;

require(DIR_FS_CLASSES . 'order_total.php');
$order_total_modules = new order_total;
$order_totals = $order_total_modules->pre_confirmation_check( $_REQUEST );
$order_totals = $order_total_modules->process( $_REQUEST );

$gBitDb->mDb->StartTrans();
// load the before_process function from the payment modules
if( !$payment_modules->processPayment( $_REQUEST, $order ) ) {
	zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, NULL, 'SSL', true, false));
}

$insert_id = $order->create($order_totals, 2);
$order->create_add_products($insert_id);

$payment_modules->after_order_create($insert_id);
$order->send_order_email($insert_id);

$gBitDb->mDb->completeTrans();

