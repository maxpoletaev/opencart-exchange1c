<?php namespace Exchange1C\Core;

use \StdClass;
use \SimpleXMLElement;

class Scheme {

	/**
	 * Load JSON scheme file.
	 *
	 * @param string $chemeName
	 * @param SimpleXMLElement $newData
	 * @param array [$oldData]
	 * @return array
	 */
	public static function process($schemeName, $newData, $oldData = array())
	{
		$result = array();
		
		$schemePath = E1C_DIR."/schemes/{$schemeName}.json";

		if (file_exists($schemePath))
		{
			$scheme = json_decode(file_get_contents($schemePath));

			if ($newData instanceof SimpleXMLElement)
			{
				self::prepareNewData($scheme, $newData);
			}

			foreach ($scheme as $schemeKey => $schemeData)
			{
				if (isset($newData->$schemeKey))
				{
					if ($schemeData->overwrite || empty($oldData[$schemeKey]))
					{
						switch($schemeData->type)
						{
							case 'string': $result[$schemeKey] = (string)$newData->$schemeKey; break;
							case 'int':    $result[$schemeKey] = (int)$newData->$schemeKey;    break;
							case 'float':  $result[$schemeKey] = (float)$newData->$schemeKey;  break;
							case 'array':  $result[$schemeKey] = (array)$newData->$schemeKey;  break;
							case 'bool':   $result[$schemeKey] = (bool)$newData->$schemeKey;   break;
						}
					}
					else
					{
						$result[$schemeKey] = $oldData[$schemeKey];
					}
				}
				else
				{
					if (isset($oldData[$schemeKey]))
					{
						$result[$schemeKey] = $oldData[$schemeKey];
					}
					else
					{
						$result[$schemeKey] = $schemeData->default;
					}
				}
			}
		}

		return $result;
	}


	/**
	 * Preparation scheme for parsing. 
	 *
	 * @param array $scheme
	 * @param SimpleXMLElement &$newData
	 * @return void
	 */
	private static function prepareNewData($scheme, &$newData)
	{
		$result = new StdClass;

		foreach ($scheme as $schemeKey => $schemeData)
		{
			if (isset($schemeData->field))
			{
				$fieldName = $schemeData->field;

				if (isset($newData->$fieldName))
				{
					$result->$schemeKey = $newData->$fieldName;
				}
			}
		}

		$newData = $result;
	}

}