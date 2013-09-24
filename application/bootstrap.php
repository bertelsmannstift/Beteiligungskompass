<?php defined('SYSPATH') or die('No direct script access.');

// -- Environment setup --------------------------------------------------------

// Load the core Kohana class
require SYSPATH.'classes/kohana/core'.EXT;

if (is_file(APPPATH.'classes/kohana'.EXT)) {
	// Application extends the core
	require APPPATH.'classes/kohana'.EXT;
} else {
	// Load empty core extension
	require SYSPATH.'classes/kohana'.EXT;
}

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set('Europe/Berlin');

/**
 * Enable the Kohana auto-loader.
 *
 * @see  http://kohanaframework.org/guide/using.autoloading
 * @see  http://php.net/spl_autoload_register
 */
spl_autoload_register(array('Kohana', 'auto_load'));

/**
 * Enable the Kohana auto-loader for unserialization.
 *
 * @see  http://php.net/spl_autoload_call
 * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
 */
#ini_set('unserialize_callback_func', 'spl_autoload_call');

// -- Configuration and initialization -----------------------------------------

/**
 * Set the default language
 */
I18n::lang('de-de');

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */
$script_base_url_array = explode("index.php", $_SERVER['SCRIPT_NAME']);
$script_base = $script_base_url_array[0];
if(isset($_SERVER['HTTP_HOST'])) {
	Cookie::$salt = 'psalt' . md5($_SERVER['HTTP_HOST']);
}

Kohana::init(array(
	'base_url'   => 'http://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . $script_base,
	'index_file' => '',
	'errors' => false,
));

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	// 'auth'       => MODPATH.'auth',       // Basic authentication
	// 'cache'      => MODPATH.'cache',      // Caching with multiple backends
	// 'codebench'  => MODPATH.'codebench',  // Benchmarking tool
	// 'database'   => MODPATH.'database',   // Database access
	// 'image'      => MODPATH.'image',      // Image manipulation
	// 'orm'        => MODPATH.'orm',        // Object Relationship Mapping
	// 'unittest'   => MODPATH.'unittest',   // Unit testing
	// 'userguide'  => MODPATH.'userguide',  // User guide and API documentation
	'doctrine2'     => MODPATH.'doctrine2',
	'smarty'        => MODPATH.'smarty',
	'jsmin'         => MODPATH.'jsmin',
	'cssmin'        => MODPATH.'cssmin',
	'yaml'          => MODPATH.'yaml',
));

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */

$environment = Kohana::$config->load('project.environment');
Kohana::$environment = $environment;

if($environment == Kohana::PRODUCTION) {
    Kohana::$errors = true;
}

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
call_user_func_array('setlocale', Kohana::$config->load('project.set_locale'));

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('home', '')
->defaults(array(
	'controller' => 'welcome',
	'action' => 'index',
	));

Route::set('activation', 'activation/<hash>', array('hash' => '\w{8}'))
->defaults(array(
	'controller' => 'user',
	'action' => 'activation',
	));

Route::set('removeaccconfirm', 'removeaccconfirm/<hash>', array('hash' => '\w{8}'))
->defaults(array(
	'controller' => 'user',
	'action' => 'removeaccconfirm',
	));

Route::set('contact', 'contact')
->defaults(array(
	'controller' => 'contact',
	'action' => 'index',
	));

Route::set('imprint', 'imprint')
->defaults(array(
	'controller' => 'imprint',
	'action' => 'index',
	));

Route::set('privacy', 'privacy')
->defaults(array(
	'controller' => 'privacy',
	'action' => 'index',
	));

Route::set('news', 'news')
->defaults(array(
	'controller' => 'news',
	'action' => 'index',
	));

Route::set('experts', 'experts')
->defaults(array(
	'controller' => 'experts',
	'action' => 'index',
	));

Route::set('login', 'login')
->defaults(array(
	'controller' => 'user',
	'action' => 'login',
	));

Route::set('logout', 'logout')
->defaults(array(
	'controller' => 'user',
	'action' => 'logout',
	));

Route::set('question', 'question')
->defaults(array(
	'controller' => 'planning',
	'action' => 'askquestion',
	));

Route::set('register', 'register')
->defaults(array(
	'controller' => 'user',
	'action' => 'register',
	));

Route::set('lostpassword', 'lostpassword')
->defaults(array(
	'controller' => 'user',
	'action' => 'lostpassword',
	));

Route::set('registered', 'registered')
->defaults(array(
	'controller' => 'user',
	'action' => 'registered',
	));

Route::set('media', 'media/<id>-<filename>', array('id' => '\d+', 'filename' => '.+'))
->defaults(array(
	'controller' => 'article',
	'action' => 'media',
	));

Route::set('previewimage', 'media/preview/<size>/<id>-<filename>', array('id' => '\d+', 'filename' => '.+', 'size' => '\d*x\d*'))
->defaults(array(
	'controller' => 'article',
	'action' => 'previewimage',
	));

Route::set('backend', 'backend')
->defaults(array(
	'directory'  => 'backend',
	'controller' => 'articles',
	'action' => 'index',
	));

Route::set('backend-texts', 'backend/texts')
->defaults(array(
	'directory'  => 'backend',
	'controller' => 'base',
	'action'     => 'texts',
	));

Route::set('backend-default', 'backend/(<controller>(/<action>(/<id>)))')
->defaults(array(
	'directory'  => 'backend',
	'controller' => 'articles',
	'action'     => 'index',
	));

Route::set('error', 'error/<action>/<origuri>/<message>', array('action' => '[0-9]++', 'origuri' => '.+', 'message' => '.+'))
->defaults(array(
	'controller' => 'error',
	'action'     => 'index'
	));

/*
 * Default route
 */
Route::set('default', '(<controller>(/<action>(/<id>(/<param>))))')
->defaults(array(
	'controller' => 'welcome',
	'action'     => 'index',
	));