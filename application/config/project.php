<?php

function is_true($val) {
	if($val == '') return false;
	return in_array(strtolower($val), array('1', 'true', 'ja', 'yes', 'j', 'y', 'ok'));
}

define('RESULT_CACHE_LIFETIME', 300);

$pconfig = Helper_Message::loadFile(APPPATH . 'config/base.config');

$pconfig['project.show_article_favorite_count'] = is_true($pconfig['project.show_article_favorite_count']);
$pconfig['project.show_intro_text'] = is_true($pconfig['project.show_intro_text']);
$pconfig['module.expert'] = is_true($pconfig['module.expert']);
$pconfig['module.news'] = is_true($pconfig['module.news']);
$pconfig['module.planning'] = is_true($pconfig['module.planning']);
$pconfig['module.study'] = is_true($pconfig['module.study']);
$pconfig['module.method'] = is_true($pconfig['module.method']);
$pconfig['module.qa'] = is_true($pconfig['module.qa']);
$pconfig['module.event'] = is_true($pconfig['module.event']);
$pconfig['module.about'] = is_true($pconfig['module.about']);
$pconfig['module.eventbox'] = is_true($pconfig['module.eventbox']);
$pconfig['module.newsbox'] = is_true($pconfig['module.newsbox']);
$pconfig['module.videobox'] = is_true($pconfig['module.videobox']);

$pconfig['module.expert.introtext'] = is_true($pconfig['module.expert.introtext']);
$pconfig['module.news.introtext'] = is_true($pconfig['module.news.introtext']);
$pconfig['module.study.introtext'] = is_true($pconfig['module.study.introtext']);
$pconfig['module.method.introtext'] = is_true($pconfig['module.method.introtext']);
$pconfig['module.qa.introtext'] = is_true($pconfig['module.qa.introtext']);
$pconfig['module.event.introtext'] = is_true($pconfig['module.event.introtext']);
$pconfig['module.video_textbox.replacement'] = is_true($pconfig['module.video_textbox.replacement']);
$pconfig['add_expert.expert.active'] = is_true($pconfig['add_expert.expert.active']);
$pconfig['add_expert.study.active'] = is_true($pconfig['add_expert.study.active']);
$pconfig['add_expert.global.active'] = is_true($pconfig['add_expert.global.active']);

