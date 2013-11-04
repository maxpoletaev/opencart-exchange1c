<?php

spl_autoload_register(function($className)
{
	$classFile = str_replace('\\', '/', $className);
	require_once __DIR__ . "/{$classFile}.php";
});