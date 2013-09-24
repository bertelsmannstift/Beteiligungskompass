<?php

class SmartyView extends View {
	private static $_smarty;

	public static function instance() {
		if(!self::$_smarty) {
			$config = Kohana::$config->load('smarty');
		  // include smarty
		  try {
		  	require_once (Kohana::find_file($config->get('lib'), $config->get('smarty_class_file')));
		  } catch (Exception $e) {
		  	throw new Kohana_Exception('Could not load Smarty class file');
		  }

		  // New Smarty instance
		  $smarty = new Smarty;

		  // Search for template and plugin dirs
		  $tpl_dirs = array();
		  $plugin_dirs = array();

		  if(file_exists(APPPATH . 'smarty_plugins')) $plugin_dirs[] = APPPATH . 'smarty_plugins';
		  if(file_exists(APPPATH . 'views')) $tpl_dirs[] = APPPATH . 'views';

		  foreach (Kohana::modules() as $module) {
		  	$plugin_path = $module . 'smarty_plugins' . DIRECTORY_SEPARATOR;
		  	if (file_exists($plugin_path)) {
		  		$plugin_dirs[] = $plugin_path;
		  	}

		  	$template_path = $module . 'views' . DIRECTORY_SEPARATOR;
		  	if (file_exists($template_path)) {
		  		$tpl_dirs[] = $template_path;
		  	}
		  }

		  $options = $config->get('smarty_config');

		  // Set template and plugin dirs
		  $smarty->setTemplateDir(array_merge($tpl_dirs, $options['template_dir']));
		  $smarty->plugins_dir = array_merge($plugin_dirs, (array) $options['plugin_dir']);

		  unset($options['template_dir'], $options['plugin_dir']);

		  // Set config form configfile and options
		  foreach ( $options as $key => $value ) {
		  	$smarty->$key = $value;
		  }

		  // Check if folders are writeable
		  if ( !is_writeable($smarty->compile_dir) ) throw new Kohana_Exception('Smarty compile_dir is not writable');
		  if ( !is_writeable($smarty->cache_dir) ) throw new Kohana_Exception('Smarty cache_dir is not writable');

      self::$_smarty = $smarty;
		}

		self::$_smarty->clearAllAssign();

		return self::$_smarty;
	}

	public static function factory($file = NULL, array $data = NULL) {
		return new SmartyView($file, $data);
	}

	public function set_filename($file) {
		if (($path = Kohana::find_file('views', $file, Kohana::$config->load('smarty')->get('tpl_extension'))) === FALSE) {
			throw new View_Exception('The requested view :file could not be found', array(
				':file' => $file,
			));
		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

	public function render($file = NULL)
	{
		if ($file !== NULL) {
			$this->set_filename($file);
		}

		if (empty($this->_file)) {
			throw new View_Exception('You must set the file to use within your view before rendering');
		}

		$smarty = self::instance();

		array_walk($this->_data, function($value, $key, &$smarty) {
			$smarty->assign($key, $value);
		}, $smarty);

		return $smarty->fetch($this->_file);
	}
}