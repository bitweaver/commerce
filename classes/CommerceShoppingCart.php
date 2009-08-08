<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce											
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers								
// |																		
// | http://www.zen-cart.com/index.php										
// |																		
// | Portions Copyright (c) 2003 osCommerce									
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,			
// | that is bundled with this package in the file LICENSE, and is			
// | available through the world-wide-web at the following url:				
// | http://www.zen-cart.com/license/2_0.txt.								
// | If you did not receive a copy of the zen-cart license and are unable 
// | to obtain it through the world-wide-web, please send a note to			
// | license@zen-cart.com so we can mail you a copy immediately.			
// +----------------------------------------------------------------------+
// $Id: CommerceShoppingCart.php,v 1.1 2009/08/08 18:51:43 spiderr Exp $
//

require_once( BITCOMMERCE_PKG_PATH.'classes/CommerceOrderBase.php' );

class CommerceShoppingCart extends CommerceOrderBase {
	var $cartID, $content_type;

	function CommerceShoppingCart() {
		parent::CommerceOrderBase();
		$this->reset();
	}

	function restore_contents() {
		global $gBitDb, $gBitUser;

		if( !$gBitUser->isRegistered() ) {
			return false;
		}

		// insert current cart contents in database
		if (is_array($this->contents)) {
			reset($this->contents);
			while (list($products_id, ) = each($this->contents)) {
//					$products_id = urldecode($products_id);
				$qty = $this->contents[$products_id]['quantity'];
				$product_query = "select `products_id`
								from " . TABLE_CUSTOMERS_BASKET . "
								where `customers_id` = '" . (int)$_SESSION['customer_id'] . "' and `products_id` = '" . zen_db_input($products_id) . "'";

				$product = $gBitDb->Execute($product_query);

				if ($product->RecordCount()<=0) {
					$sql = "insert into " . TABLE_CUSTOMERS_BASKET . "
									(`customers_id`, `products_id`, `customers_basket_quantity`, `customers_basket_date_added`)
									values ('" . (int)$_SESSION['customer_id'] . "', '" . zen_db_input($products_id) . "', '" .
															 $qty . "', '" . date('Ymd') . "')";

					$gBitDb->Execute($sql);

					if (isset($this->contents[$products_id]['attributes'])) {
						reset($this->contents[$products_id]['attributes']);
						while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
							//clr 031714 udate query to include attribute value. This is needed for text attributes.
							$attr_value = $this->contents[$products_id]['attributes_values'][$option];
							$products_options_sort_order= zen_get_attributes_options_sort_order(zen_get_prid($products_id), $option, $value);
							if ($attr_value) {
								$attr_value = zen_db_input($attr_value);
							}
							$sql = "insert into " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . "
																	(`customers_id`, `products_id`, `products_options_id`,
																	 `products_options_value_id`, `products_options_value_text`, `products_options_sort_order`)
																	 values ('" . (int)$_SESSION['customer_id'] . "', '" . zen_db_input($products_id) . "', '" .
																	 $option . "', '" . $value . "', '" . $attr_value . "', '" . $products_options_sort_order . "')";
							$gBitDb->Execute($sql);
						}
					}
				} else {
					$sql = "UPDATE " . TABLE_CUSTOMERS_BASKET . "
									SET `customers_basket_quantity` = ?
									WHERE `customers_id` = ? AND `products_id` = ?";

					$gBitDb->query( $sql, array( $qty, (int)$_SESSION['customer_id'],	zen_db_input($products_id) ) );

				}
			}
		}

// reset per-session cart contents, but not the database contents
		$this->reset(false);

		$products_query = "select `products_id`, `customers_basket_quantity`
											 from " . TABLE_CUSTOMERS_BASKET . "
											 where `customers_id` = '" . (int)$_SESSION['customer_id'] . "'";
		$products = $gBitDb->Execute($products_query);

		while (!$products->EOF) {
			$this->contents[$products->fields['products_id']] = array('quantity' => $products->fields['customers_basket_quantity']);
// attributes
// set contents in sort order

			//CLR 020606 update query to pull attribute value_text. This is needed for text attributes.
//				$attributes_query = zen_db_query("select products_options_id, products_options_value_id, products_options_value_text from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where `customers_id` = '" . (int)$customer_id . "' and `products_id` = '" . zen_db_input($products['products_id']) . "'");

			$order_by = ' order by `products_options_sort_order`';

			$attributes = $gBitDb->Execute("select `products_options_id`, `products_options_value_id`, `products_options_value_text`
													 from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . "
													 where `customers_id` = '" . (int)$_SESSION['customer_id'] . "'
													 and `products_id` = '" . zen_db_input($products->fields['products_id']) . "' " . $order_by);

			while (!$attributes->EOF) {
				$this->contents[$products->fields['products_id']]['attributes'][$attributes->fields['products_options_id']] = $attributes->fields['products_options_value_id'];
				//CLR 020606 if text attribute, then set additional information
				if ($attributes->fields['products_options_value_id'] == PRODUCTS_OPTIONS_VALUES_TEXT_ID) {
					$this->contents[$products->fields['products_id']]['attributes_values'][$attributes->fields['products_options_id']] = $attributes->fields['products_options_value_text'];
				}
				$attributes->MoveNext();
			}
			$products->MoveNext();
		}

		$this->cleanup();
	}

	function reset($reset_database = false) {
		global $gBitDb, $gBitUser;

		$this->contents = array();
		$this->total = 0;
		$this->weight = 0;
		$this->content_type = false;

		// shipping adjustment
		$this->free_shipping_item = 0;
		$this->free_shipping_price = 0;
		$this->free_shipping_weight = 0;

		if( $gBitUser->isRegistered() && ($reset_database == true)) {
			$sql = "delete from " . TABLE_CUSTOMERS_BASKET . " where `customers_id` = ?";
			$gBitDb->query($sql, array( $gBitUser->mUserId ) );

			$sql = "delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where `customers_id` = ?";
			$gBitDb->query($sql, array( $gBitUser->mUserId ) );
		}

		unset($this->cartID);
	}

	function add_cart($products_id, $qty = '1', $attributes = '', $notify = true) {
		global $gBitDb, $gBitUser;
		$products_id = zen_get_uprid($products_id, $attributes);
		if ($notify == true) {
			$_SESSION['new_products_id_in_cart'] = $products_id;
		}

		if ($this->in_cart($products_id)) {
			$this->update_quantity($products_id, $qty, $attributes);
		} else {
			$this->contents[] = array($products_id);
			$this->contents[$products_id] = array('quantity' => $qty);
			if( $gBitUser->isRegistered() ) {
				// insert into database
				$sql = "insert into " . TABLE_CUSTOMERS_BASKET . " (`customers_id`, `products_id`, `customers_basket_quantity`, `customers_basket_date_added`) values ( ?, ?, ?, ? )";
				$gBitDb->query( $sql, array( $gBitUser->mUserId, $products_id, $qty, date('Ymd') ) );
			}

			if (is_array($attributes)) {
				reset($attributes);
				while (list($option, $value) = each($attributes)) {
					//CLR 020606 check if input was from text box.	If so, store additional attribute information
					//CLR 020708 check if text input is blank, if so do not add to attribute lists
					//CLR 030228 add htmlspecialchars processing.	This handles quotes and other special chars in the user input.
					$attr_value = NULL;
					$blank_value = FALSE;
					if (strstr($option, TEXT_PREFIX)) {
						if (trim($value) == NULL) {
							$blank_value = TRUE;
						} else {
							$option = substr($option, strlen(TEXT_PREFIX));
							$attr_value = stripslashes($value);
							$value = PRODUCTS_OPTIONS_VALUES_TEXT_ID;
							$this->contents[$products_id]['attributes_values'][$option] = $attr_value;
						}
					}

					if (!$blank_value) {
						if (is_array($value) ) {
							reset($value);
							while (list($opt, $val) = each($value)) {
								$this->contents[$products_id]['attributes'][$option.'_chk'.$val] = $val;
							}
						} else {
							$this->contents[$products_id]['attributes'][$option] = $value;
						}
// insert into database
					//CLR 020606 update db insert to include attribute value_text. This is needed for text attributes.
						if( $gBitUser->isRegistered() ) {
							if (is_array($value) ) {
								reset($value);
								while (list($opt, $val) = each($value)) {
									$products_options_sort_order= zen_get_attributes_options_sort_order(zen_get_prid($products_id), $option, $opt);
									$sql = "insert into " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . "
											(`customers_id`, `products_id`, `products_options_id`, `products_options_value_id`, `products_options_sort_order`)
											values ( ?, ?, ?, ?, ? )";
									$this->mDb->query($sql, array( $gBitUser->mUserId, zen_db_input($products_id), (int)$option.'_chk'.$val, $val, $products_options_sort_order ) );
								}
							} else {
								if ($attr_value) {
									$attr_value = zen_db_input($attr_value);
								}
								$products_options_sort_order= zen_get_attributes_options_sort_order(zen_get_prid($products_id), $option, $value);
								$sql = "insert into " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . "
																		(`customers_id`, `products_id`, `products_options_id`, `products_options_value_id`, `products_options_value_text`, `products_options_sort_order`)
																		values ('" . (int)$_SESSION['customer_id'] . "', '" . zen_db_input($products_id) . "', '" .
																		(int)$option . "', '" . $value . "', '" . $attr_value . "', '" . $products_options_sort_order . "')";

								$gBitDb->Execute($sql);
							}
						}
					}
				}
			}
		}
		$this->cleanup();

// assign a temporary unique ID to the order contents to prevent hack attempts during the checkout procedure
		$this->cartID = $this->generate_cart_id();
	}

	function update_quantity($products_id, $quantity = '', $attributes = '') {
		global $gBitDb;

		if (empty($quantity)) return true; // nothing needs to be updated if theres no quantity, so we return true..

		$this->contents[$products_id] = array('quantity' => $quantity);
// update database
		if ($_SESSION['customer_id']) {
			$sql = "UPDATE " . TABLE_CUSTOMERS_BASKET . "
							SET `customers_basket_quantity` = ?
							WHERE `customers_id` = ? AND `products_id` = ?";

			$gBitDb->query($sql, array( $quantity, (int)$_SESSION['customer_id'], zen_db_input($products_id) ) );

		}

		if (is_array($attributes)) {
			reset($attributes);
			while (list($option, $value) = each($attributes)) {
				//CLR 020606 check if input was from text box.	If so, store additional attribute information
				//CLR 030108 check if text input is blank, if so do not update attribute lists
				//CLR 030228 add htmlspecialchars processing.	This handles quotes and other special chars in the user input.
				$attr_value = NULL;
				$blank_value = FALSE;
				if (strstr($option, TEXT_PREFIX)) {
					if (trim($value) == NULL) {
						$blank_value = TRUE;
					} else {
						$option = substr($option, strlen(TEXT_PREFIX));
						$attr_value = stripslashes($value);
						$value = PRODUCTS_OPTIONS_VALUES_TEXT_ID;
						$this->contents[$products_id]['attributes_values'][$option] = $attr_value;
					}
				}

				if (!$blank_value) {
					if (is_array($value) ) {
						reset($value);
						while (list($opt, $val) = each($value)) {
							$this->contents[$products_id]['attributes'][$option.'_chk'.$val] = $val;
						}
					} else {
						$this->contents[$products_id]['attributes'][$option] = $value;
					}
// update database
					//CLR 020606 update db insert to include attribute value_text. This is needed for text attributes.
					//CLR 030228 add zen_db_input() processing
//					if (zen_session_is_registered('customer_id')) zen_db_query("update " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " set products_options_value_id = '" . (int)$value . "', products_options_value_text = '" . zen_db_input($attr_value) . "' where `customers_id` = '" . (int)$customer_id . "' and `products_id` = '" . zen_db_input($products_id) . "' and products_options_id = '" . (int)$option . "'");

					if ($attr_value) {
						$attr_value = zen_db_input($attr_value);
					}
					if (is_array($value) ) {
						reset($value);
						while (list($opt, $val) = each($value)) {
							$products_options_sort_order= zen_get_attributes_options_sort_order(zen_get_prid($products_id), $option, $opt);
							$sql = "UPDATE " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . "
											SET `products_options_value_id` = ?
											WHERE `customers_id` =? AND `products_id` =? AND `products_options_id` =?";

							$gBitDb->query( $sql, array( $val, $_SESSION['customer_id'], $products_id, (int)$option.'_chk'.$val ) );
						}
					} elseif( is_int( $value ) ) {
						if ($_SESSION['customer_id']) {
							$sql = "UPDATE " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . "
											SET `products_options_value_id`=?, `products_options_value_text`=?
											WHERE `customers_id` = ? AND `products_id` = ? AND `products_options_id` = ?";

							$gBitDb->query( $sql, array( $value, $attr_value, $_SESSION['customer_id'], $products_id, $option ) );
						}
					}
				}
			}
		}
	}

	function cleanup() {
		global $gBitDb;

		reset($this->contents);
		while (list($key,) = each($this->contents)) {
			if (empty( $this->contents[$key]['quantity'] ) || $this->contents[$key]['quantity'] <= 0) {
				unset($this->contents[$key]);
// remove from database
				if ($_SESSION['customer_id']) {
					$sql = "delete from " . TABLE_CUSTOMERS_BASKET . "
									where `customers_id` = '" . (int)$_SESSION['customer_id'] . "'
									and `products_id` = '" . $key . "'";

					$gBitDb->Execute($sql);

					$sql = "delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . "
									where `customers_id` = '" . (int)$_SESSION['customer_id'] . "'
									and `products_id` = '" . $key . "'";

					$gBitDb->Execute($sql);
				}
			}
		}
	}

	function count_contents() {	// get total number of items in cart
		$total_items = 0;
		if (is_array($this->contents)) {
			reset($this->contents);
			while (list($products_id, ) = each($this->contents)) {
				$total_items += $this->get_quantity($products_id);
			}
		}

		return $total_items;
	}

	function get_quantity($pProductsId) {
		$ret = 0;
		$keys = array_keys( $this->contents );
		foreach( $keys AS $k ) {
			if( !strpos( $pProductsId, ':' ) ) {
				$productId = (strpos( $k, ':' ) ? substr( $k, 0, strpos( $k, ':' ) ) : $k);
			} else {
				$productId = $k;
			}
			if( $productId == $pProductsId ) {
				$ret += $this->contents[$k]['quantity'];
			}
		}
		return( $ret );
	}

	function in_cart( $pProductsId ) {
		return( $this->get_quantity( $pProductsId ) > 0 );
	}

	function remove($products_id) {
		global $gBitDb;
		//CLR 030228 add call zen_get_uprid to correctly format product ids containing quotes
//			$products_id = zen_get_uprid($products_id, $attributes);
		unset($this->contents[$products_id]);

		if ($_SESSION['customer_id']) {
			// remove from database
			$sql = "delete from " . TABLE_CUSTOMERS_BASKET . " where `customers_id` = '" . (int)$_SESSION['customer_id'] . "' and `products_id` = '" . zen_db_input($products_id) . "'";
			$gBitDb->Execute($sql);

			$sql = "delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where `customers_id` = '" . (int)$_SESSION['customer_id'] . "' and `products_id` = '" . zen_db_input($products_id) . "'";
			$gBitDb->Execute($sql);

		}

		// assign a temporary unique ID to the order contents to prevent hack attempts during the checkout procedure
		$this->cartID = $this->generate_cart_id();
	}

	function remove_all() {
		$this->reset();
	}

	function get_product_id_list() {
		$product_id_list = '';
		if (is_array($this->contents)) {
			reset($this->contents);
			while (list($products_id, ) = each($this->contents)) {
				$product_id_list .= ', ' . zen_db_input($products_id);
			}
		}

		return substr($product_id_list, 2);
	}

	// calculates totals
	function calculate( $pForceRecalculate=FALSE ) {
		global $gBitDb;
		if( is_null( $this->total ) || $pForceRecalculate ) {
			$this->total = 0;
			$this->weight = 0;

			// shipping adjustment
			$this->free_shipping_item = 0;
			$this->free_shipping_price = 0;
			$this->free_shipping_weight = 0;

			if (!is_array($this->contents)) return 0;

			reset($this->contents);
			foreach( array_keys( $this->contents ) as $productsCartKey ) {
				$qty = $this->contents[$productsCartKey]['quantity'];
				$prid = zen_get_prid( $productsCartKey );

				// products price
				$product = $this->getProductObject( $prid );
				// sometimes 0 hash things can get stuck in cart.
				if( $product && $product->isValid() ) {
					$products_tax = zen_get_tax_rate($product->getField('products_tax_class_id'));
					$products_price = $product->getPurchasePrice( $qty );

					// shipping adjustments
					if (($product->getField('product_is_always_free_ship') == 1) or ($product->getField('products_virtual') == 1) or (ereg('^GIFT', addslashes($product->getField('products_model'))))) {
						$this->free_shipping_item += $qty;
						$this->free_shipping_price += zen_add_tax($products_price, $products_tax) * $qty;
						$this->free_shipping_weight += ($qty * $product->getField('products_weight') );
					}

					$this->total += zen_add_tax($products_price, $products_tax) * $qty;
					$this->weight += ($qty * $product->getField('products_weight') );
				}

				// attributes price
				if (isset($this->contents[$productsCartKey]['attributes'])) {
					reset($this->contents[$productsCartKey]['attributes']);
					while (list($option, $value) = each($this->contents[$productsCartKey]['attributes'])) {
						$added_charge = $product->getAttributesPriceFinal( (int)$value, $qty );
						$this->total += zen_add_tax($added_charge, $products_tax);
					}
				} // attributes price

				// attributes weight
				if (isset($this->contents[$productsCartKey]['attributes'])) {
					reset($this->contents[$productsCartKey]['attributes']);
					while (list($option, $value) = each($this->contents[$productsCartKey]['attributes'])) {
						$attribute_weight_query = "SELECT `products_attributes_wt`, `products_attributes_wt_pfix`
												FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
												WHERE pa.`products_options_id` = ? AND pa.`products_options_values_id` = ?";
						$attribute_weight = $gBitDb->query( $attribute_weight_query, array( (int)$option , (int)$value ) );
						
						// adjusted count for free shipping
						if ($product->getField('product_is_always_free_ship') != 1) {
						$new_attributes_weight = $attribute_weight->fields['products_attributes_wt'];
						} else {
						$new_attributes_weight = 0;
						}

						// + or blank adds
						if ($attribute_weight->fields['products_attributes_wt_pfix'] == '-') {
							$this->weight -= $qty * $new_attributes_weight;
						} else {
							$this->weight += $qty * $new_attributes_weight;
						}
					}
				} // attributes weight

			}
		}
	}

	function attributes_price( $products_id, $pTotalPrice=TRUE ) {
		global $gBitDb;

		$attributes_price = 0;
		// check for attributes qty pricing (if pricing is negative, this can really screw pricing)
		$qty = $this->in_cart_mixed_discount_quantity( $products_id );

		if (isset($this->contents[$products_id]['attributes'])) {
			reset($this->contents[$products_id]['attributes']);
			while (list($option, $value) = each($this->contents[$products_id]['attributes'])) {
				$prid = zen_get_prid( $products_id );
				$product = $this->getProductObject( $prid );
				$attributes_price += $product->getAttributesPriceFinal( (int)$value, $qty, $pTotalPrice );
			}
		}

		return $attributes_price;
	}


	// one time attribute prices
	// add to tpl_shopping_cart/orders
	function attributes_price_onetime_charges( $pProductsId, $pQty=1 ) {
		global $gBitDb;

		$attributes_price_onetime = 0;

		if (isset($this->contents[$pProductsId]['attributes'])) {
			$product = $this->getProductObject( $pProductsId );

			reset($this->contents[$pProductsId]['attributes']);
			while (list($option, $value) = each($this->contents[$pProductsId]['attributes'])) {
				if( $option = $product->getOptionValue( $option, $value ) ) {
					if( $option['product_attribute_is_free'] != '1' && !$product->getField( 'product_is_free' ) ) {
						// calculate additional one time charges
						if( !empty( $option['attributes_price_onetime'] ) ) {
							$attributes_price_onetime += $option['attributes_price_onetime'];
						}
						if( !empty( $option['attributes_pf_onetime'] ) ) {
							$attributes_price_onetime = zen_get_attributes_price_factor( $product->mInfo['normal_price'], $product->mInfo['sale_price'], $option['attributes_pf_onetime'], $option['attributes_pf_onetime_offset']);
						}
						if( !empty( $option['attributes_qty_prices_onetime'] ) ) {
							$attributes_price_onetime = zen_get_attributes_qty_prices_onetime($option['attributes_qty_prices_onetime'], $pQty);
						}
					}
				}
			}
		}

		return $attributes_price_onetime;
	}


	function attributes_weight( $pCartProductsHash ) {
		global $gBitDb;

		$prid = zen_get_prid( $pCartProductsHash );
		$attribute_weight = 0;

		if (isset($this->contents[$pCartProductsHash]['attributes'])) {
			reset($this->contents[$pCartProductsHash]['attributes']);
			while (list($option, $value) = each($this->contents[$pCartProductsHash]['attributes'])) {
				$sql = "SELECT `products_attributes_wt`, `products_attributes_wt_pfix`
						FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
						WHERE pa.`products_options_id` = ? AND pa.`products_options_values_id` = ?";
				$attribute_weight_info = $gBitDb->query( $sql, array( (int)$option, (int)$value ) );
				// adjusted count for free shipping
				$freeShip = $gBitDb->getOne("select `product_is_always_free_ship`
												from " . TABLE_PRODUCTS . "
												where `products_id` = ?", array( $prid ) );

				if ( $freeShip != 1 ) {
					$new_attributes_weight = $attribute_weight_info->fields['products_attributes_wt'];
				} else {
					$new_attributes_weight = 0;
				}

				// + or blank adds
				if ($attribute_weight_info->fields['products_attributes_wt_pfix'] == '-') {
					$attribute_weight -= $new_attributes_weight;
				} else {
					$attribute_weight += $attribute_weight_info->fields['products_attributes_wt'];
				}
			}
		}

		return $attribute_weight;
	}


	function getProductObject( $pProductsId ) {
		if( BitBase::verifyId( $pProductsId ) ) {
			if( !isset( $this->mProductObjects[$pProductsId] ) ) {
				$this->mProductObjects[$pProductsId] = bc_get_commerce_product( zen_get_prid( $pProductsId ) );
				if( $this->mProductObjects[$pProductsId]->load() ) {
					$ret = &$this->mProductObjects[$pProductsId];
				}
			}
		}
		return $this->mProductObjects[$pProductsId];
	}

	function get_products($check_for_valid_cart = false) {
		global $gBitDb, $gBitProduct;

		if (!is_array($this->contents)) return false;

		$products_array = array();
		reset($this->contents);
		while( list( $products_id, $productsHash ) = each( $this->contents ) ) {
			$product = $this->getProductObject( zen_get_prid( $products_id ) );
			if( $product && $product->isValid() ) {
				$prid = $product->mProductsId;
				$qty = $productsHash['quantity'];
				$products_price = $product->getPurchasePrice( $qty );
				if ($check_for_valid_cart == true) {
					$check_quantity = $productsHash['quantity'];
					$check_quantity_min = $product->getField( 'products_quantity_order_min' );
					// Check quantity min
					if ($new_check_quantity = $this->in_cart_mixed($prid) ) {
						$check_quantity = $new_check_quantity;
					}

					$fix_once = 0;
					if ($check_quantity < $check_quantity_min) {
						$fix_once ++;
						$_SESSION['valid_to_checkout'] = false;
						$_SESSION['cart_errors'] .= ERROR_PRODUCT . $product->getTitle() . ERROR_PRODUCT_QUANTITY_MIN_SHOPPING_CART . ERROR_PRODUCT_QUANTITY_ORDERED . $check_quantity	. ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display((int)$prid, false, true) . '</span> ' . '<br />';
					}

					// Check Quantity Units if not already an error on Quantity Minimum
					if ($fix_once == 0) {
						$check_units = $product->getField( 'products_quantity_order_units' );
						if ( fmod($check_quantity,$check_units) != 0 ) {
							$_SESSION['valid_to_checkout'] = false;
							$_SESSION['cart_errors'] .= ERROR_PRODUCT . $product->getTitle() . ERROR_PRODUCT_QUANTITY_UNITS_SHOPPING_CART . ERROR_PRODUCT_QUANTITY_ORDERED . $check_quantity	. ' <span class="alertBlack">' . zen_get_products_quantity_min_units_display((int)$prid, false, true) . '</span> ' . '<br />';
						}
					}

				// Verify Valid Attributes
				}

				//clr 030714 update $products_array to include attribute value_text. This is needed for text attributes.

		// convert quantity to proper decimals
				if (QUANTITY_DECIMALS != 0) {
		//					$new_qty = round($new_qty, QUANTITY_DECIMALS);

					$fix_qty = $productsHash['quantity'];
					switch (true) {
						case (!strstr($fix_qty, '.')):
							$new_qty = $fix_qty;
							break;
						default:
							$new_qty = preg_replace('/[0]+$/','',$productsHash['quantity']);
							break;
					}
				} else {
					$new_qty = $productsHash['quantity'];
				}

				$new_qty = round($new_qty, QUANTITY_DECIMALS);

				if ($new_qty == (int)$new_qty) {
					$new_qty = (int)$new_qty;
				}

				$productHash =$product->mInfo;
				$productHash['id'] = $products_id;
				$productHash['name'] = $product->getField('products_name');
				$productHash['purchase_group_id'] = $product->getField('purchase_group_id');
				$productHash['model'] = $product->getField('products_model');
				$productHash['image'] = $product->getField('products_image');
				$productHash['image_url'] = $product->getField('products_image_url');
				$productHash['price'] = ($product->getField('product_is_free') =='1' ? 0 : $products_price);
				$productHash['quantity'] = $new_qty;
				if( $product->getField( 'products_commission' ) && !$product->getCommissionDiscount() ) {
					$productHash['commission'] = ($products_price / $product->getField('actual_price')) * ($product->getField('products_commission') - $product->getCommissionDiscount());
				} else {
					$productHash['commission'] = 0;
				}
				$productHash['weight'] = $product->getField('products_weight') + $this->attributes_weight($products_id);
		// fix here
				$productHash['final_price'] = $products_price + $this->attributes_price($products_id, FALSE);
				$productHash['onetime_charges'] = $this->attributes_price_onetime_charges($products_id, $new_qty);
				$productHash['tax_class_id'] = $product->getField('products_tax_class_id');
				$productHash['tax'] = $product->getField('tax_rate');
				$productHash['tax_description'] = $product->getField('tax_description');
				$productHash['attributes'] = (isset( $productsHash['attributes'] ) ? $productsHash['attributes'] : '');
				$productHash['attributes_values'] = (isset( $productsHash['attributes_values'] ) ? $productsHash['attributes_values'] : '');
				$products_array[] = $productHash;
			}
		}
		return $products_array;
	}

	function show_total() {
		$this->calculate();

		return $this->total;
	}

	function show_weight( $pUnit=NULL ) {
		$this->calculate();
		$ret = $this->weight;
		if( strtolower( $pUnit ) == 'kg' ) {
			$ret *= .45359;
		}

		return $ret;
	}

	function generate_cart_id($length = 5) {
		return zen_create_random_value($length, 'digits');
	}

	function get_content_type($gv_only = 'false') {
		global $gBitDb;

		$this->content_type = false;
		$gift_voucher = 0;

//			if ( (DOWNLOAD_ENABLED == 'true') && ($this->count_contents() > 0) ) {
		if ( $this->count_contents() > 0 ) {
			reset($this->contents);
			while (list($products_id, ) = each($this->contents)) {
				$free_ship_check = $gBitDb->query( "select `products_virtual`, `products_model`, `products_price` from " . TABLE_PRODUCTS . " where `products_id` = ?", array( zen_get_prid($products_id) ) );
				if( $free_ship_check && ereg( '^GIFT', addslashes($free_ship_check->fields['products_model'] ) ) ) {
					$gift_voucher += ($free_ship_check->fields['products_price'] + $this->attributes_price($products_id)) * $this->contents[$products_id]['quantity'];
				}
				if (isset($this->contents[$products_id]['attributes'])) {
					reset($this->contents[$products_id]['attributes']);
					while (list(, $value) = each($this->contents[$products_id]['attributes'])) {
						$virtual_check_query = "SELECT COUNT(*) as `total`
												FROM " . TABLE_PRODUCTS_ATTRIBUTES . " pa
													INNER JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad ON(pa.`products_attributes_id` = pad.`products_attributes_id`)
												WHERE pa.`products_options_values_id` = ?";

						$virtualCount = $gBitDb->getOne( $virtual_check_query, array( (int)$value ) );

						if ($virtualCount > 0) {
							switch ($this->content_type) {
								case 'physical':
									$this->content_type = 'mixed';
										if ($gv_only == 'true') {
											return $gift_voucher;
										} else {
											return $this->content_type;
										}
									break;
								default:
									$this->content_type = 'virtual';
									break;
							}
						} else {
							switch ($this->content_type) {
								case 'virtual':
									if ($free_ship_check->fields['products_virtual'] == '1') {
										$this->content_type = 'virtual';
									} else {
										$this->content_type = 'mixed';
										if ($gv_only == 'true') {
											return $gift_voucher;
										} else {
											return $this->content_type;
										}
									}
									break;
								case 'physical':
									if ($free_ship_check->fields['products_virtual'] == '1') {
										$this->content_type = 'mixed';
										if ($gv_only == 'true') {
											return $gift_voucher;
										} else {
											return $this->content_type;
										}
									} else {
										$this->content_type = 'physical';
									}
									break;
								default:
									if ($free_ship_check->fields['products_virtual'] == '1') {
										$this->content_type = 'virtual';
									} else {
										$this->content_type = 'physical';
									}
							}
						}
					}
				} else {
					switch ($this->content_type) {
						case 'virtual':
							if ($free_ship_check->fields['products_virtual'] == '1') {
								$this->content_type = 'virtual';
							} else {
								$this->content_type = 'mixed';
								if ($gv_only == 'true') {
									return $gift_voucher;
								} else {
									return $this->content_type;
								}
							}
							break;
						case 'physical':
							if ($free_ship_check->fields['products_virtual'] == '1') {
								$this->content_type = 'mixed';
								if ($gv_only == 'true') {
									return $gift_voucher;
								} else {
									return $this->content_type;
								}
							 } else {
								$this->content_type = 'physical';
							 }
							break;
						default:
							if( $free_ship_check && $free_ship_check->fields['products_virtual'] == '1') {
								$this->content_type = 'virtual';
							 } else {
								$this->content_type = 'physical';
							 }
					}
				}
			}
		} else {
			$this->content_type = 'physical';
		}

		if ($gv_only == 'true') {
			return $gift_voucher;
		} else {
			return $this->content_type;
		}
	}

	function __sleep() {
		unset( $this->mProductObjects );
		return array( 'contents', 'total', 'weight', 'content_type', 'free_shipping_item', 'free_shipping_weight', 'free_shipping_price' );
	}

	function unserialize($broken) {
		for(reset($broken);$kv=each($broken);) {
			$key=$kv['key'];
			if (gettype($this->$key)!="user function")
			$this->$key=$kv['value'];
		}
	}

	// check mixed min/units
	function in_cart_mixed($products_id) {
		global $gBitDb;
		// if nothing is in cart return 0
		if (!is_array($this->contents)) return 0;

		if( is_array( $products_id ) ) {
			$products_id = current( $products_id );
		}
		// check if mixed is on
		$productQtyMixed = $gBitDb->GetOne("select `products_quantity_mixed` from " . TABLE_PRODUCTS .
		" where `products_id` ='" .	zen_get_prid( $products_id ) . "'");

		// if mixed attributes is off return qty for current attribute selection
		if( $productQtyMixed == '0' ) {
			return $this->get_quantity($products_id);
		}

		// compute total quantity regardless of attributes
		$in_cart_mixed_qty = 0;
		$chk_products_id= zen_get_prid($products_id);

		// reset($this->contents); // breaks cart
		$check_contents = $this->contents;
		while (list($products_id, ) = each($check_contents)) {
			$test_id = zen_get_prid($products_id);
			if ($test_id == $chk_products_id) {
				$in_cart_mixed_qty += $check_contents[$products_id]['quantity'];
			}
		}
		return $in_cart_mixed_qty;
	}

	// check mixed discount_quantity
	function in_cart_mixed_discount_quantity( $pProductsId ) {
		global $gBitDb;
		// if nothing is in cart return 0
		$ret = 0;

		if( is_array( $this->contents ) ) {
			// check if mixed is on
			$chk_products_id= zen_get_prid( $pProductsId );
			if( $hasMixedQuantity = $gBitDb->getOne("select `products_mixed_discount_qty` from " . TABLE_PRODUCTS . " where `products_id` =?", array( zen_get_prid( $chk_products_id ) ) ) ) {
				// compute total quantity regardless of attributes
				// reset($this->contents); // breaks cart
				$check_contents = $this->contents;
				foreach( array_keys( $check_contents ) as $products_key ) {
					$test_id = zen_get_prid($products_key);
					if ($test_id == $chk_products_id) {
						$ret += $check_contents[$products_key]['quantity'];
					}
				}
			} else {
				$ret = $this->get_quantity( $pProductsId );
			}
		}
				
		return $ret;
	}

	// $check_what is the fieldname example: 'products_is_free'
	// $check_value is the value being tested for - default is 1
	// Syntax: $gBitCustomer->mCart->in_cart_check('product_is_free','1');
	function in_cart_check($check_what, $check_value='1') {
		global $gBitDb;
		// if nothing is in cart return 0
		if (!is_array($this->contents)) return 0;

		// compute total quantity for field
		$in_cart_check_qty=0;

		reset($this->contents);
		while (list($products_id, ) = each($this->contents)) {
			$testing_id = zen_get_prid($products_id);
			// check if field it true
			$product_check = $gBitDb->getOne("select " . $check_what . " as `check_it` from " . TABLE_PRODUCTS .
		" where `products_id` ='" . $testing_id . "'");
			if( $product_check == $check_value ) {
				$in_cart_check_qty += $this->contents[$products_id]['quantity'];
			}
		}
		return $in_cart_check_qty;
	}

	// gift voucher only
	function gv_only() {
		$gift_voucher = $this->get_content_type(true);
		return $gift_voucher;
	}

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

}
?>