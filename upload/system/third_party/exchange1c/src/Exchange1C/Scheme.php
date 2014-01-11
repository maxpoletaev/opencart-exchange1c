<?php namespace Exchange1C;

use StdClass;
use SimpleXMLElement;

class Scheme {

	/**
	 * @var StdClass
	 */
	protected $scheme;

	/**
	 * @param string $schemeName
	 */
	public function __construct($schemeName)
	{
		$schemePath = E1C_DIR."/schemes/{$schemeName}.json";

		if (file_exists($schemePath))
		{
			$this->scheme = json_decode(file_get_contents($schemePath));
		}
		else
		{
			Log::error("Scheme file not found");
		}
	}

	/**
	 * Load JSON scheme file.
	 *
	 * @param object $newData
	 * @param array [$oldData]
	 * @return array
	 */
	public function process($newData, $oldData = array())
	{
		$result = array();

		if ($newData instanceof SimpleXMLElement)
		{
			$newData = $this->prepareNewData($newData);
		}

		foreach ($this->scheme as $schemeKey => $schemeData)
		{
			if (isset($newData->{$schemeKey}))
			{
				if ($schemeData->overwrite || empty($oldData[$schemeKey]))
				{
					switch($schemeData->type)
					{
						case 'string': $result[$schemeKey] = (string) $newData->{$schemeKey}; break;
						case 'int':    $result[$schemeKey] = (int) $newData->{$schemeKey};    break;
						case 'float':  $result[$schemeKey] = (float) $newData->{$schemeKey};  break;
						case 'array':  $result[$schemeKey] = (array) $newData->{$schemeKey};  break;
						case 'bool':   $result[$schemeKey] = (bool) $newData->{$schemeKey};   break;
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

		return $result;
	}

	/**
	 * Preparation new data for parsing.
	 *
	 * @param SimpleXMLElement $newData
	 * @return void
	 */
	private function prepareNewData($newData)
	{
		$result = new StdClass;

		foreach ($this->scheme as $schemeKey => $schemeData)
		{
			if (isset($schemeData->field))
			{
				$fieldName = $schemeData->field;

				if (isset($newData->{$fieldName}))
				{
					$result->{$schemeKey} = $newData->{$fieldName};
				}
			}
		}

		return $result;
	}

}
