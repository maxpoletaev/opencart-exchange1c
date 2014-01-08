<?php

spl_autoload_register(function($className)
{
	$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	$classFile = __DIR__."/src/{$className}.php";

	if (file_exists($classFile)) {
		include_once($classFile);
	}
});
