<?php namespace Exchange1C\Core;

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
		echo "[$logType] $message \n";

		// @TODO: write log to file
	}

}