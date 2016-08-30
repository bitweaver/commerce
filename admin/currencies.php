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

  require_once(DIR_WS_FUNCTIONS . 'localization.php');

  $currencies = new currencies();

  $action = (isset($_GET['action']) ? $_GET['action'] : '');

  if (zen_not_null($action)) {
    switch ($action) {
      case 'insert':
      case 'save':
        if (isset($_GET['cID'])) $currency_id = zen_db_prepare_input($_GET['cID']);
        $title = zen_db_prepare_input($_POST['title']);
        $code = strtoupper(zen_db_prepare_input($_POST['code']));
        $symbol_left = zen_db_prepare_input($_POST['symbol_left']);
        $symbol_right = zen_db_prepare_input($_POST['symbol_right']);
        $decimal_point = zen_db_prepare_input($_POST['decimal_point']);
        $thousands_point = zen_db_prepare_input($_POST['thousands_point']);
        $decimal_places = zen_db_prepare_input($_POST['decimal_places']);
        $currency_value = zen_db_prepare_input($_POST['currency_value']);

        $sql_data_array = array('title' => $title,
                                'code' => $code,
                                'symbol_left' => $symbol_left,
                                'symbol_right' => $symbol_right,
                                'decimal_point' => $decimal_point,
                                'thousands_point' => $thousands_point,
                                'decimal_places' => $decimal_places,
                                'currency_value' => $currency_value);

        if ($action == 'insert') {
          $gBitDb->associateInsert(TABLE_CURRENCIES, $sql_data_array);
          $currency_id = zen_db_insert_id( TABLE_CURRENCIES, 'currencies_id' );
        } elseif ($action == 'save') {
          $gBitDb->associateUpdate(TABLE_CURRENCIES, $sql_data_array, array( 'currencies_id'=> $currency_id ) );
        }

        if (isset($_POST['default']) && ($_POST['default'] == 'on')) {
			$gCommerceSystem->storeConfig( 'DEFAULT_CURRENCY', $code );
        }

        zen_redirect(zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $currency_id));
        break;
      case 'deleteconfirm':
        // demo active test
        if (zen_admin_demo()) {
          $_GET['action']= '';
          $messageStack->add_session(ERROR_ADMIN_DEMO, 'caution');
          zen_redirect(zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page']));
        }
        $currencies_id = zen_db_prepare_input($_GET['cID']);

        $currency = $gBitDb->Execute("SELECT `currencies_id`
                                  FROM " . TABLE_CURRENCIES . "
                                  WHERE `code` = '" . DEFAULT_CURRENCY . "'");


        if ($currency->fields['currencies_id'] == $currencies_id) {
          $gBitDb->Execute("UPDATE " . TABLE_CONFIGURATION . "
                        SET `configuration_value` = ''
                        WHERE `configuration_key` = 'DEFAULT_CURRENCY'");
        }

        $gBitDb->Execute("DELETE FROM " . TABLE_CURRENCIES . "
                      WHERE `currencies_id` = '" . (int)$currencies_id . "'");

        zen_redirect(zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page']));
        break;
      case 'bulk':
	  	$currencies->bulkImport( $_REQUEST['bulk_currencies'] );
	  	break;
      case 'update':
        $server_used = CURRENCY_SERVER_PRIMARY;

		$output = currency_update_quotes();
		foreach( $output as $result ) {
            $messageStack->add_session($result['message'], $result['result']);
		}

        zen_redirect(zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $_GET['cID']));
        break;
      case 'delete':
        // demo active test
        if (zen_admin_demo()) {
          $_GET['action']= '';
          $messageStack->add_session(ERROR_ADMIN_DEMO, 'caution');
          zen_redirect(zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $_GET['cID']));
        }
        $currencies_id = zen_db_prepare_input($_GET['cID']);

        $currency = $gBitDb->Execute("SELECT `code`
                                  FROM " . TABLE_CURRENCIES . "
                                  WHERE `currencies_id` = '" . (int)$currencies_id . "'");

        $remove_currency = true;
        if ($currency->fields['code'] == DEFAULT_CURRENCY) {
          $remove_currency = false;
          $messageStack->add(ERROR_REMOVE_DEFAULT_CURRENCY, 'error');
        }
        break;
    }
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
</head>
<body>
<!-- header //-->
<?php require(DIR_FS_ADMIN_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<div class="row">
	<div class="col-md-8">
		<table class="table table-hover width100p">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CURRENCY_NAME; ?></td>
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_CURRENCY_CODES; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_CURRENCY_VALUE; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  $currency_query_raw = "select `currencies_id`, `title`, `code`, `symbol_left`, `symbol_right`, `decimal_point`, `thousands_point`, `decimal_places`, `last_updated`, `currency_value` from " . TABLE_CURRENCIES . " order by `title`";
  $currency_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $currency_query_raw, $currency_query_numrows);
  $currency = $gBitDb->Execute($currency_query_raw);
  while (!$currency->EOF) {
    if ((!isset($_GET['cID']) || (isset($_GET['cID']) && ($_GET['cID'] == $currency->fields['currencies_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
      $cInfo = new objectInfo($currency->fields);
	}

    echo '<tr '.((!empty( $cInfo ) && is_object($cInfo) && ($currency->fields['currencies_id'] == $cInfo->currencies_id) )  ? ' class="info" ' : '').' onclick="document.location.href=\'' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $currency->fields['currencies_id'] . '&action=edit') . '\'">' . "\n";

    if (DEFAULT_CURRENCY == $currency->fields['code']) {
      echo '                <td class="dataTableContent"><b>' . $currency->fields['title'] . ' (' . TEXT_DEFAULT . ')</b></td>' . "\n";
    } else {
      echo '                <td class="dataTableContent">' . $currency->fields['title'] . '</td>' . "\n";
    }
?>
                <td class="dataTableContent"><?php echo $currency->fields['code']; ?></td>
                <td class="dataTableContent" align="right"><?php echo  $currencies->format($currency->fields['currency_value'], false, $currency->fields['code'] ); ?></td>
                <td class="dataTableContent" align="right"><?php if (isset($cInfo) && is_object($cInfo) && ($currency->fields['currencies_id'] == $cInfo->currencies_id) ) { echo zen_image(DIR_WS_IMAGES . 'icon_arrow_right.gif'); } else { echo '<a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $currency->fields['currencies_id']) . '">' . zen_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
              </tr>
<?php
    $currency->MoveNext();
  }
?>
				</td>
              </tr>
            </table>

            <div><?php echo $currency_split->display_count($currency_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_CURRENCIES); ?>, <?php echo $currency_split->display_links($currency_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></div>
<?php
  if (empty($action)) {
?>
			<div>
                 <?php if (CURRENCY_SERVER_PRIMARY) { echo '<a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id . '&action=update') . '">' . zen_image_button('button_update_currencies.gif', IMAGE_UPDATE_CURRENCIES) . '</a>'; } ?> <?php echo '<a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id . '&action=new') . '">' . zen_image_button('button_new_currency.gif', IMAGE_NEW_CURRENCY) . '</a>'; ?>
			</div>
<?php
  }
?>

<?=zen_draw_form_admin('currencies', FILENAME_CURRENCIES, 'page=' . $_GET['page'] . (isset($cInfo) ? '&cID=' . $cInfo->currencies_id : '') . '&action=bulk')?>
<fieldset>
	<legend>Bulk Import Currencies</legend>
				Here you can paste in currencies values. All values should be relative to the US Dollar, and have the following example format:
<textarea class="width95p" rows="10" name="bulk_currencies">
USD United States Dollars                 1.0000000000          1.0000000000
EUR Euro                                  1.2186191347          0.8206009339
GBP United Kingdom Pounds                 1.7684362222          0.5654713399
CAD Canada Dollars                        0.8253906445          1.2115475341
AUD Australia Dollars                     0.7620792397          1.3121995036
JPY Japan Yen                             0.0089098765        112.2350011215
</textarea>
<span class="help-block">[three letter abbreviatinon] [Name separated by at most one space] [dollar/currency] [currency/dollar]</span>

<br/><input type="submit" name="bulk_submit" value="Bulk Update" />
</fieldset>
</form>

	</div>
	<div class="col-md-4">

<?php
  $heading = array();
  $contents = array();
  
  switch ($action) {
    case 'new':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_NEW_CURRENCY . '</b>');

      $contents = array('form' => zen_draw_form_admin('currencies', FILENAME_CURRENCIES, 'page=' . $_GET['page'] . (isset($cInfo) ? '&cID=' . $cInfo->currencies_id : '') . '&action=insert'));
      $contents[] = array('text' => TEXT_INFO_INSERT_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_TITLE . '<br>' . zen_draw_input_field('title'));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_CODE . '<br>' . zen_draw_input_field('code'));
      $contents[] = array('text' => '<br>' . tra( 'Symbol Left:' ) . '<br>' . zen_draw_input_field('symbol_left'));
      $contents[] = array('text' => '<br>' . tra( 'Symbol Right:' ) . '<br>' . zen_draw_input_field('symbol_right'));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_DECIMAL_POINT . '<br>' . zen_draw_input_field('decimal_point'));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_THOUSANDS_POINT . '<br>' . zen_draw_input_field('thousands_point'));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_DECIMAL_PLACES . '<br>' . zen_draw_input_field('decimal_places'));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_VALUE . '<br>' . zen_draw_input_field('currency_value'));
      $contents[] = array('text' => '<br>' . zen_draw_checkbox_field('default') . ' ' . TEXT_INFO_SET_AS_DEFAULT);
      $contents[] = array('align' => 'center', 'text' => '<br>' . zen_image_submit('button_insert.gif', IMAGE_INSERT) . ' <a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $_GET['cID']) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'edit':        
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_EDIT_CURRENCY . '</b>');
      
      $contents = array('form' => zen_draw_form_admin('currencies', FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id . '&action=save'));
      $contents[] = array('text' => TEXT_INFO_EDIT_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_TITLE . '<br>' . zen_draw_input_field('title', $cInfo->title,'','text',$reinsert_value=false));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_CODE . '<br>' . zen_draw_input_field('code', $cInfo->code));
      $contents[] = array('text' => '<br>' . tra( 'Symbol Left:' ) . '<br>' . zen_draw_input_field('symbol_left', htmlspecialchars($cInfo->symbol_left)));
      $contents[] = array('text' => '<br>' . tra( 'Symbol Right:' ) . '<br>' . zen_draw_input_field('symbol_right', htmlspecialchars($cInfo->symbol_right)));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_DECIMAL_POINT . '<br>' . zen_draw_input_field('decimal_point', $cInfo->decimal_point));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_THOUSANDS_POINT . '<br>' . zen_draw_input_field('thousands_point', $cInfo->thousands_point));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_DECIMAL_PLACES . '<br>' . zen_draw_input_field('decimal_places', $cInfo->decimal_places));
      $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_VALUE . '<br>' . zen_draw_input_field('currency_value', $cInfo->currency_value));
      $contents[] = array('text' => zen_draw_checkbox_field('default', 'on', (DEFAULT_CURRENCY == $cInfo->code), NULL, TEXT_INFO_SET_AS_DEFAULT ) );
      $contents[] = array('align' => 'center', 'text' => '<br>' . zen_image_submit('button_update.gif', IMAGE_UPDATE) . ' <a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_CURRENCY . '</b>');

      $contents[] = array('text' => tra( 'Are you sure you want to delete this currency?' ) );
      $contents[] = array('text' => '<br><b>' . $cInfo->title . '</b>');
      $contents[] = array('align' => 'center', 'text' => '<br>' . (($remove_currency) ? '<a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id . '&action=deleteconfirm') . '">' . zen_image_button('button_delete.gif', IMAGE_DELETE) . '</a>' : '') . ' <a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id) . '">' . zen_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (is_object($cInfo)) {
        $heading[] = array('text' => '<b>' . $cInfo->title . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id . '&action=edit') . '">' . zen_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . zen_href_link_admin(FILENAME_CURRENCIES, 'page=' . $_GET['page'] . '&cID=' . $cInfo->currencies_id . '&action=delete') . '">' . zen_image_button('button_delete.gif', IMAGE_DELETE) . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_TITLE . ' ' . $cInfo->title);
        $contents[] = array('text' => TEXT_INFO_CURRENCY_CODE . ' ' . $cInfo->code);
        $contents[] = array('text' => '<br>' . tra( 'Symbol Left:' ) . ' ' . $cInfo->symbol_left);
        $contents[] = array('text' => tra( 'Symbol Right:' ) . ' ' . $cInfo->symbol_right);
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_DECIMAL_POINT . ' ' . $cInfo->decimal_point);
        $contents[] = array('text' => TEXT_INFO_CURRENCY_THOUSANDS_POINT . ' ' . $cInfo->thousands_point);
        $contents[] = array('text' => TEXT_INFO_CURRENCY_DECIMAL_PLACES . ' ' . $cInfo->decimal_places);
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_LAST_UPDATED . ' ' . zen_date_short($cInfo->last_updated));
        $contents[] = array('text' => TEXT_INFO_CURRENCY_VALUE . ' ' . number_format($cInfo->currency_value, 8));
        $contents[] = array('text' => '<br>' . TEXT_INFO_CURRENCY_EXAMPLE . '<br>' . $currencies->format('30', false, DEFAULT_CURRENCY) . ' = ' . $currencies->format('30', true, $cInfo->code));
      }
      break;
  }

  if ( (zen_not_null($heading)) && (zen_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>

	</div>
</div>

<!-- footer //-->
<?php require(DIR_FS_ADMIN_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_FS_ADMIN_INCLUDES . 'application_bottom.php'); ?>
