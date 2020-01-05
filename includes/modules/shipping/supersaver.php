<?php
// +----------------------------------------------------------------------+
// | bitcommerce,	http://www.bitcommerce.org                            |
// +----------------------------------------------------------------------+
// | Copyright (c) 2019 bitcommerce.org                                   |
// | This source file is subject to version 3.0 of the GPL license        |
// | Portions Copyrigth (c) 2005 http://www.zen-cart.com                  |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+

require_once( BITCOMMERCE_PKG_PATH.'classes/CommercePluginShippingBase.php' );

class supersaver extends CommercePluginShippingBase {

	function __construct() {
		parent::__construct();
		$this->title = tra( 'SuperSaver Shipping' );
		$this->description = tra( 'Offer fixed rate (or free!) shipping for orders within a specified amount.' );
	}

	function quote( $pShipHash ) {
		global $currencies;

		if( $quotes = $this->isEligibleShipper( $pShipHash ) ) {

			$min = $this->getConfig( 'MODULE_SHIPPING_SUPERSAVER_MIN' );
			$max = $this->getConfig( 'MODULE_SHIPPING_SUPERSAVER_MAX' );

			$shippedTotal = $pShipHash['shipping_value'];

			if( !empty( $min ) && $shippedTotal < MODULE_SHIPPING_SUPERSAVER_MIN ) {
				$quotes['error'] = tra( 'You must spend at least '. $currencies->format( MODULE_SHIPPING_SUPERSAVER_MIN ).' to get SuperSaver Shipping.' ). ' <a href="'.zen_href_link(FILENAME_SHOPPING_CART).'">'.tra( 'Update Cart' ).'</a>';
			} elseif( !empty( $max ) && $shippedTotal > MODULE_SHIPPING_SUPERSAVER_MAX ) {
				// no quote for you!
				$quotes['error'] = tra( 'SuperSaver Shipping only applies to orders up to '.$currencies->format( MODULE_SHIPPING_SUPERSAVER_MAX ) ). ' <a href="'.zen_href_link(FILENAME_SHOPPING_CART).'">'.tra( 'Update Cart' ).'</a>';
			} else {
				if( $this->isInternationOrder( $pShipHash ) ) {
					if( $this->isEnabled( 'MODULE_SHIPPING_SUPERSAVER_INTL' ) ) {
						$desc = tra( MODULE_SHIPPING_SUPERSAVER_DESC ).' '.tra( MODULE_SHIPPING_SUPERSAVER_INTL_DESC );
						$quotes['methods'][] = array(
													'id' => $this->code,
													'title' => trim( $desc ),
													'code' => 'supersaverintl',
													'transit_time' => MODULE_SHIPPING_SUPERSAVER_INTL_TRANSIT_TIME,
													'cost' => MODULE_SHIPPING_SUPERSAVER_INTL_COST + MODULE_SHIPPING_SUPERSAVER_HANDLING
												);
					}
				} elseif( $this->isEnabled( 'MODULE_SHIPPING_SUPERSAVER_DOMESTIC' ) ) {
					$desc = tra( MODULE_SHIPPING_SUPERSAVER_DESC ).' '.tra( MODULE_SHIPPING_SUPERSAVER_DOMESTIC_DESC );
					$quotes['methods'][] = array(
												'id' => $this->code,
												'title' => trim( $desc ),
												'code' => 'supersaver',
												'transit_time' => MODULE_SHIPPING_SUPERSAVER_DOMESTIC_TRANSIT_TIME,
												'cost' => MODULE_SHIPPING_SUPERSAVER_DOMESTIC_COST + MODULE_SHIPPING_SUPERSAVER_HANDLING
												);
				}
			}
		}

		return $quotes;
	}


	/**
	* rows for com_configuration table as associative array of column => value
	*/
	protected function config() {
		$i = 3;
		return array_merge( parent::config(), array( 
			$this->getModuleKeyTrunk().'_MIN' => array(
				'configuration_title' => 'Minimum Cart Value',
				'configuration_value' => '30.00',
				'configuration_description' => 'What is the minimum cart total to get supersaver shipping?',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_MAX' => array(
				'configuration_title' => 'Maximum Cart Value',
				'configuration_value' => '',
				'configuration_description' => 'What is the maximum cart total to get supersaver shipping?',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_DOMESTIC_COST' => array(
				'configuration_title' => 'SuperSaver Shipping Cost',
				'configuration_value' => '4.99',
				'configuration_description' => 'What is the SuperSaver Shipping cost?',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_HANDLING' => array(
				'configuration_title' => 'Handling Fee',
				'configuration_value' => '0',
				'configuration_description' => 'Handling fee for this shipping method.',
				'sort_order' => $i++,
				'configuration_group_id' => '6',
			),
			$this->getModuleKeyTrunk().'_DESC' => array(
				'configuration_title' => 'SuperSaver Shipping Description',
				'configuration_value' => 'SuperSaver',
				'configuration_description' => 'Text to accompany all SuperSaver quotes',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_DOMESTIC' => array(
				'configuration_title' => 'Domestic SuperSaver Shipping',
				'configuration_value' => 'True',
				'configuration_description' => 'Allow domestic SuperSaver shipping - the same country as the <a href=\"configuration.php?gID=5&cID=123&action=edit\">Default Country</a>.',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
				'set_function' => "zen_cfg_select_option(array('True', 'False'), ",
			),
			$this->getModuleKeyTrunk().'_DOMESTIC_DESC' => array(
				'configuration_title' => 'Domestic SuperSaver Shipping Description',
				'configuration_value' => 'Domestic',
				'configuration_description' => 'Text to accompany SuperSaver domestic quote',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_DOMESTIC_TRANSIT_TIME' => array(
				'configuration_title' => 'Domestic SuperSaver Shipping Transit Time',
				'configuration_value' => '1-2 weeks',
				'configuration_description' => 'Transit time to accompany SuperSaver domestic quote',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_INTL' => array(
				'configuration_title' => 'International SuperSaver Shipping',
				'configuration_value' => 'True',
				'configuration_description' => 'Allow international SuperSaver shipping - countries outside of the <a href=\"configuration.php?gID=5&cID=123&action=edit\">Default Country</a>.',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
				'set_function' => "zen_cfg_select_option(array('True', 'False'), ",
			),
			$this->getModuleKeyTrunk().'_INTL_DESC' => array(
				'configuration_title' => 'International SuperSaver Shipping Description',
				'configuration_value' => 'International',
				'configuration_description' => 'Text to accompany SuperSaver international quote',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_INTL_TRANSIT_TIME' => array(
				'configuration_title' => 'International SuperSaver Shipping Transit Time',
				'configuration_value' => '4-8 weeks',
				'configuration_description' => 'Transit time to accompany SuperSaver international quote',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
			$this->getModuleKeyTrunk().'_INTL_COST' => array(
				'configuration_title' => 'SuperSaver Shipping Cost',
				'configuration_value' => '14.99',
				'configuration_description' => 'What is the SuperSaver Shipping International cost?',
				'configuration_group_id' => '6',
				'sort_order' => $i++,
			),
		) );
	}
}
