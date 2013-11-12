<?php namespace Exchange1C;

class Log {

	/**
	 * Write log.
	 *
	 * @param string $message
	 * @param string $type
	 * @return void
	 */
	public static function write($message, $type = 'notify')
	{
		$logType = strtoupper($type);
		
		$date = date('Y-m-d');
		$time = date('H:i:s');

		$string = "{{$time}} [$logType]: {$message} \n";

		$handle = fopen(E1C_DIR . "/logs/{$date}.log", 'a');
		fwrite($handle, $string);
		fclose($handle);
	}


	/**
	 * Write debug message.
	 *
	 * @var string $message
	 * @return void
	 */
	public static function debug($message)
	{
		$trace = debug_backtrace();
		
		$class = $trace[1]['class'];
		$method = $trace[1]['function'];
		
		$debugMessage = "{$message} ({$class}::{$method})";
		static::write($debugMessage, 'debug');
	}


	/**
	 * Write error message.
	 *
	 * @var string $message
	 * @return void
	 */
	public static function error($message)
	{
		$trace = debug_backtrace();
		
		$file = $trace[1]['file'];
		$line = $trace[1]['line'];

		$errorMessage = "{$message} ({$file}:{$line})";
		static::write($errorMessage, 'error');
	}

}