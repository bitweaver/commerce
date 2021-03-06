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

define('TEXT_MAIN',$gBitSystem->getConfig( 'commerce_main_text', '') );
define('TEXT_GREETING_GUEST', tra( 'Welcome <span class="greetUser">Guest!</span> Would you like to <a href="%s">log yourself in</a>?' ) );
define('TEXT_GREETING_PERSONAL', tra( 'Hello <span class="greetUser">%s</span>! Would you like to see our <a href="%s">newest additions</a>?' ) );

define('TEXT_INFORMATION', '
<h2>To begin customizing the look of your Zen Cart&trade; CSS Template, please follow the instructions
  below. </h2>
<h3> 1. Correct Any Error Messages </h3>
<p>If there are any error or warning messages displayed at the very top of the
  page, you should correct them before proceeding. Errors are messages with a
  solid <span class="messageStackError">red</span> background. The most commonly
  encountered error is a warning that the installation directory exists at /such/and/such/an/address.
  We recommend that you delete or rename the install directory for your site\'s
  security. </p>
<p>Several checks are automatically performed during the installation process
  by <strong>Zen Cart&trade;</strong> to insure you have the proper configuration for
  your online store. For more information on how to correct other errors or warnings,
  see the documentation at <a href="http://www.zen-cart.com/"><strong>Zen Cart&trade;
  Docs</strong></a>. </p>
<h3>2. Customizing the Look of Your Zen Cart&trade; Shop </h3>
<h4>A. Create a Directory for Your Template</h4>
<p>First, you need to make a new directory for your custom template files. Open:
  includes/templates/ and add a folder. (You may call it anything you want, but we
  will be calling it template_custom.) Copy all of directories and their files
  from <strong>zencss</strong> and place them in your new <strong>template_custom</strong>
  folder. When you finish you should have one file called; <strong>template_info.php</strong>,
  and the following directories inside <strong>template_custom</strong>:</p>
<ul>
  <li>buttons</li>
  <li>common</li>
  <li>css</li>
  <li>images</li>
  <li>popup_image</li>
  <li>sideboxes</li>
  <li>templates</li>
</ul>
<p>Next open the <strong>template_info.php</strong> in your favorite text editor.
  Change $template_name = \'CSS Layout\'; to $template_name = \'Custom Theme\';,
  add your own version, author and description. Now go to the Admin and change
  to your template_custom. <strong>(Admin &gt; Tools &gt; Template Selection)</strong></p>
<p align="right"><a href="index.php?main_page=page_2">Colors, Text &amp; the Header &raquo;</a></p>
<p> For more tips and tricks visit the <a href="http://www.zen-cart.com" target="_blank">Zen
  Cart support site</a>. This shop is running on Zen Cart&trade; version <strong>' .
  PROJECT_VERSION_NAME . '</strong> This is a demonstration of the Zen Cart&trade; project,
  products shown are for informational purposes only. <strong>Any demonstration
  products purchased will not be billed or be delivered.</strong>All descriptions,
  prices, and other information is fictional. </p>
');


if( !defined( 'TABLE_HEADING_NEW_PRODUCTS' ) ) {
	define('TABLE_HEADING_NEW_PRODUCTS', tra( 'New Products For %s' ) );
}
if( !defined( 'TABLE_HEADING_UPCOMING_PRODUCTS' ) ) {
	define('TABLE_HEADING_UPCOMING_PRODUCTS', tra( 'Upcoming Products' ) );
}
if( !defined( 'TABLE_HEADING_DATE_EXPECTED' ) ) {
	define('TABLE_HEADING_DATE_EXPECTED', tra( 'Date Expected' ) );
}

if ( ($category_depth == 'products') || (isset($_GET['manufacturers_id'])) ) {
  define('HEADING_TITLE', tra( 'Available Products' ) );
  define('TABLE_HEADING_IMAGE', tra( 'Product Image' ) );
  define('TABLE_HEADING_MODEL', tra( 'Model' ) );
  define('TABLE_HEADING_PRODUCTS', tra( 'Product Name' ) );
  define('TABLE_HEADING_MANUFACTURER', tra( 'Manufacturer' ) );
  define('TABLE_HEADING_QUANTITY', tra( 'Quantity' ) );
  define('TABLE_HEADING_PRICE', tra( 'Price' ) );
  define('TABLE_HEADING_WEIGHT', tra( 'Weight' ) );
  define('TABLE_HEADING_BUY_NOW', tra( 'Buy Now' ) );
  define('TEXT_NO_PRODUCTS', tra( 'There are no products to list in this category.' ) );
  define('TEXT_NO_PRODUCTS2', tra( 'There is no product available from this manufacturer.' ) );
  define('TEXT_NUMBER_OF_PRODUCTS', tra( 'Number of Products: ' ) );
  define('TEXT_SHOW', tra( '<b>Sort by:</b> ' ) );
  define('TEXT_BUY', tra( 'Buy 1 \'' ) );
  define('TEXT_NOW', tra( '\' now' ) );
  define('TEXT_ALL_CATEGORIES', tra( 'All Categories' ) );
  define('TEXT_ALL_MANUFACTURERS', tra( 'All Manufacturers' ) );
} elseif ($category_depth == 'top') {
  define('HEADING_TITLE', $gBitSystem->getConfig( 'siteTitle' ).' Shopping'); /*Replace this line with the headline you would like for your shop. For example: Welcome to My SHOP!*/
} elseif ($category_depth == 'nested') {
  define('HEADING_TITLE', tra( 'Categories' ) );
}
?>
