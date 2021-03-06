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

define('NAVBAR_TITLE', tra( 'Login Time Out' ) );
define('HEADING_TITLE', tra( 'Login Time Out' ) );

define('TEXT_INFORMATION', 'We\'re sorry but for your protection,
  due to the long delay while either checking out,
  or on a secure page, the session has timed out.
  If you were placing an order, please
  <a href="' . FILENAME_LOGIN . '">Login</a>
  and your Shopping Cart will be restored. You may then go back to the Checkout and complete your final purchases.
  If you had completed an order and wish to review it' .
  (DOWNLOAD_ENABLED == 'true' ? ', or had a download and wish to retrieve it' : '') . ',
  please go to your <a href="' . zen_href_link(FILENAME_ACCOUNT, '', 'SSL') . '">My Account</a> page to view your order.
  ');
?>