$baseConfig = array(
	//////////////////////////////////////
	//
	// Set your configuration options here
	//
	//////////////////////////////////////

	// API
	'api_key' => 'YOUR_API_KEY',

	// Tool paths
	'convert_path' => 'PATH_TO_WKHTMLTOPDF', // recommended: /usr/bin/convert'
	'wkhtmltopdf_path' => 'PATH_TO_WKHTMLTOPDF', // recommended: '/usr/bin/wkhtmltopdf'
	'wkhtmltopdf_http_auth_username' => 'YOUR_WKHTMLTOPDF_USERNAME',
	'wkhtmltopdf_http_auth_password' => 'YOUR_WKHTMLTOPDF_PASSWORD',

	// Directories. Changing these should not be necessary.
	'upload_dir' => DOCROOT . 'files',
	'file_dir' => APPPATH . 'data/files',
	'public_preview_path' => DOCROOT . 'media/preview',
	'public_media_path' => DOCROOT . 'media',
	'public_imgupload_path' => 'files/static_pages_img',

	// Environment settings
	'smarty_force_compile' => true,
	'dateformat' => 'd.m.Y',
    'datetimeformat' => 'd.m.Y H:i',
    'set_locale' => array(LC_ALL, 'en_US.utf-8'),
	'locale' => 'de_DE',

	// Files and Configs
	// If you dont know what to set here, just set the path to ''.
	// You can add these information manually later in the backend.
    'files' => array('form' => array(
    						 'study' => 'PATH_TO_FILE_IN_application/public/files/downloads',
	                         'method' => 'PATH_TO_FILE_IN_application/public/files/downloads',
	                         'instrument' => 'PATH_TO_FILE_IN_application/public/files/downloads'),
                     'image' => array(
                     		'header' => 'PATH_TO_FILE_IN_application/public/files/img',
							'home' => 'PATH_TO_FILE_IN_application/public/files/img')
                     ),

	// Paths to message files
	'article' => Yaml::loadFile(APPPATH . 'data/articleconfig.yaml'),
    'messages_backend' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.backend.en'),
    'messages' => Helper_Message::loadFile(APPPATH . 'messages/project.messages.frontend.en'),
	'mobile_messages' => Helper_Message::loadFile(APPPATH . 'messages/mobile.messages.en'),
    'message_field_types' => Helper_Message::loadFile(APPPATH . 'messages/field_types'),
    'messages_backend_file' => APPPATH . 'messages/project.messages.backend.en',
    'frontend_message_groups' => APPPATH . 'messages/frontend_message_groups.en',
    'backend_message_groups' => APPPATH . 'messages/backend_message_groups.en',
    'mobile_message_groups' => APPPATH . 'messages/mobile_message_groups.en',
    'messages_file' => APPPATH . 'messages/project.messages.frontend.en',
	'mobile_messages_file' => APPPATH . 'messages/mobile.messages.en',

	/////////////////////////////////////////////
	//
	// Don't change these configuration settings
	//
	/////////////////////////////////////////////

	'name' => $pconfig['project.name'],
	'show_article_favorite_count' => $pconfig['project.show_article_favorite_count'],
	'show_intro' => $pconfig['project.show_intro_text'],
	'video_textbox_replacement' => $pconfig['module.video_textbox.replacement'],
    'add_expert' => array(
        'expert' => $pconfig['add_expert.expert.active'],
        'study' => $pconfig['add_expert.study.active'],
        'global' => $pconfig['add_expert.global.active'],
    ),

	'modules' => array(
		'expert' => $pconfig['module.expert'],
	    'news' => $pconfig['module.news'],
	    'planning' => $pconfig['module.planning'],
	    'study' => $pconfig['module.study'],
	    'method' => $pconfig['module.method'],
	    'qa' => $pconfig['module.qa'],
	    'event' => $pconfig['module.event'],
	    'about' => $pconfig['module.about'],
	    'eventbox' => $pconfig['module.eventbox'],
	    'newsbox' => $pconfig['module.newsbox'],
	    'videobox' => $pconfig['module.videobox'],
	),

    'introtext' => array(
		'expert' => $pconfig['module.expert.introtext'],
	    'study' => $pconfig['module.study.introtext'],
	    'method' => $pconfig['module.method.introtext'],
	    'qa' => $pconfig['module.qa.introtext'],
	    'event' => $pconfig['module.event.introtext'],
	    'news' => $pconfig['module.news.introtext'],
	),

	'email' => array(
		'activation' => array(
			'from' => $pconfig['email.activation.from'],
		),
        'contact' => array(
        	'to' => $pconfig['email.contact.to'],
        ),
        'new_password' => array(
        	'from' => $pconfig['email.new_password.from'],
        ),
        'remove_account' => array(
        	'from' => $pconfig['email.remove_account.from'],
        ),
        'readforpublish' => array(
        	'from' => $pconfig['email.readforpublish.from'],
        	'to' => $pconfig['email.readforpublish.to'],
        ),
        'question_info' => array(
        	'from' => $pconfig['email.question_info.from']
        ),
        'new_question' => array(
        	'to' => $pconfig['email.new_question.to']
        ),
        'published' => array(
        	'from' => $pconfig['email.published.from']
        ),
	),

	'countries' => array(
		"ab", "ad", "ae", "af", "ag", "ai", "al", "am", "ao", "aq", "ar", "as", "at", "au", "aw", "ax", "az", "ba", "bb", "bd", "be", "bf", "bg", "bh", "bi", "bj", "bl", "bm", "bn", "bo", "bq", "br", "bs", "bt", "bv", "bw", "by", "bz", "ca", "cc", "cd", "cf", "cg", "ch", "ci", "ck", "cl", "cm", "cn", "co", "cr", "cu", "cv", "cw", "cx", "cy", "cz", "de", "dj", "dk", "dm", "do", "dz", "ec", "ee", "eg", "eh", "er", "es", "et", "fi", "fj", "fk", "fm", "fo", "fr", "ga", "gb", "gd", "ge", "gf", "gg", "gh", "gi", "gl", "gm", "gn", "gp", "gq", "gr", "gs", "gt", "gu", "gw", "gy", "hk", "hm", "hn", "hr", "ht", "hu", "id", "ie", "il", "im", "in", "io", "iq", "ir", "is", "it", "je", "jm", "jo", "jp", "ke", "kg", "kh", "ki", "km", "kn", "kp", "kr", "kw", "ky", "kz", "la", "lb", "lc", "li", "lk", "lr", "ls", "lt", "lu", "lv", "ly", "ma", "mc", "md", "me", "mf", "mg", "mh", "mk", "ml", "mm", "mn", "mo", "mp", "mq", "mr", "ms", "mt", "mu", "mv", "mw", "mx", "my", "mz", "na", "nc", "ne", "nf", "ng", "ni", "nl", "no", "np", "nr", "nu", "nz", "om", "pa", "pe", "pf", "pg", "ph", "pk", "pl", "pm", "pn", "pr", "ps", "pt", "pw", "py", "qa", "re", "ro", "rs", "ru", "rw", "sa", "sb", "sc", "sd", "se", "sg", "sh", "si", "sj", "sk", "sl", "sm", "sn", "so", "sr", "ss", "st", "sv", "sx", "sy", "sz", "tc", "td", "tf", "tg", "th", "tj", "tk", "tl", "tm", "tn", "to", "tr", "tt", "tv", "tw", "tz", "ua", "ug", "um", "us", "uy", "uz", "va", "vc", "ve", "vg", "vi", "vn", "vu", "wf", "ws", "ye", "yt", "za", "zm", "zw"
	),
	'projectstatus' => array(
		1 => 'planned',
		2 => 'open',
		3 => 'finished',
	),
	'participation' => array(
		1 => 'open',
		2 => 'register',
		3 => 'invitation',
	),

    'thumbnail_sizes' => array('43x42', '80x80', '250x188', '170x128', 'x180', '180x180', '189x', '189x160', '89x', '89x69'),
    'datepicker_config' => "{onSelect: function(input, inst){ $(this).siblings('input[type=hidden]').val($(this).parent().find('input[type=text]').map(function(){return $(this).val();}).get().join(' ')); $(this).siblings('input[type=hidden]').trigger('update'); }, inline: true, dateFormat: 'dd.mm.yy',firstDay: 1,dayNames: ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],dayNamesMin: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],dayNamesShort: ['Son', 'Mon', 'Din', 'Mit', 'Don', 'Fre', 'Sam'],monthNames: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],monthNamesShort: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez']}"
);

/**
 * Load environment specific configuration data
 * See deploy.rb for details what is copied
 */
$envConfig = Kohana::$config->load('environment')->as_array();
$config = array_merge($baseConfig,$envConfig);

return $config;
