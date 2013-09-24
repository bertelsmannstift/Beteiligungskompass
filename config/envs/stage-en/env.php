<?php
return array(
	'locale' => 'en_US',
    'environment' => Kohana::PRODUCTION,
	'convert_path' => 'PATH/TO/CONVERT', // '/usr/bin/convert',
	'smarty_force_compile' => false,
	'wkhtmltopdf_path' => 'PATH/TO/WKHTMLTOPDF',
    'etrackercode' => 'CODE',

    'messages_backend' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.backend.en'),
    'messages_backend_file' => APPPATH . 'messages/project.messages.backend.en',

	'messages' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.frontend.en'),
	'messages_file' => APPPATH . 'messages/project.messages.frontend.en',

	'mobile_messages' => Helper_Message::loadFile(APPPATH . 'messages/mobile.messages.en'),
	'mobile_messages_file' => APPPATH . 'messages/mobile.messages.en',

    'backend_message_groups' => APPPATH . 'messages/backend_message_groups.en',
    'frontend_message_groups' => APPPATH . 'messages/frontend_message_groups.en',
    'mobile_message_groups' => APPPATH . 'messages/mobile_message_groups.en',

	);
?>