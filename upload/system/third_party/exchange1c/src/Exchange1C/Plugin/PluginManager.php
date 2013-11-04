<?php namespace Exchange1C\Plugin;

use Exchange1C\Core\OpenCart;
use Exchange1C\Core\Log;

class PluginManager {

	/**
	 * Event map for plugins.
	 *
	 * @var array
	 */
	protected $eventMap = array();


	/**
	 * Directory for plugins.
	 *
	 * @var string
	 */
	protected $pluginsDir;


	/**
	 * Class constructor.
	 *
	 * @param string $dir
	 * @return void
	 */
	public function __construct($dir)
	{
		$this->pluginsDir = $dir;
	}


	/**
	 * Load plugins from dir.
	 *
	 * @param string $dir
	 * @return void
	 */
	public function addPlugins()
	{
		foreach(scandir($this->pluginsDir) as $pluginFile)
		{
			if ($pluginFile != '.' && $pluginFile != '..')
			{
				$pluginName = explode('.', $pluginFile);
				$pluginName = $pluginName[0];

				if (isset($pluginName[1]) && $pluginName[1] == 'php')
				{
					require "{$this->pluginsDir}/{$pluginFile}";

					$plugin = new $pluginName(OpenCart::getInstance());
					$plugin->init();

					foreach ($plugin->events as $eventName => $funcName)
					{
						$this->registerPlugin($pluginName, $eventName, array($plugin, $funcName));
						Log::write("Register event: {$pluginName}::{$funcName} on {$eventName}");
					}
				}
			}
		}
	}


	/**
	 * Register new plugin.
	 *
	 * @param string $pligunName
	 * @param string $eventName
	 * @param function $func
	 * @return void
	 */
	public function registerPlugin($pluginName, $eventName, $func)
	{
		$this->eventMap[] = array(
			'plugin' => $pluginName,
			'event'  => $eventName,
			'func'   => $func
		);
	}


	/**
	 * Run all plugins on event.
	 *
	 * @param string $event 
	 * @param array $args
	 * @return mixed
	 */
	public function runPlugins($event, $args = array())
	{
		foreach($this->eventMap as $plugin)
		{
			if ($plugin['event'] == $event)
			{
				$func = $plugin['func'];

				if (is_callable($func))
				{
					Log::write("Run plugin: {$plugin['plugin']} on {$plugin['event']}");
					return call_user_func_array($func, $args);
				}
			}
		}

		return false;
	}

}