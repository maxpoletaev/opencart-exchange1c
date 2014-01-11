<?php namespace Exchange1C;

use Exchange1C\Import\CategoryImport;
use Exchange1C\Import\ProductImport;

class Protocol {

	/**
	 * Init exchange protocol.
	 *
	 * @return void
	 */
	public function watch()
	{
		if (Request::get('mode') && Request::get('type'))
		{
			$type = Request::get('type');
			$mode = ucfirst(Request::get('mode'));

			$funcName = "{$type}{$mode}";
			
			if (is_callable(array($this, $funcName)))
			{
				$this->{$funcName}();
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
		$username = Request::server('PHP_AUTH_USER',
			Request::get('username')
		);
		
		$password = Request::server('PHP_AUTH_PW',
			Request::get('password')
		);

		Log::debug("Auth attempt of {$username}");

		if (Auth::attempt($username, $password))
		{
			$token = OpenCart::session()->data['token'];

			Log::debug("Authentication succeeded");
			echo "success\n", "key\n", $token;
		}
		else
		{
			Log::error("Authentication failed");
			die("failure\n" . "Authentication failed");
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
			$limit = (str_replace('M', '', ini_get('post_max_size')) * 1024 * 1024);
			echo "zip=no\n", "file_limit={$limit}\n";

			Log::debug("Catalog inited.");
		}
		else
		{
			Log::error("Authentication failed");
			die("failure\n" . "Authentication failed");
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
			$filename = Request::get('filename');

			if (strrpos($filename, 'import_files'))
			{
				// @TODO: Uploading images.
			}
			else
			{
				File::stream(E1C_DIR . "/cache/{$filename}");
			}
			
			Log::debug("File {$filename} is uploaded.");
			print "success";
		}
		else
		{
			Log::error("Authentication failed");
			die("failure\n" . "Authentication failed");
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
				$filePath = E1C_DIR . "/cache/{$fileName}";
				
				$this->importCategories($filePath);
				$this->importProducts($filePath);

				unlink($filePath);

				Log::debug("Import completed");
				print "success";
			}	
		}
		else
		{
			Log::error("Authentication failed");
			die("failure\n" . "Authentication failed");
		}
	}

	/**
	 * Helper for categories parser.
	 *
	 * @return void
	 */
	private function importCategories($filePath)
	{
		$categoryImport = new CategoryImport();

		switch(File::type($filePath))
		{
			case 'import.xml':
				Log::debug("Import categories from import.xml");
				$categoryImport->parseImport($filePath);
			break;
		}

		unset($categoryImport);
	}

	/**
	 * Helper for product parser.
	 *
	 * @return void
	 */
	private function importProducts($filePath)
	{
		$productImport = new ProductImport();

		switch (File::type($filePath))
		{
			case 'import.xml':
				Log::debug("Import products from import.xml");
				$productImport->parseImport($filePath);
			break;

			case 'offers.xml':
				Log::debug("Import products form offers.xml");
				$productImport->parseOffers($filePath);
			break;
		}

		unset($productImport);
	}

}
