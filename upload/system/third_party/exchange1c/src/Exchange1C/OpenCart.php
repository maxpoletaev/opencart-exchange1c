<?php namespace Exchange1C;

class OpenCart {

	/**
	 * Opencart registry instance.
	 *
	 * @var Registry
	 */
	protected static $registry;

	/**
	 * Get OpenCart object from registry.
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public static function __callStatic($name, $args = array())
	{
		return self::$registry->get($name);
	}

	/**
	 * Set registry instance.
	 *
	 * @param Registry
	 * @return void
	 */
	public static function setInstance($registry)
	{
		self::$registry = $registry;
	}

	/**
	 * Get OpenCart registry instance.
	 *
	 * @return Registry
	 */
	public static function getInstance()
	{
		return self::$registry;
	}

	/**
	 * OpenCart models loader.
	 *
	 * @param string $modelName
	 * @param Registry $registry
	 * @return Model
	 */
	public static function loadModel($modelName)
	{
		//@TODO: && $config->get('e1c_use_vqmod')
		if (class_exists('\VQMod'))
		{
			$modelFile = \VQMod::modCheck(DIR_APPLICATION . "model/{$modelName}.php");
		}
		else
		{
			$modelFile = DIR_APPLICATION . "model/{$modelName}.php";
		}

		$className = $class = 'Model' . preg_replace('/[^a-zA-Z0-9]/', '', $modelName);

		if (file_exists($modelFile))
		{
			require_once $modelFile;
			return new $className(self::$registry);
		}
	}

}
