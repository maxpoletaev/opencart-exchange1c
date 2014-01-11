<?php namespace Exchange1C;

use Exception;

class File {

	/**
	 * Read raw post data from php://input.
	 *
	 * @param string $savePath.
	 */
	public static function stream($savePath)
	{
		$content = @file_get_contents('php://input');

		if ($content && strlen($content) > 0)
		{
			$upload = fopen($savePath, 'w');
			fwrite($upload, $content);
			fclose($upload);
		}
		else
		{
			Log::error('File upload error: content is empty');
		}
	}

	/**
	 * Detect file type (import.xml or offers.xml) of signature.
	 *
	 * @param string $content
	 * @param bool $isFile
	 * @return mixed
	 */
	public static function type($content, $isFile = true)
	{
		if ($isFile)
		{
			if (file_exists($content) && is_readable($content))
			{
				$handle = fopen($content, 'r');
				$buffer = fread($handle, 256);
				fclose($handle);
			}
		}
		else
		{
			$buffer = substr($content, 0, 256);
			unset($content);
		}

		$signatures = array(
			'import.xml' => 'Классификатор',
			'offers.xml' => 'ПакетПредложений'
		);

		foreach ($signatures as $type => $signature)
		{
			if (strpos($buffer, $signature))
			{
				return $type;
			}
		}

		return false;
	}

}
