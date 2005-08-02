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
// $Id: mod_languages.php,v 1.2 2005/08/02 15:35:45 spiderr Exp $
//
	global $db, $gBitProduct, $lng;

	$show_languages= false;
	if (substr(basename($_SERVER['PHP_SELF']), 0, 8) != 'checkout') {
		$show_languages= true;
	}

	if ($show_languages == true) {
		if (!isset($lng) || (isset($lng) && !is_object($lng))) {
		include(DIR_WS_CLASSES . 'language.php');
		$lng = new language;
		}
		reset($lng->catalog_languages);
		$gBitSmarty->assign_by_ref( 'sideboxLanguages', $lng->catalog_languages );
		$gBitSmarty->assign( 'getAllParams', zen_get_all_get_params(array('language', 'currency') ) );
	}
	if( empty( $moduleTitle ) ) {
		$gBitSmarty->assign( 'moduleTitle', tra( 'Languages' ) );
	}
?>
