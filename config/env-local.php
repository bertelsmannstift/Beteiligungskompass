<?php
return array(
	'environment' => Kohana::DEVELOPMENT,
	'convert_path' => '/usr/local/bin/convert',
	'smarty_force_compile' => true,
	'wkhtmltopdf_path' => '/usr/local/bin/wkhtmltopdf',
	'set_locale' => array(LC_ALL, 'de_DE.utf-8', 'de_DE', 'de', 'ge'),
	'files' => array(
		'form' => array(
			'study' => '',
			'method' => '',
			'instrument' => ''),
		'image' => array(
			'header' => 'files/img/top-logo.png',
			'home' => 'files/img/logo/dashboard_logo_participationcompass.png')),
	'etrackercode' => 'no-real-code-in-development'
	);
?>