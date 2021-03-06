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
global $gBitDb, $gCommerceSystem, $gBitProduct, $lng;

require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'bitcommerce_start_inc.php' );

$show_languages= false;
if (substr(basename($_SERVER['SCRIPT_NAME']), 0, 8) != 'checkout') {
	$show_languages= true;
}

if ($show_languages == true) {
	if (!isset($lng) || (isset($lng) && !is_object($lng))) {
		require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'classes/language.php' );
		$lng = new language;
	}
	reset($lng->catalog_languages);
	$_template->tpl_vars['sideboxLanguages'] = new Smarty_variable( $lng->catalog_languages );
	$baseUrl = preg_replace( '/[\?&]?language=[a-z]{2}/', '', $_SERVER['REQUEST_URI'] );
	$baseUrl .= strpos( $baseUrl, '?' ) ? '&amp;' : '?' ;
	$_template->tpl_vars['sideboxLanguagesBaseUrl'] = new Smarty_variable( $baseUrl );
}
if( empty( $moduleTitle ) ) {
	$_template->tpl_vars['moduleTitle'] = new Smarty_variable(  'Languages' );
}
?>
