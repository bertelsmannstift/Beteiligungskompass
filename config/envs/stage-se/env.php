<?php
return array(
	'locale' => 'sv_SE',
	'environment' => Kohana::PRODUCTION,
	'convert_path' => 'PATH/TO/CONVERT', // '/usr/bin/convert',
	'smarty_force_compile' => false,
	'wkhtmltopdf_path' => 'PATH/TO/WKHTMLTOPDF',
    'etrackercode' => 'CODE',

	'messages_backend' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.backend.se'),
	'messages_backend_file' => APPPATH . 'messages/project.messages.backend.se',

	'messages' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.frontend.se'),
	'messages_file' => APPPATH . 'messages/project.messages.frontend.se',

	'mobile_messages' => Helper_Message::loadFile(APPPATH . 'messages/mobile.messages.se'),
	'mobile_messages_file' => APPPATH . 'messages/mobile.messages.se',

	'backend_message_groups' => APPPATH . 'messages/backend_message_groups.se',
	'frontend_message_groups' => APPPATH . 'messages/frontend_message_groups.se',
	'mobile_message_groups' => APPPATH . 'messages/mobile_message_groups.se',
	);
?>