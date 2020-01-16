<?php
/**
 * @package bitcommerce
 * @author spiderr <spiderr@bitweaver.org>
 * Copyright (c) 2020 bitweaver.org, All Rights Reserved
 * This source file is subject to the 2.0 GNU GENERAL PUBLIC LICENSE. 
 *
 * Base class for Order and ShoppingCart. Used for quoting shipping and fulfillment
 *
 */

abstract class CommerceOrderBase extends BitBase {

	public $mProductObjects = array();
	public $total;
	protected $weight;
	public $free_shipping_item;
	public $free_shipping_weight;
	public $free_shipping_price;
	public $contents;

	protected $mOtClasses = array();
	protected $mOtProcessModules = array();

	abstract public function getDelivery();
	abstract public function getProductHash( $pProductsKey );
	abstract public function calculate( $pForceRecalculate=FALSE );

	// can take a productsKey or a straight productsId
	function getProductObject( $pProductsMixed ) {
		$productsId = zen_get_prid( $pProductsMixed );
		if( BitBase::verifyId( $productsId ) ) {
			if( !isset( $this->mProductObjects[$productsId] ) ) {
				if( $this->mProductObjects[$productsId] = bc_get_commerce_product( $productsId ) ) {
					$ret = &$this->mProductObjects[$productsId];
				}
			}
		}
		return $this->mProductObjects[$productsId];
	}

	function getWeight() {
		if( empty( $this->weight ) ) {
			$this->calculate();
		}
		return( $this->weight );
	}

	function getFieldLocalized( $pField ) {
		$ret = $this->getField( $pField );
		if( is_numeric( $ret ) ) {
			if( $this->getField( 'currency', DEFAULT_CURRENCY ) != DEFAULT_CURRENCY ) {
				global $currencies;
				$paymentDecimal = $currencies->get_decimal_places( $this->getField( 'currency' ) );
				$ret = number_format( $ret * $this->getField('currency_value'), $paymentDecimal, '.', '' ) ;
			}
		}
		return $ret;
	}

	public function getShipmentValue() {
		$this->calculate();
		$ret = $this->subtotal - $this->free_shipping_prices();

		//$ret = (float)($order->subtotal > 0 ? $order->subtotal + $order->getField( 'tax' ) : 0);
		//$ret = (!empty( $_SESSION['cart']->total	) ? $_SESSION['cart']->total: 0);

		return $ret;
	}

/*
	public function getShipmentPackages() {
		$ret = array();
// Some code From FedEx, not really helpful
					foreach ($products as $product) {
						$dimensions_query = "SELECT products_length, products_width, products_height, products_ready_to_ship, products_dim_type FROM " . TABLE_PRODUCTS . " 
																 WHERE products_id = " . (int)$product['id'] . " 
																 AND products_length > 0 
																 AND products_width > 0
																 AND products_height > 0 
																 LIMIT 1;";
						$dimensions = $this->mDb->query($dimensions_query);
						if ($dimensions->RecordCount() > 0 && $dimensions->fields['products_ready_to_ship'] == 1) {
							for ($i = 1; $i <= $product['quantity']; $i++) {
								$packages[] = array('weight' => $product['weight'], 'length' => $dimensions->fields['products_length'], 'width' => $dimensions->fields['products_width'], 'height' => $dimensions->fields['products_height'], 'units' => strtoupper($dimensions->fields['products_dim_type']));
							}		
						} else {
							$pShipHash['shipping_weight_total'] += $product['weight'] * $product['quantity']; 
						}
					}
		return $ret;
	}
*/

	// shipping adjustment
	function free_shipping_items() {
		$this->calculate();

		return $this->free_shipping_item;
	}

	function free_shipping_prices() {
		$this->calculate();

		return $this->free_shipping_price;
	}

	function free_shipping_weight() {
		$this->calculate();

		return $this->free_shipping_weight;
	}

	public function getShippingDestination( $pCountryIso2 = '', $pPostalCode = '' ) {
		$ret = array();

		if( $pCountryIso2 && $pPostalCode ) {
			$ret = zen_get_countries( $pCountryIso2 );
		} else {
			$ret = $this->getDelivery();
		}

		return $ret;
	}

