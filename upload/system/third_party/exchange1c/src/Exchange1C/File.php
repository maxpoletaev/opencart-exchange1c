<?php namespace Exchange1C;

class File {

	/**
	 * Uploading file.
	 *
	 * @param string $filename
	 * @param string $savePath
	 */
	public static function upload($filename, $savePath)
	{

	}


	/**
	 * Read raw post data from php://input.
	 *
	 * @param string $savePath.
	 */
	public static function stream($savePath)
	{
		try
		{
			$content = file_get_contents('php://input');
		}
		catch
		{
			throw new Exception('Failed to open stream.');
		}

		if (is_writable($savePath))
		{
			if (strlen($content) > 0)
			{
				$upload = fopen($savePath, 'w');
				fwrite($fp, $content);
				fclose($upload);
			}
		}
		else
		{
			throw new Exception("{$savePath} is not writeable.");
		}
	}
	

	/**
	 * Detect file type (import.xml or offers.xml) of signature.
	 *
	 * @param string $content
	 * @param bool $isFile
	 * @return mixed
	 */
	public static function type($content, $isFile = false)
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