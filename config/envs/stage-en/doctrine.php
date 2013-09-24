<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'connection' => array(
		'dbname'   => 'DBNAME',
		'host'     => 'HOSTNAME',
		'user'     => 'USERNAME',
		'password' => 'PASSWORD',
	),
    'customStringFunctions' => array('SHA1' => 'Doctrine_Function_SHA1', 'FIELD' => 'Doctrine_Function_Field'),
	'cache_prefix' => 'opensource_stage_en',
);