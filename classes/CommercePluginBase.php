<?php
// +----------------------------------------------------------------------+
// | bitcommerce                                                          |
// +----------------------------------------------------------------------+
// | Copyright (c) 2013 bitcommerce.org                                   |
// |                                                                      |
// | http://www.bitcommerce.org                                           |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license        |
// +----------------------------------------------------------------------+

abstract class CommercePluginBase extends BitBase {

	protected $mStatusKey;

	abstract function keys();
	abstract function install();
	// Check if module is installed (Administration Tool)

	public function __construct() {
		parent::__construct();
	}

	function remove() {
		$this->mDb->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` IN ('" . implode("', '", $this->keys()) . "')");
	}

	function isEnabled() {
		global $gCommerceSystem;
		if( !isset( $this->isEnabled ) ) {
			$this->isEnabled = $gCommerceSystem->isConfigActive( $this->mStatusKey );
		}
		return $this->isEnabled;
	}

	function check() {
		return $this->isEnabled();
	}

}

