<?php
return array(
	'environment' => Kohana::PRODUCTION,
	'set_locale' => array(LC_ALL, 'en_US.utf-8'),
	'locale' => 'en_US',
	'convert_path' => 'PATH/TO/CONVERT', // '/usr/bin/convert',
	'smarty_force_compile' => false,
	'wkhtmltopdf_path' => 'PATH/TO/WKHTMLTOPDF',
    'etrackercode' => 'CODE',

    'messages_backend_file' => APPPATH . 'messages/project.messages.backend.en',
    'messages_backend' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.backend.en'),

	'messages' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.frontend.en'),
	'messages_file' => APPPATH . 'messages/project.messages.frontend.en',

	'mobile_messages' => Helper_Message::loadFile(APPPATH . 'messages/mobile.messages.en'),
	'mobile_messages_file' => APPPATH . 'messages/mobile.messages.en',

    'frontend_message_groups' => APPPATH . 'messages/frontend_message_groups.en',
    'backend_message_groups' => APPPATH . 'messages/backend_message_groups.en',
    'mobile_message_groups' => APPPATH . 'messages/mobile_message_groups.en',

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