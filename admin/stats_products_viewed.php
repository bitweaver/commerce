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
//  $Id$
//
  require('includes/application_top.php');
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css"/>
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS"/>
<script type="text/javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_FS_ADMIN_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<header>
	<h1><?php echo HEADING_TITLE; ?></h1>
</header>
<table class="width100p"><tr><td><table class="width100p">
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table>
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_NUMBER; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo TABLE_HEADING_VIEWED; ?>&nbsp;</td>
              </tr>
<?php
  if (isset($_GET['page']) && ($_GET['page'] > 1)) $rows = $_GET['page'] * MAX_DISPLAY_SEARCH_RESULTS_REPORTS - MAX_DISPLAY_SEARCH_RESULTS_REPORTS;
  $rows = 0;
  $products_query_raw = "select p.`products_id`, pd.`products_name`, pd.`products_viewed`, l.`name` from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd, " . TABLE_LANGUAGES . " l where p.`products_id` = pd.`products_id` and l.`languages_id` = pd.`language_id` order by pd.`products_viewed` DESC";
  $products_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $products_query_raw, $products_query_numrows);
  $products = $gBitDb->Execute($products_query_raw);
  while (!$products->EOF) {

// only show low stock on products that can be added to the cart
    if ($zc_products->get_allow_add_to_cart($products->fields['products_id']) == 'Y') {
      $rows++;

      if (strlen($rows) < 2) {
        $rows = '0' . $rows;
      }
      $cPath = zen_get_product_path($products->fields['products_id']);
?>
              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href='<?php echo zen_href_link_admin(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products->fields['products_id']); ?>'">
                <td class="dataTableContent" align="right"><?php echo $products->fields['products_id']; ?>&nbsp;&nbsp;</td>
                <td class="dataTableContent"><?php echo '<a href="' . zen_href_link_admin(FILENAME_CATEGORIES, 'cPath=' . $cPath . '&pID=' . $products->fields['products_id']) . '">' . $products->fields['products_name'] . '</a> (' . $products->fields['name'] . ')'; ?></td>
                <td class="dataTableContent" align="center"><?php echo $products->fields['products_viewed']; ?>&nbsp;</td>
              </tr>
<?php
    }
    $products->MoveNext();
  }
?>
            </table></td>
          </tr>
          <tr>
            <td colspan="3"><table>
              <tr>
                <td class="smallText" valign="top"><?php echo $products_split->display_count($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
                <td class="smallText" align="right"><?php echo $products_split->display_links($products_query_numrows, MAX_DISPLAY_SEARCH_RESULTS_REPORTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_FS_ADMIN_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_FS_ADMIN_INCLUDES . 'application_bottom.php'); ?>