	public function getShippingOrigin() {
		global $gCommerceSystem; 

		$fulfillmentModules = CommerceSystem::scanModules( 'fulfillment' );

		$fulfillmentPriority = array();
		foreach( $fulfillmentModules as $fulfillmentKey => $fulfiller  ) {
			if( is_object( $fulfiller ) && $fulfiller->isEnabled() ) {
				if( $origin = $fulfiller->getFulfillment( $this ) ) {
					$fulfillmentPriority[$fulfillmentKey] = $origin;
				}
			}
			if( !empty( $fulfiller->mErrors ) ) {
				$feedback[$fulfillmentKey]['error'][] = $fulfiller->mErrors;
			}
		}

		if( empty( $fulfillmentPriority ) ) {
			$ret['countries_id'] = $storeCountryId = $gCommerceSystem->getConfig( 'SHIPPING_ORIGIN_COUNTRY', $gCommerceSystem->getConfig( 'STORE_COUNTRY' ) );

			if( $ret = zen_get_countries( $storeCountryId ) ) {
				$ret['postcode'] = $gCommerceSystem->getConfig( 'SHIPPING_ORIGIN_ZIP' );
			}
			$fulfillmentPriority[] = $ret;
		} 

		uasort( $fulfillmentPriority, 'commerce_order_sort_fulfillers' );
		return current( $fulfillmentPriority );
	}

	/**
	* Used for checkout tracking
	**/
	public function getTrackingHash() {
		$ret = array();
		foreach( array_keys( $this->contents ) as $productsKey ) {
			if( $prod = &$this->getProductObject( $this->contents[$productsKey]['products_id'] ) ) {
				$ret[] = $prod->getTrackingHash( $this->getProductHash( $productsKey ) );
			}
		}
		return $ret;
	}

	private function scanOtModules( $pRefresh = FALSE ) {
		if( empty( $this->mOtClasses ) || $pRefresh ) {
			global $gBitCustomer;

			if( defined( 'MODULE_ORDER_TOTAL_INSTALLED' ) && MODULE_ORDER_TOTAL_INSTALLED ) {
				$otActiveClasses = explode(';', str_replace( '.php', '', MODULE_ORDER_TOTAL_INSTALLED ) );
				foreach( $otActiveClasses as $otClass ) {
					if( !class_exists( $otClass ) ) {
						$langFile = zen_get_file_directory( BITCOMMERCE_PKG_PATH.DIR_WS_LANGUAGES . $gBitCustomer->getLanguage() . '/modules/order_total/', $otClass.'.php', 'false' );
						if( file_exists( $langFile ) ) {
							include_once( $langFile );
						}
						include_once( BITCOMMERCE_PKG_PATH.DIR_WS_MODULES.'order_total/'.$otClass.'.php' );
					}
					$this->mOtClasses[$otClass] = new $otClass( $this );
				}
			}
		}
	}

	function sortModules( &$pModules ) {
		usort($pModules, function($a, $b) {
				if ($a['sort_order'] == $b['sort_order']) {
					return 0;
				} else if ($a['sort_order'] > $b['sort_order']) {
					return 1;
				} else {
					return -1;
				}
			});
	}

	function otProcess() {
		$this->scanOtModules();
		$ret = array();

		foreach( $this->mOtClasses as $class=>&$otObject ) {
			$otObject->process();
			if( $otOutput = $otObject->getOutput() ) {
				$outHash = array( 'code' => $otObject->code, 'sort_order' => $otObject->getSortOrder() );
				foreach( array( 'title', 'text', 'value' ) as $key ) {
					if( isset( $otOutput[$key] ) && !is_null( $otOutput[$key] ) ) {
						$outHash[$key] = $otOutput[$key];
					}
				}

				$ret[] = $outHash;
			}
		}
		$this->sortModules( $ret );
		$this->mOtProcessModules = $ret;

		return $ret;
	}

	function otOutput() {
		$this->scanOtModules();
		$ret = array();
		foreach( $this->mOtClasses as $class=>&$otObject ) {
			if( $output = $otObject->getOutput() ) {
				array_push( $ret, $output );
			}
		}
		$this->sortModules( $ret );

		return $ret;
	}

	//
	// This function is called in checkout payment after display of payment methods. It actually calls
	// two credit class functions.
	//
	// use_credit_amount() is normally a checkbox used to decide whether the credit amount should be applied to reduce
	// the order total. Whether this is a Gift Voucher, or discount coupon or reward points etc.
	//
	// The second function called is credit_selection(). This in the credit classes already made is usually a redeem box.
	// for entering a Gift Voucher number. Note credit classes can decide whether this part is displayed depending on
	// E.g. a setting in the admin section.
	//
	function otCreditSelection() {
		$this->scanOtModules();
		$ret = '';
		foreach( $this->mOtClasses as $class=>&$otObject ) {
			$selection = $otObject->credit_selection();
			if (is_array($selection)) {
				$ret[] = $selection;
			}
		}
		return $ret;
	}


