<?php
/**
 * @package bitcommerce
 * @author spiderr <spiderr@bitweaver.org>
 * Copyright (c) 2020 bitweaver.org, All Rights Reserved
 * This source file is subject to the 2.0 GNU GENERAL PUBLIC LICENSE. 
 *
 * Base class for all plugin types.
 *
 */

abstract class CommercePluginBase extends BitBase {

	public $code;
	public $title;
	public $description;
	public $enabled; 
	public $icon; 

	// Cached computed vars
	protected $isEnabled;
	protected $isInstalled;
	protected $sort_order;
	protected $mConfigKey;
	protected $mModuleKey;

	abstract protected function getModuleType();
	// Check if module is installed (Administration Tool)

	public function __construct() {
		parent::__construct();
		$this->code = get_called_class();
		$this->mConfigKey = $this->getConfigKey();
		$this->mModuleKey = $this->getModuleKey();
		$this->enabled = $this->isEnabled(); // legacy support for old plugins
		$this->check = $this->isInstalled(); // legacy support for old plugins
	}

	protected function getModuleKey() {
		return strtoupper( $this->getModuleType() );
	}

	protected function getConfigKey() {
		return strtoupper( $this->code );
	}

	protected function getModuleKeyTrunk() {
		return 'MODULE_'.$this->mModuleKey.'_'.$this->mConfigKey;
	}

	protected function getSortOrderKey() {
		return $this->getModuleKeyTrunk().'_SORT_ORDER';
	}

	protected function getStatusKey() {
		return $this->getModuleKeyTrunk().'_STATUS';
	}

	public function keys() {
		return array_keys( $this->config() );
	}

	public function install() {
		if( !$this->isInstalled() ) {
			$this->mDb->StartTrans();
			foreach( $this->config() as $configKey => $configHash ) {
				$this->storeModuleConfigHash( $configKey, $configHash );
			}
			$this->mDb->CompleteTrans();
		}
	}

	protected function storeModuleConfigHash( $pModuleConfigKey, $pModuleConfigHash ) {
		// set some defaults if not set
		foreach( array( 'configuration_key' => $pModuleConfigKey, 'sort_order' => 0, 'date_added' => 'now()', 'configuration_group_id' => '6' ) as $key => $defaultValue ) {
			if( empty( $pModuleConfigHash[$key] ) ) {
				$pModuleConfigHash[$key] = $defaultValue;
			}
		}
		$this->mDb->AssociateInsert( TABLE_CONFIGURATION, $pModuleConfigHash );
	}

	protected function getModuleConfigValue( $pConfigKeyBranch, $pDefaultValue=NULL ) {
		global $gCommerceSystem;
		return $gCommerceSystem->getConfig( $this->getModuleKeyTrunk().$pConfigKeyBranch, $pDefaultValue );
	}

	protected function storeModuleConfigValue( $pConfigKeyBranch, $pConfigValue ) {
		global $gCommerceSystem;
		$gCommerceSystem->storeConfig( $this->getModuleKeyTrunk().$pConfigKeyBranch, $pConfigValue );
	}

	public function getDefaultConfig() {
		return $this->config();
	}

	public function getActiveConfig() {
		return $this->mDb->getAssoc( 'SELECT `configuration_key`, * FROM ' . TABLE_CONFIGURATION . ' WHERE `configuration_key` LIKE ?', array( $this->getModuleKeyTrunk().'_%' ) );
	}

	public function remove() {
		$this->mDb->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE `configuration_key` IN ('" . implode("', '", $this->keys()) . "')");
	}

	public function isEnabled() {
		global $gCommerceSystem;
		if( !isset( $this->isEnabled ) ) {
			$this->isEnabled = $gCommerceSystem->isConfigActive( $this->getStatusKey() );
		}
		return $this->isEnabled;
	}

	public function check() {
		return $this->isInstalled();
	}

	public function getSortOrder() {
		global $gCommerceSystem;
		if( !isset( $this->sort_order ) ) {
			$this->sort_order = $gCommerceSystem->isConfigActive( $this->getSortOrderKey() );
		}
		return $this->sort_order;
	}

	public function isInstalled() {
		global $gCommerceSystem;
		if( !isset( $this->isInstalled ) ) {
			$this->isInstalled= $gCommerceSystem->isConfigLoaded( $this->getStatusKey() );
		}
		$this->check = $this->isInstalled; // legacy variable
		return $this->isInstalled;
	}

	public function fixConfig() {
		if( $this->isInstalled() ) {
			$this->mDb->StartTrans();
			$activeKeys = array_keys( $this->getActiveConfig() );
			$defaultConfig = $this->getDefaultConfig();
			$defaultKeys = array_keys( $defaultConfig );

			if( $missingConfigKeys = array_flip( array_diff( $defaultKeys, $activeKeys ) ) ) {
				foreach( $defaultConfig as $configKey => $configHash ) {
					if( !empty( $missingConfigKeys[$configKey] ) && !is_null( $configHash['configuration_value'] ) ) {
						$this->storeModuleConfigHash( $configKey, $configHash );
					}
				}
			}
			if( $unusedConfigKeys = array_flip( array_diff( $activeKeys, $defaultKeys ) ) ) {
				global $gCommerceSystem;
				foreach( $unusedConfigKeys as $configKey=>$configValue ) {
					$gCommerceSystem->storeConfig( $configKey, NULL );
				}
			}
			$this->mDb->CompleteTrans();
		}
	}

	public function getConfig( $pConfigName, $pDefault=NULL ) {
		global $gCommerceSystem;
		return $gCommerceSystem->getConfig( $pConfigName, $pDefault );
	}

	public function isConfigActive( $pConfigName ) {
		global $gCommerceSystem;
		return $gCommerceSystem->isConfigActive( $pConfigName );
	}

	public function storeConfig ( $pConfigKey, $pConfigValue ) {
		global $gCommerceSystem;
		return $gCommerceSystem->storeConfig( $pConfigKey, $pConfigValue );
	}

	/**
	* rows for com_configuration table as associative array of column => value
	*/
	protected function config() {
		return array( 
			$this->getStatusKey() => array(
				'configuration_title' => 'Enable '.$this->title,
				'configuration_value' => 'True',
				'sort_order' => '1',
				'configuration_description' => 'Do you want '.$this->title.' '.$this->getModuleType().' active?',
				'set_function' => "zen_cfg_select_option(array('True', 'False'), ",
			),
			$this->getSortOrderKey() => array(
				'configuration_title' => 'Sort Order',
				'configuration_description' => 'Sort order of display.',
				'sort_order' => '1',
			),
		);
	}

}

