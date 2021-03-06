<?php
global $gBitSystem;

$registerHash = array(
	'package_name' => 'bitcommerce',
	'package_path' => dirname( dirname( __FILE__ ) ).'/',
	'service' => LIBERTY_SERVICE_COMMERCE,
	'homeable' => TRUE,
);
$gBitSystem->registerPackage( $registerHash );

if( $gBitSystem->isPackageActive( 'bitcommerce' ) ) {
	if( !defined( 'BITCOMMERCE_PKG_ADMIN_URI' ) ) {
		define( 'BITCOMMERCE_PKG_ADMIN_URI', str_replace( 'http:', 'https:', BITCOMMERCE_PKG_URI.'admin/' ) );
	}

	$menuHash = array(
		'package_name'  => BITCOMMERCE_PKG_NAME,
		'index_url'     => BITCOMMERCE_PKG_URL.'index.php',
		'menu_template' => 'bitpackage:bitcommerce/menu_bitcommerce.tpl',
	);
	$gBitSystem->registerAppMenu( $menuHash );
}

if( !defined( 'BITCOMMERCE_DB_PREFIX' ) ) {
	define( 'BITCOMMERCE_DB_PREFIX', BIT_DB_PREFIX );
}

if( defined( 'FLAT_STORAGE_NAME' ) ) {
	define( 'BITCOMMERCE_STORAGE_NAME', FLAT_STORAGE_NAME );
} else {
	define( 'BITCOMMERCE_STORAGE_NAME', BITCOMMERCE_PKG_NAME );
}

// include shopping cart class
// 	require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'classes/shopping_cart.php' );
if( $gBitSystem->isPackageActive( 'bitcommerce' ) ) {
	define( 'BITPRODUCT_CONTENT_TYPE_GUID', 'bitproduct' );
	$gLibertySystem->registerService( LIBERTY_SERVICE_COMMERCE, BITCOMMERCE_PKG_NAME, array(
		'content_expunge_function' => 'bitcommerce_content_expunge',
		'users_expunge_check_function' => 'bitcommerce_user_expunge_check',
		'users_expunge_function'	=> 'bitcommerce_user_expunge',
		'users_register_function'   => 'bitcommerce_user_register',
	) );

	function bitcommerce_content_expunge ( &$pObject ) {
		require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'bitcommerce_start_inc.php' );
		if( $relProduct = bc_get_commerce_product( array( 'related_content_id' => $pObject->mContentId ) ) ) {
			// do not delete products if related content is getting deleted, but product has been purchased
			if( $relProduct->isPurchased() ) {
				$relProduct->update( array( 'related_content_id' => NULL ) );
			} else {
				$relProduct->expunge();
			}
		}

	}

	function bitcommerce_user_expunge_check( &$pObject ) {
		if( is_a( $pObject, 'BitUser' ) && !empty( $pObject->mUserId ) ) {
			require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'bitcommerce_start_inc.php' );
			$exCustomer = new CommerceCustomer( $pObject->mUserId );
			if( $exCustomer->load() ) {
				if( $orderHistory = $exCustomer->getOrdersHistory() ) {
					$pObject->mErrors['expunge_check'][] = tra( 'Customer has completed orders: ' ).'#'.implode( ', #', array_keys( $orderHistory ) );
				}
			}
		}
	}

	// make sure all mail_queue messages from a deleted user are nuked
	function bitcommerce_user_expunge( &$pObject ) {
		if( is_a( $pObject, 'BitUser' ) && !empty( $pObject->mUserId ) ) {
			require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'bitcommerce_start_inc.php' );
			$pObject->StartTrans();
			$exCustomer = new CommerceCustomer( $pObject->mUserId );
			if( $exCustomer->load() ) {
				$exCustomer->expunge();
			}
			$pObject->CompleteTrans();
		}
	}
	
	function bitcommerce_user_register( &$pObject ) {
		require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'bitcommerce_start_inc.php' );
		if( is_a( $pObject, 'BitUser' ) && !empty( $pObject->mUserId ) && !empty( $_REQUEST['com_interests'] ) ) {
			CommerceCustomer::syncBitUser( $pObject->mInfo );
			$newCustomer = new CommerceCustomer( $pObject->mUserId );
			foreach( $_REQUEST['com_interests'] as $intId ) {
				$newCustomer->storeCustomerInterest( array( 'customers_id' => $pObject->mUserId, 'interests_id' => $intId ) );
			}
		}
	}

	function sphinx_bitcommerce_results( $pResults ) {
		global $gSphinxSystem, $gBitUser, $gBitProduct;
		require_once( BITCOMMERCE_PKG_INCLUDE_PATH.'bitcommerce_start_inc.php' );
		if( !empty( $pResults['matches'] ) ) {
			$contentIds = array_keys( $pResults['matches'] );

			$listHash = array( 'content_id_list' => $contentIds );
			$listHash['hash_key'] = 'products.page_id';
			$listHash['include_data'] = TRUE;
			if( $productsList = $gBitProduct->getList( $listHash ) ) {
				reset( $contentIds );
				foreach( $productsList as $product ) {
					$contentId = $product['content_id'];
					$product['data'] = $product['products_description'];
					$product['format_guid'] = 'bithtml';
					$product['stripped_data'] = (!empty( $product['data'] ) ? strip_tags( LibertyContent::parseDataHash( $product ) ) : '' );
					$pResults['matches'][$contentId] = array_merge( $pResults['matches'][$contentId], $product );
					$excerptSources[array_search($contentId,$contentIds)] = $product['stripped_data'];
				}
				ksort( $excerptSources );
				$gSphinxSystem->populateExcerpts( $pResults, $excerptSources );
			}
		}

		return $pResults;
	}

    //  Get a key position in array
    function array_kpos(&$array,$key) {
        $x=0;
        foreach($array as $i=>$v) {
            if($key===$i) return $x;
            $x++;
        }
        return false;
    }


}


?>