	// update_credit_account is called in checkout process on a per product basis. It's purpose
	// is to decide whether each product in the cart should add something to a credit account.
	// e.g. for the Gift Voucher it checks whether the product is a Gift voucher and then adds the amount
	// to the Gift Voucher account.
	// Another use would be to check if the product would give reward points and add these to the points/reward account.
	function otUpdateCreditAccount($i) {
		$this->scanOtModules();
		foreach( $this->mOtClasses as $class=>&$otObject ) {
			$otObject->update_credit_account($i);
		}
	}


	// This function is called in checkout confirmation.
	// It's main use is for credit classes that use the credit_selection() method. This is usually for
	// entering redeem codes(Gift Vouchers/Discount Coupons). This function is used to validate these codes.
	// If they are valid then the necessary actions are taken, if not valid we are returned to checkout payment
	// with an error
	function otCollectPosts( $pRequestParams ) {
		$this->scanOtModules();
		foreach( $this->mOtClasses as $class=>&$otObject ) {
			$post_var = 'c' . $otObject->code;
			if ( !empty( $pRequestParams[$post_var] ) ) {
				$_SESSION[$post_var] = $pRequestParams[$post_var];
			}
			$otObject->collect_posts( $pRequestParams );
		}
	}

	// this function is called in checkout process. it tests whether a decision was made at checkout payment to use
	// the credit amount be applied aginst the order. If so some action is taken. E.g. for a Gift voucher the account
	// is reduced the order total amount.
	function otApplyCredit() {
		$this->scanOtModules();
		foreach( $this->mOtClasses as $class=>&$otObject ) {
			$otObject->apply_credit();
		}
	}

	// Called in checkout process to clear session variables created by each credit class module.
	function otClearPosts() {
		$this->scanOtModules();
		foreach( $this->mOtClasses as $class=>&$otObject ) {
			$postVar = 'c' . $otObject->code;
			if( isset( $_SESSION[$postVar] ) ) {
				unset( $_SESSION[$post_var] );
			}
		}
	}

	function getModuleValue( $pClass, $pKey ) {
		$ret = '';
		for( $i = 0; $i < count( $this->totals ); $i++ ) {
			if( $this->totals[$i]['class'] == $pClass && !empty( $this->totals[$i][$pKey] ) ) {
				$ret = $this->totals[$i][$pKey];
			}
		}
		return $ret;
	}

	function getModuleTotal( $pClass ) {
		return $this->getModuleValue($pClass, 'orders_value');
	}

	// hasPaymentDue is called on checkout confirmation. It's function is to decide whether the
	// credits available are greater than the order total. If they are then a variable (credit_covers) is set to
	// true. This is used to bypass the payment method. In other words if the Gift Voucher is more than the order
	// total, we don't want to go to paypal etc.
	function hasPaymentDue() {
		return ($this->getPaymentDue() > 0);
	}

	function getSubtotal( $pToModule = 'ot_subtotal' ) {
		global $currencies;
		$this->scanOtModules();
		$round = $currencies->get_decimal_places( $this->getField( 'currency', DEFAULT_CURRENCY ) );
		if( $totalDue = $this->getField( 'total' ) ) {
			$totalDeductions = 0;
			foreach( $this->mOtClasses as $class=>&$otObject ) {
				if( $class == $pToModule ) {
					break;
				} elseif( $orderCredit = $otObject->getOrderDeduction( $this ) ) {
					$totalDeductions += $orderCredit;
					$totalDue -= $orderCredit;
				}
			}
		}
		return round( $totalDue, $round );
	}

	function getPaymentDue() {
		global $currencies;
		$this->scanOtModules();
		$round = $currencies->get_decimal_places( $this->getField( 'currency', DEFAULT_CURRENCY ) );
		if( $totalDue = $this->getField( 'total' ) ) {
			$totalDeductions = 0;
			foreach( $this->mOtClasses as $class=>&$otObject ) {
				if( $orderCredit = $otObject->getOrderDeduction( $this ) ) {
					$totalDeductions += $orderCredit;
					$totalDue -= $orderCredit;
				}
			}
		}
		return round( $totalDue, $round );
	}

}

function commerce_order_sort_fulfillers( $a, $b ) {
	    if ($a['priority'] == $b) {
        return 0;
    }
    return ($a < $b) ? 1 : -1;
}
