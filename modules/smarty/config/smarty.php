<?php
	return array(
		'lib' => 'vendor/Smarty-3.1.7/libs',
		'smarty_class_file' => 'Smarty.class',
		'tpl_extension' => 'html',
		'smarty_config' => array(
			// we can use Kohana's cache directory for our compiled templates
			'compile_dir' => Kohana::$cache_dir . '/smarty/compile',

			// ... and the smarty cache (only used if it is enabled)
			'cache_dir' => Kohana::$cache_dir . '/smarty/cache',

			// Set additional smarty template dirs as array. *views will be added automatically
			'template_dir' => array(),

			// Set additional smarty plugin dirs as array. *smarty_plugins will be added automatically
			'plugin_dir' => array(realpath(__DIR__ . '/../vendor/Smarty-3.1.7/libs/plugins')), 

			// If you want to use smarty config files, put them in this place
			//'config_dir' => APPPATH.'smarty_config',

			// useful when developing, override to false in your application's config
			// for a small speed increase
			'compile_check' => true,
			'caching' => false,
			'debugging' => Kohana::$config->load('project.smarty_force_compile'),
			'force_compile' => Kohana::$config->load('project.smarty_force_compile'),
			'error_reporting' => E_ALL & ~E_NOTICE,
		)
	);