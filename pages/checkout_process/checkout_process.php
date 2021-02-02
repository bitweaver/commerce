<?php
// +----------------------------------------------------------------------+
// | bitcommerce,	http://www.bitcommerce.org                            |
// +----------------------------------------------------------------------+
// | Copyright (c) 2013 bitcommerce.org                                   |
// | This source file is subject to version 3.0 of the GPL license        |
// | Portions Copyrigth (c) 2005 http://www.zen-cart.com                  |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+

if ($gBitCustomer->mCart->count_contents() <= 0) {
	// if there is nothing in the customers cart, redirect them to the shopping cart page
	zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
} elseif( !$gBitUser->isRegistered() ) {
	// if the customer is not logged on, redirect them to the login page
	$_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_PAYMENT));
	zen_redirect(FILENAME_LOGIN);
} elseif( empty( $_SESSION['shipping'] )  && ($gBitCustomer->mCart->get_content_type() != 'virtual') ) {
	// if no shipping method has been selected, redirect the customer to the shipping method selection page
	zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
} elseif( !$gBitCustomer->mCart->verifyCheckout() ) {
	// Stock Check and more....
	$messageStack->add('header', 'Please update your order ...', 'error');
	zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
}

if( !empty( $_POST['comments'] ) ) {
	$_SESSION['comments'] = zen_db_prepare_input($_POST['comments']);
}

require(BITCOMMERCE_PKG_CLASS_PATH.'CommerceOrder.php');
$order = new order();

// load the before_process function from the payment modules
if( !$order->process( array_merge( $_SESSION, $_REQUEST ) ) ) {
	zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, NULL, 'SSL', true, false));
}

$gBitCustomer->mCart->reset(true);

$order->otClearPosts();

// unregister session variables used during checkout
foreach( array( 'sendto', 'billto', 'shipping', 'payment', 'comments' ) as $key ) {
	if( isset( $_SESSION[$key] ) ) {
		unset( $_SESSION[$key] );
	}
}

zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));

