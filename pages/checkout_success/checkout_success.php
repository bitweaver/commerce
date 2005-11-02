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
// $Id: checkout_success.php,v 1.2 2005/11/02 22:26:54 spiderr Exp $
//
global $zv_orders_id;
?>
<?php echo zen_draw_form('order', zen_href_link(FILENAME_CHECKOUT_SUCCESS, 'action=update', 'SSL')); ?>
<table  width="100%" border="0" cellspacing="2" cellpadding="2">
  <tr>
    <td>
		<div class="heading">
			<h1><?php echo HEADING_TITLE; ?></h1>
		</div>
<?php
	global $gBitSmarty;
	print $gBitSmarty->fetch( 'bitpackage:bitcommerce/order_success.tpl' );
?>
  </td>
<?php
  if (CUSTOMERS_PRODUCTS_NOTIFICATION_STATUS == '1') {
?>
  <tr>
    <td class="main">
<?php
    if ($global->fields['global_product_notifications'] != '1') {
      echo TEXT_NOTIFY_PRODUCTS . '<br /><p class="productsNotifications">';

      $products_displayed = array();
      for ($i=0, $n=sizeof($products_array); $i<$n; $i++) {
        if (!in_array($products_array[$i]['id'], $products_displayed)) {
          echo zen_draw_checkbox_field('notify[]', $products_array[$i]['id']) . ' ' . $products_array[$i]['text'] . '<br />';
          $products_displayed[] = $products_array[$i]['id'];
        }
      }

      echo '</p>';
    }
?>
    </td>
  </tr>
<?php } ?>
  <tr>
    <td class="main"><?php echo TEXT_YOUR_ORDER_NUMBER . '<a href="' . zen_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $zv_orders_id, 'SSL', false); ?>"><?=$zv_orders_id?></a></td>
  </tr>
  <tr>
    <td class="plainBox"><?php  echo TEXT_SEE_ORDERS . '<br /><br />' . TEXT_CONTACT_STORE_OWNER;?></td>
  </tr>
  <tr>
    <td align="center"><h3><?php echo TEXT_THANKS_FOR_SHOPPING; ?></h3></td>
  </tr>
<?php
  $gv_query="select `amount` from " . TABLE_COUPON_GV_CUSTOMER . "
             where `customer_id`='".$_SESSION['customer_id'] . "'";

  $gv_result = $db->Execute($gv_query);

  if (!$gv_result->EOF) {
    if ($gv_result->fields['amount'] > 0) {
?>
  <tr>
    <td align="center" class="main"><?php echo GV_HAS_VOUCHERA; echo zen_href_link(FILENAME_GV_SEND); echo GV_HAS_VOUCHERB; ?></td>
  </tr>
<?php
    }
  }
?>
  <tr>
    <td class="main">
      <?php if (DOWNLOAD_ENABLED == 'true') include(DIR_WS_MODULES . 'downloads.php'); ?>
    </td>
  </tr>
  <tr>
    <td align="right"><?php echo zen_image_submit(BUTTON_IMAGE_CONTINUE, BUTTON_CONTINUE_ALT); ?></td>
  </tr>
</table></form>
