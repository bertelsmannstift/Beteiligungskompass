<?php
require __DIR__ . '/DoctrineExtensions/Query/Mysql/Field.php';
use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Configuration;

class Doctrine {

	/**
	 * EntityManager instance
	 */
	private static $em = false;
	private static $cache = false;

	/**
	 * Get EntityManager instance
	 */
	public static function instance() {
		if(!self::$em) {
			self::$em = self::init();
		}

		return self::$em;
	}


    public static function getCache() {
		if(!self::$cache) {
            self::init();
		}

		return self::$cache;
	}

	public static function init() {

		$configObject = Kohana::$config->load('doctrine');

        // if(function_exists('apc_cache_info')) {
       //     self::$cache = new \Doctrine\Common\Cache\ApcCache;
       // } else {
            self::$cache = new \Doctrine\Common\Cache\ArrayCache;
       // }

		self::$cache->setNamespace($configObject->get('cache_prefix'));

		$config = new Configuration;
		$config->setMetadataCacheImpl(self::$cache);
		$config->setQueryCacheImpl(self::$cache);
        $config->setResultCacheImpl(self::$cache);

		$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(APPPATH . 'classes/model'));

		$config->setProxyDir(Kohana::$cache_dir . '/doctrine/proxies');
		$config->setProxyNamespace('Proxies');

		if (Kohana::$environment == Kohana::DEVELOPMENT) {
		    $config->setAutoGenerateProxyClasses(true);
		} else {
		    $config->setAutoGenerateProxyClasses(false);
		}

		$configObject = Kohana::$config->load('doctrine');

		if($configObject->get('customStringFunctions')) {
			foreach($configObject->get('customStringFunctions') as $name => $class) {
				$config->addCustomStringFunction($name, $class);
			};
		}

		if($configObject->get('customNumericunctions')) {
			foreach($configObject->get('customNumericunctions') as $name => $class) {
				$config->addCustomNumericFunction($name, $class);
			};
		}

		if($configObject->get('customDatetimeFunctions')) {
			foreach($configObject->get('customDatetimeFunctions') as $name => $class) {
				$config->addCustomDatetimeFunction($name, $class);
			};
	  }

		$em = EntityManager::create($configObject->get('connection'), $config);
        //$em->getConnection()->setCharset('utf8');

		return $em;
	}
}
