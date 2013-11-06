<?php namespace Exchange1C\Import;

use Exchange1C\Log;
use Exchange1C\OpenCart;
use Exchange1C\Plugin\PluginManager;

class BaseImport {

	/**
	 * OpenCart language ids.
	 *
	 * @var array
	 */
	protected $languageIds = array();


	/**
	 * Plugin manager instance.
	 *
	 * @var PluginManager
	 */
	protected $pluginManager;


	/**
	 * Class constructor.
	 *
	 * @param Registry $registry
	 * @return void
	 */
	public function __construct()
	{		
		$dbPrefix = DB_PREFIX;

		foreach (OpenCart::db()->query("SELECT * FROM {$dbPrefix}language")->rows as $language)
		{
			$this->languageIds[] = $language['language_id'];
		}

		$this->pluginManager = new PluginManager(E1C_DIR.'/plugins');
		$this->pluginManager->addPlugins();
	}

}