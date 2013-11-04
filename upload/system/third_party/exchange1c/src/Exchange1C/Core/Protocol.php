<?php namespace Exchange1C\Core;

class Protocol {

	/**
	 * Init exchange protocol.
	 *
	 * @return void
	 */
	public function run()
	{
		if (Request::get('mode') && Request::get('type'))
		{
			$type = Request::get('type');
			$mode = ucfirst(Request::get('mode'));

			$funcName = "{$type}{$mode}";

			if (is_callable(array($this, $funcName)))
			{
				$this->$funcName();
			}
		}
	}


	/**
	 * Authentication.
	 *
	 * @return void
	 */
	public function catalogCheckauth()
	{
		$username = Request::server('PHP_AUTH_USER');
		$password = Request::server('PHP_AUTH_PW');

		if (Auth::attempt($username, $password))
		{
			$token = OpenCart::session()->data['token'];
			echo "success\n", "key\n", $token;
		}
		else
		{
			die("failure\n" . "Authentication failed.");
		}
	}


	/**
	 * Init catalog.
	 *
	 * @return void
	 */
	public function catalogInit()
	{
		if (Auth::check())
		{
			print "success";
		}
		else
		{
			die("failure\n" . "Authentication failed.");
		}
	}


	/**
	 * Upload file.
	 *
	 * @return void
	 */
	public function catalogFile()
	{
		if (Auth::check())
		{
			print "success";
		}
		else
		{
			die("failure\n" . "Authentication failed.");
		}
	}


	/**
	 * Run import.
	 *
	 * @return void
	 */
	public function catalogImport()
	{
		if (Auth::check())
		{
			if (Request::get('filename'))
			{
				$fileName = Request::get('filename');
				
				$this->importCategories($fileName);
				$this->importProducts($fileName);

				print "success";
			}	
		}
		else
		{
			die("failure\n" . "Authentication failed.");
		}
	}


	/**
	 * Helper for categories parser.
	 *
	 * @return void
	 */
	private function importCategories($fileName)
	{
		$filePath = E1C_DIR . "/cache/{$fileName}";

		$categoryImport = new CategoryImport();
		$categoryImport->addPlugins();

		if (strpos($fileName, 'import'))
		{
			$categoryImport->parseImport($filePath);
		}

		unset($categoryImport);
	}


	/**
	 * Helper for product parser.
	 *
	 * @return void
	 */
	private function importProducts($fileName)
	{
		$filePath = E1C_DIR . "/cache/{$fileName}";

		$productImport = new ProductImport();
		$productImport->addPlugins();

		if (strpos($fileName, 'import'))
		{
			$productImport->parseImport($filePath);
		}
		else if (strpos($fileName, 'offers'))
		{
			$productImport->parseOffers($filePath);
		}

		unset($productImport);
	}
}