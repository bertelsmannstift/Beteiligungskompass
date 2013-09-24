<?php defined('SYSPATH') or die('No direct script access.');

// SAMPLE DOCTRINE DB Connection details

return array(
	'connection' => array(
		'dbname'   => 'YOUR_DB_NAME',
		'host'     => '127.0.0.1',
		'user'     => 'USERNAME',
		'password' => 'PASSWORD',
	),
    'customStringFunctions' => array('SHA1' => 'Doctrine_Function_SHA1', 'FIELD' => 'Doctrine_Function_Field'),
);
