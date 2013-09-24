<?php
return array(
	'environment' => Kohana::PRODUCTION,
	'set_locale' => array(LC_ALL, 'de_DE.utf-8', 'de_DE', 'de', 'ge'),
	'locale' => 'de_DE',
	'convert_path' => 'PATH/TO/CONVERT', // '/usr/bin/convert',
	'smarty_force_compile' => false,
	'wkhtmltopdf_path' => 'PATH/TO/WKHTMLTOPDF',
    'etrackercode' => 'CODE',

    'messages_backend' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.backend.de'),
    'messages_backend_file' => APPPATH . 'messages/project.messages.backend.de',

	'messages' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.frontend.de'),
	'messages_file' => APPPATH . 'messages/project.messages.frontend.de',

	'mobile_messages' => Helper_Message::loadFile(APPPATH . 'messages/mobile.messages.de'),
	'mobile_messages_file' => APPPATH . 'messages/mobile.messages.de',

    'backend_message_groups' => APPPATH . 'messages/backend_message_groups.de',
    'frontend_message_groups' => APPPATH . 'messages/frontend_message_groups.de',
    'mobile_message_groups' => APPPATH . 'messages/mobile_message_groups.de',

	'files' => array(
		'form' => array(
			'study' => '',
			'method' => '',
			'instrument' => ''),
		'image' => array(
			'header' => 'files/img/top-logo.png',
			'home' => 'files/img/logo/dashboard_logo_participationcompass.png')
		),
	);
?>