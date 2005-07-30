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
// $Id: mod_featured.php,v 1.1 2005/07/30 15:08:15 spiderr Exp $
//
	global $db, $gBitProduct, $currencies;

    $random_featured_products_query = "select p.products_id, p.products_image, pd.products_name
                           from " . TABLE_PRODUCTS . " p
                           left join " . TABLE_FEATURED . " f on p.products_id = f.products_id
                           left join " . TABLE_PRODUCTS_DESCRIPTION . " pd on p.products_id = pd.products_id
                           where p.products_id = f.products_id and p.products_id = pd.products_id and p.products_status = '1' and f.status = '1' and pd.language_id = '" . (int)$_SESSION['languages_id'] . "'
                           order by pd.products_name desc
                           limit " . MAX_RANDOM_SELECT_FEATURED_PRODUCTS;

   	$listHash['max_records'] = 1;
	$listHash['sort_mode'] = 'random';
	$listHash['featured'] = TRUE;
	if( $sideboxFeature = $gBitProduct->getList( $listHash ) ) {
		$sideboxFeature = current( $sideboxFeature );
		$whats_new_price = zen_get_products_display_price($sideboxFeature['products_id']);

		$sideboxFeature['display_price'] = $currencies->display_price($sideboxFeature['products_price'], zen_get_tax_rate($sideboxFeature['products_tax_class_id']));
		if( $sideboxFeature['specials_new_products_price'] = zen_get_products_special_price($sideboxFeature['products_id']) ) {
			$sideboxFeature['display_special_price'] = $currencies->display_price($sideboxFeature['specials_new_products_price'], zen_get_tax_rate($sideboxFeature['products_tax_class_id']));
		}
		$gBitSmarty->assign_by_ref( 'sideboxFeature', $sideboxFeature );
	}
?>
