<?php
//
// +----------------------------------------------------------------------+
// | bitcommerce                                                          |
// +----------------------------------------------------------------------+
// | Copyright (c) 2007 bitcommerce.org                                   |
// |                                                                      |
// | http://www.bitcommerce.org                                           |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license        |
// +----------------------------------------------------------------------+
//  $Id$
//

require('includes/application_top.php');
require_once( BITCOMMERCE_PKG_PATH.'classes/CommerceCommission.php' );

if( count( $_GET ) > 2 || count( $_POST ) > 2 ) {
	$gBitUser->verifyTicket();
}

$commissionManager = new CommerceProductCommission();

$listHash  = array();
$listHash['commissions_delay'] = $gBitSystem->getConfig( 'com_commissions_delay', '60' );
$endEpoch = strtotime( "-".$listHash['commissions_delay']." days" );
$listHash['commissions_due'] = $endEpoch;

$date = getdate( $endEpoch );
$periodEndDate = $date['year'].'-'.str_pad( $date['mon'], 2, '0', STR_PAD_LEFT ).'-'.str_pad( $date['mday'], 2, '0', STR_PAD_LEFT );
$gBitSmarty->assign( 'periodEndDate', $periodEndDate );

if( !empty( $_REQUEST['save_payment'] ) ) {
	$commissionManager->storePayment( $_REQUEST );
}

if( $commissionsDue = $commissionManager->getCommissions( $listHash ) ) {
	$gBitSmarty->assign_by_ref( 'commissionsDue', $commissionsDue );
}

$gBitSystem->display( 'bitpackage:bitcommerce/admin_commissions.tpl', 'Commissions Report' , array( 'display_mode' => 'admin' ));
?>
