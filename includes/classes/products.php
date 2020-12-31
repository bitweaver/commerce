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

  class products {
	var $modules, $selected_module;

// class constructor
	function __construct($module = '') {
	}

	function get_products_in_category($zf_category_id, $zf_recurse=true, $zf_product_ids_only=false) {
		global $gBitDb;
		$za_products_array = array();
		// get top level products
		$zp_products_query = "select ptc.*, pd.`products_name`
		                      from " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc
		                      left join " . TABLE_PRODUCTS_DESCRIPTION . " pd
		                      on ptc.`products_id` = pd.`products_id`
		                      and pd.`language_id` = '" . (int)$_SESSION['languages_id'] . "'
		                      where ptc.`categories_id`='" . $zf_category_id . "'
		                      order by pd.`products_name`";

		$zp_products = $gBitDb->Execute($zp_products_query);
		while (!$zp_products->EOF) {
		  if ($zf_product_ids_only) {
		    $za_products_array[] = $zp_products->fields['products_id'];
		  } else {
		    $za_products_array[] = array('id' => $zp_products->fields['products_id'],
		                                 'text' => $zp_products->fields['products_name']);
		  }
		  $zp_products->MoveNext();
		}
		if ($zf_recurse) {
		  $zp_categories_query = "select `categories_id` from " . TABLE_CATEGORIES . "
		                          where `parent_id` = '"   . $zf_category_id . "'";
		  $zp_categories = $gBitDb->Execute($zp_categories_query);
		  while (!$zp_categories->EOF) {
		    $za_sub_products_array = $this->get_products_in_category($zp_categories->fields['categories_id'], true, $zf_product_ids_only);
		    $za_products_array = array_merge($za_products_array, $za_sub_products_array);
		    $zp_categories->MoveNext();
		  }
		}
		return $za_products_array;
	}

	function products_name($zf_product_id) {
		global $gBitDb;
		$zp_product_name_query = "select `products_name` from " . TABLE_PRODUCTS_DESCRIPTION . "
		                          where `language_id` = '" . $_SESSION['languages_id'] . "'
		                          and `products_id` = '" . (int)$zf_product_id . "'";
		$zp_product_name = $gBitDb->Execute($zp_product_name_query);
		$zp_product_name = $zp_product_name->fields['products_name'];
		return $zp_product_name;
	}

	function get_admin_handler($type) {
		  return $this->get_handler($type) . '.php';
		}

		function get_handler($type) {
		global $gBitDb;

		$sql = "select `type_handler` from " . TABLE_PRODUCT_TYPES . " where `type_id` = '" . $type . "'";
		$handler = $gBitDb->Execute($sql);
		  return $handler->fields['type_handler'];
	}

	function get_allow_add_to_cart($zf_product_id) {
		global $gBitDb;

		$sql = "SELECT `allow_add_to_cart` FROM " . TABLE_PRODUCTS . " cp INNER JOIN " . TABLE_PRODUCT_TYPES . " cpt ON(cp.`products_type`=cpt.`type_id`) where `products_id` = ?";
		return $gBitDb->getOne($sql, array( $zf_product_id ) );
	}

  }
?>
