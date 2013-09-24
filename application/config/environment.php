<?php
return array(
	'environment' => Kohana::DEVELOPMENT,
	'convert_path' => 'PATH_TO_WKHTMLTOPDF', // recommended: /usr/bin/convert'
	'wkhtmltopdf_path' => 'PATH_TO_WKHTMLTOPDF', // recommended: '/usr/bin/wkhtmltopdf'
	'set_locale' => array(LC_ALL, 'de_DE.utf-8', 'de_DE', 'de', 'ge'),
	'etrackercode' => 'no-real-code-in-development',


	'files' => array(
		'form' => array(
			'study' => 'PATH_TO_FILE_IN_application/public/files/downloads',
			'method' => 'PATH_TO_FILE_IN_application/public/files/downloads',
			'instrument' => 'PATH_TO_FILE_IN_application/public/files/downloads'),
		'image' => array(
			'header' => 'PATH_TO_FILE_IN_application/public/files/img',
			'home' => 'PATH_TO_FILE_IN_application/public/files/img')
	),
);
?>