<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 The zen-cart developers                           |
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
//  $Id: preview_info.php,v 1.1 2008/07/14 13:06:45 lsces Exp $
//

	if (zen_not_null($_POST)) {
		$pInfo = new objectInfo($_POST);
		$products_name = $_POST['products_name'];
		$products_description = $_POST['products_description'];
		$products_url = $_POST['products_url'];
		if ( isset( $pInfo->products_image_att ) && is_numeric( $pInfo->products_image_att ) ) {
			$products_image = '';
			$products_image_name = $pInfo->products_image_att;
		} else {
			$products_image = $products_image_name;
			$products_image_name = DIR_WS_CATALOG_IMAGES . $products_image_name;
		}
	} else {
		$products = $gBitDb->Execute("select p.`products_id`, pd.`language_id`, pd.`products_name`,
									pd.`products_description`, pd.`products_url`, p.`products_quantity`,
									p.`products_model`, p.`products_manufacturers_model`, p.`products_image`, p.`products_price`, p.`products_cogs`, p.`products_virtual`,
									p.`products_weight`, p.`products_date_added`, p.products_last_modified,
									p.`products_date_available`, p.`products_status, p.`manufacturers_id`, p.`suppliers_id`, p.`products_barcode` ,
									p.`products_quantity`_order_min, p.`products_quantity`_order_units, p.`products_priced_by_attribute`,
									p.`product_is_free`, p.product_is_call, p.`products_quantity`_mixed,
									p.product_is_always_free_ship, p.`products_qty_box_status`, p.`products_quantity_order_max`,
									p.`products_sort_order`
								from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd
								where p.`products_id` = pd.`products_id`
								and p.`products_id` = '" . (int)$_GET['pID'] . "'");

		$pInfo = new objectInfo($products->fields);
		if ( is_numeric( $pInfo->products_image ) ) {
			$products_image_name = STORAGE_PKG_URL . BITCOMMERCE_STORAGE_NAME . '/' . ($pInfo->products_image % 1000) . '/' . $pInfo->products_image . '/medium.jpg';
		} else {
			$products_image_name = $pInfo->products_image;
		}
	}

	$form_action = (isset($_GET['pID'])) ? 'update_product' : 'insert_product';

    echo zen_draw_form_admin($form_action, $type_admin_handler, 'cPath=' . $cPath . (isset($_GET['product_type']) ? '&product_type=' . $_GET['product_type'] : '') . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . '&action=' . $form_action . (isset($_GET['page']) ? '&page=' . $_GET['page'] : ''), 'post', 'enctype="multipart/form-data"');

    $languages = zen_get_languages();
    for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
      if (isset($_GET['read']) && ($_GET['read'] == 'only')) {
        $pInfo->products_name = zen_get_products_name($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_description = zen_get_products_description($pInfo->products_id, $languages[$i]['id']);
        $pInfo->products_url = zen_get_products_url($pInfo->products_id, $languages[$i]['id']);
      } else {
        $pInfo->products_name = zen_db_prepare_input($products_name[$languages[$i]['id']]);
        $pInfo->products_description = zen_db_prepare_input($products_description[$languages[$i]['id']]);
        $pInfo->products_url = zen_db_prepare_input($products_url[$languages[$i]['id']]);
      }

?>
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo zen_image(DIR_WS_CATALOG_LANGUAGES . $languages[$i]['directory'] . '/images/' . $languages[$i]['image'], $languages[$i]['name']) . '&nbsp;' . $pInfo->products_name; ?></td>
            <td class="pageHeading" align="right"><?php echo $currencies->format($pInfo->products_price) . ($pInfo->products_virtual == 1 ? '<span class="errorText">' . '<br />' . TEXT_VIRTUAL_PREVIEW . '</span>' : '') . ($pInfo->product_is_always_free_ship == 1 ? '<span class="errorText">' . '<br />' . TEXT_FREE_SHIPPING_PREVIEW . '</span>' : '') . ($pInfo->products_priced_by_attribute == 1 ? '<span class="errorText">' . '<br />' . TEXT_PRODUCTS_PRICED_BY_ATTRIBUTES_PREVIEW . '</span>' : '') . ($pInfo->product_is_free == 1 ? '<span class="errorText">' . '<br />' . TEXT_PRODUCTS_IS_FREE_PREVIEW . '</span>' : '') . ($pInfo->product_is_call == 1 ? '<span class="errorText">' . '<br />' . TEXT_PRODUCTS_IS_CALL_PREVIEW . '</span>' : '') . ($pInfo->products_qty_box_status == 0 ? '<span class="errorText">' . '<br />' . TEXT_PRODUCTS_QTY_BOX_STATUS_PREVIEW . '</span>' : '') . ($pInfo->products_priced_by_attribute == 1 ? '<br />' . CommerceProduct::getDisplayPrice($_GET['pID']) : ''); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main">
          <?php
//auto replace with defined missing image
            if ($products_image_name == '' and PRODUCTS_IMAGE_NO_IMAGE_STATUS == '1') {
              echo zen_image(DIR_WS_CATALOG_IMAGES . PRODUCTS_IMAGE_NO_IMAGE, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="right" hspace="5" vspace="5"') . $pInfo->products_description;
            } else {
              echo zen_image( $products_image_name, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT, 'align="right" hspace="5" vspace="5"') . $pInfo->products_description;
            }
          ?>
        </td>
      </tr>
<?php
      if ($pInfo->products_url) {
?>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
      <tr>
        <td class="main"><?php echo sprintf(TEXT_PRODUCT_MORE_INFORMATION, $pInfo->products_url); ?></td>
      </tr>
<?php
      }
?>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<?php
      if ($pInfo->products_date_available > date('Y-m-d')) {
?>
      <tr>
        <td align="center" class="smallText"><?php echo sprintf(TEXT_PRODUCT_DATE_AVAILABLE, zen_date_long($pInfo->products_date_available)); ?></td>
      </tr>
<?php
      } else {
?>
      <tr>
        <td align="center" class="smallText"><?php echo sprintf(TEXT_PRODUCT_DATE_ADDED, zen_date_long($pInfo->products_date_added)); ?></td>
      </tr>
<?php
      }
?>
      <tr>
        <td><?php echo zen_draw_separator('pixel_trans.gif', '1', '10'); ?></td>
      </tr>
<?php
    }

    if (isset($_GET['read']) && ($_GET['read'] == 'only')) {
      if (isset($_GET['origin'])) {
        $pos_params = strpos($_GET['origin'], '?', 0);
        if ($pos_params != false) {
          $back_url = substr($_GET['origin'], 0, $pos_params);
          $back_url_params = substr($_GET['origin'], $pos_params + 1);
        } else {
          $back_url = $_GET['origin'];
          $back_url_params = '';
        }
      } else {
        $back_url = FILENAME_CATEGORIES;
        $back_url_params = 'cPath=' . $cPath . '&pID=' . $pInfo->products_id;
      }
?>
      <tr>
        <td align="right"><?php echo '<a href="' . zen_href_link_admin($back_url, $back_url_params, 'NONSSL') . '">' . zen_image_button('button_back.gif', IMAGE_BACK) . '</a>'; ?></td>
      </tr>
<?php
    } else {
?>
      <tr>
        <td align="right" class="smallText">
<?php
/* Re-Post all POST'ed variables */
      reset($_POST);
      while (list($key, $value) = each($_POST)) {
        if (!is_array($_POST[$key])) {
          echo zen_draw_hidden_field($key, htmlspecialchars(stripslashes($value)));
        }
      }

      $languages = zen_get_languages();
      for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
        echo zen_draw_hidden_field('products_name[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_name[$languages[$i]['id']])));
        echo zen_draw_hidden_field('products_description[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_description[$languages[$i]['id']])));
        echo zen_draw_hidden_field('products_url[' . $languages[$i]['id'] . ']', htmlspecialchars(stripslashes($products_url[$languages[$i]['id']])));
      }
      echo zen_draw_hidden_field('products_image', stripslashes($products_image));

      echo zen_image_submit('button_back.gif', IMAGE_BACK, 'name="edit"') . '&nbsp;&nbsp;';

      if (isset($_GET['pID'])) {
        echo zen_image_submit('button_update.gif', IMAGE_UPDATE);
      } else {
        echo zen_image_submit('button_insert.gif', IMAGE_INSERT);
      }
      echo '&nbsp;&nbsp;<a href="' . zen_href_link_admin(FILENAME_CATEGORIES, 'cPath=' . $cPath . (isset($_GET['pID']) ? '&pID=' . $_GET['pID'] : '') . (isset($_GET['page']) ? '&page=' . $_GET['page'] : '')) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>';
?>
        </td>
      </tr>
    </table></form>
<?php
    }
?>
