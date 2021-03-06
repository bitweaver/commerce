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
// $Id$
//
global $gBitDb, $gCommerceSystem, $gBitProduct;

if( empty( $gCommerceSystem ) ) {
	require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'bitcommerce_start_inc.php' );
}


$main_category_tree = new category_tree;
$row = 0;
$box_categories_array = array();

// don't build a tree when no categories
$check_categories = $gBitDb->getOne("select `categories_id` from " . TABLE_CATEGORIES . " c, " . TABLE_PRODUCT_TYPES . " pt, " . TABLE_PRODUCT_TYPES_TO_CATEGORY . " ptc where pt.`type_master_type` = '3' and ptc.`product_type_id` = pt.`type_id` and c.`categories_id` = ptc.`category_id` and c.`categories_status`=1");
if ($check_categories) {
	$_template->tpl_vars['sideboxDocumentCategories'] = new Smarty_variable( $main_category_tree->zen_category_tree('3') );
	if( empty( $moduleTitle ) ) {
		$_template->tpl_vars['moduleTitle'] = new Smarty_variable(  'Documents' );
	}
}

?>
