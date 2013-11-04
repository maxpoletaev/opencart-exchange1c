<?php namespace Exchange1C\Plugin;

abstract class Plugin {

	/**
	 * Plugin events.
	 *
	 * @var array
	 */
	public $events = array();


	/**
	 * Active or non active.
	 *
	 * @var bool
	 */
	private $active = true;


	/**
	 * OpenCart DB instance.
	 *
	 * @var DB
	 */
	protected $db;


	/**
	 * OpenCart Config instance.
	 * @var Config
	 */
	protected $config;


	/**
	 * Plugin constructor.
	 *
	 * @param Registry $registry
	 * @return void
	 */
	public function __construct($registry)
	{
		$this->db = $registry->get('db');
		$this->config = $registry->get('config');
	}


	/**
	 * Add event to plugnin event map.
	 *
	 * @param string $event
	 * @param string $funcName
	 */
	protected function addEventListener($event, $funcName = false)
	{
		if ($this->active)
		{
			$this->events[$event] = $funcName? $funcName : $event;
		}
	}


	/**
	 * Disable plugin.
	 *
	 * @return void
	 */
	protected function disable()
	{
		$this->active = false;
	}


	/**
	 * Init plugin.
	 *
	 * @return void
	 */
	abstract function init();
}