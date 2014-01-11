<?php namespace Exchange1C;

class Request {

	/**
	 * $_GET
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($key, $default = false)
	{
		isset($_GET[$key])? $out = $_GET[$key] : $out = $default;
		return $out;
	}

	/**
	 * $_POST
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function post($key, $default = false)
	{
		isset($_POST[$key])? $out = $_POST[$key] : $out = $default;
		return $out;
	}

	/**
	 * $_REQUEST
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function all($key, $default = false)
	{
		isset($_REQUEST[$key])? $out = $_REQUEST[$key] : $out = $default;
		return $out;
	}

	/**
	 * $_SERVER
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function server($key, $default = false)
	{
		isset($_SERVER[$key])? $out = $_SERVER[$key] : $out = $default;
		return $out;
	}

	/**
	 * $_COOKIE
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function cookie($key, $default = false)
	{
		isset($_COOKIE[$key])? $out = $_COOKIE[$key] : $out = $default;
		return $out;
	}

}
