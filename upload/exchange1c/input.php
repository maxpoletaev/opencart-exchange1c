<?php

use Exchange1C\OpenCart;
use Exchange1C\Protocol;
use Exchange1C\Import\CategoryImport;
use Exchange1C\Import\ProductImport;

// Disable show warnings
error_reporting(E_ERROR);


// OpenCart Version
define('VERSION', '1.5.5.1.1');


// Exchange1C directory
define('E1C_DIR', __DIR__);


// Start time
$startTime = microtime(true);


// VirtualQMOD
require_once('../vqmod/vqmod.php');
VQMod::bootup();


// OpenCart
require_once(__DIR__ . '/../admin/config.php');
require_once(VQMod::modCheck(DIR_SYSTEM . 'startup.php'));


// Application Classes
require_once(VQMod::modCheck(DIR_SYSTEM . 'library/user.php'));


// Exchange 1C
require_once(DIR_SYSTEM . 'third_party/exchange1c/autoload.php');


// Registry
$registry = new Registry();


// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);


// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);


// Cache
$cache = new Cache();
$registry->set('cache', $cache);


// Config
$config = new Config();
$registry->set('config', $config);


// Session
$session = new Session();
$registry->set('session', $session); 


// User
$user = new User($registry);
$registry->set('user', $user);


// Settings
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");
 
foreach ($query->rows as $setting)
{
	if (!$setting['serialized'])
	{
		$config->set($setting['key'], $setting['value']);
	}
	else
	{
		$config->set($setting['key'], unserialize($setting['value']));
	}
}


// Language
$languages = array();
$query = $db->query("SELECT * FROM " . DB_PREFIX . "language");

foreach ($query->rows as $result)
{
	$languages[$result['code']] = $result;
}

$config->set('config_language_id', $languages[$config->get('config_admin_language')]['language_id']);


// Import
OpenCart::setInstance($registry);

// $categoryImport = new CategoryImport();
// $categoryImport->parseImport(__DIR__.'/cache/art_import.xml');
// unset($categoryImport);

// $productImport = new ProductImport();
// $productImport->parseImport(__DIR__.'/cache/art_import.xml');
// $productImport->parseOffers(__DIR__.'/cache/art_offers.xml');
// unset($productImport);


// Init exchange protocol
$protocol = new Protocol();
$protocol->run();


// End time
$endTime = microtime(true);


// Stats
$memory = round(memory_get_peak_usage(true) / 1024);
$time = round($endTime - $startTime, 3);

// print "=> Memory used: {$memory} kb \n";
// print "=> Execution time: {$time} s \n";