<?php

require_once 'Zend/Controller/Plugin/Abstract.php';
require_once 'Zend/Controller/Front.php';
require_once 'Zend/Controller/Request/Abstract.php';
require_once 'Zend/Controller/Action/HelperBroker.php';

class mdb_Initializer extends Zend_Controller_Plugin_Abstract
{
	protected $_env;

	protected $_front;

	protected $_root;

	public function __construct($env = null, $root = null)
	{

		$this->_setEnv($env);
		if (null === $root) {
			$root = realpath(dirname(__FILE__) . '/../../');
		}
		$this->_root = $root;

		if (! mdb_Globals::getConfig() && is_file($this->_root.'/application/config/config.ini') ) {
			mdb_Globals::setConfig(new Zend_Config_Ini ( $this->_root.'/application/config/config.ini', 'general' ));
		}

		$this->_front = Zend_Controller_Front::getInstance();
		if ( is_null($env) && mdb_Globals::getConfig()) {
			$env = 	mdb_Globals::getConfig()->system->environment;
		    $this->_setEnv($env);
		}
		// set the test environment parameters
		if ($env == 'development') {
			// Enable all errors so we'll know when something goes wrong.
			error_reporting(E_ALL | E_STRICT);
			ini_set('display_startup_errors', 1);
			ini_set('display_errors', 1);

			$this->_front->throwExceptions(true);
		}
	}

	protected function _setEnv($env)
	{
		$this->_env = $env;
	}


	public function initDefaults()
	{
		foreach (mdb_Defaults::getDefaults() as $key => $data) {
			Zend_Registry::set($key, $data);
		}
		$config = mdb_Globals::getConfig();
		if ($config) {
			$this->recurseConfig($config);
		}

		if (Zend_Registry::isRegistered('system.timezone')) {
			date_default_timezone_set(Zend_Registry::get('system.timezone'));
		}
	}

	protected function recurseConfig(Zend_Config $config, $prefix = null) {

		foreach ($config as $key => $data) {
			if ($prefix) {
				$key = $prefix.'.'.$key;
			}
			if ( $data instanceof Zend_Config ) {
				$this->recurseConfig($data, $key);
			} else {
				Zend_Registry::set($key, $data);
			}
		}
	}

	public function routeStartup(Zend_Controller_Request_Abstract $request)
	{
		$this->initDefaults();
	    $this->initDb();
		$this->initHelpers();
		$this->initView();
		$this->initPlugins();
		$this->initRoutes();
		$this->initControllers();

		// this line to avoid Eclipse flagging this function for unused variable
		if (true || $request) {return;};
	}

	public function initDb()
	{
		$config = mdb_Globals::getConfig();
		try {
			$db = Zend_Db::factory ( $config->db );
			Zend_Db_Table::setDefaultAdapter ( $db );
			$db->query ( "SET NAMES 'utf8'" );
		} catch (Exception $e) {
			Zend_Registry::set('db_error', $e->getMessage());
			mdb_Log::Write('unable to connect to database: '.$e->__toString());
		}

		// First, set up the Cache
        $frontendOptions = array('automatic_serialization' => true);

        $backendOptions = array('cache_dir' => '/tmp/');

        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        // Next, set the cache to be used with all table objects
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

        if ($this->_env == 'development' && isset($db)) {
            // create a new profiler
            $profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
            $profiler->setEnabled(true);
            $db->setProfiler($profiler);

            // create logger
            $logger = new Zend_Log();
            $firebug_writer = new Zend_Log_Writer_Firebug();
            $logger->addWriter($firebug_writer);
		}
	}

	public function initHelpers()
	{
		// register the default action helpers
		Zend_Controller_Action_HelperBroker::addPath('../application/modules/default/helpers', 'Zend_Controller_Action_Helper');
	}

	public function initView()
	{
		// Bootstrap layouts
		Zend_Layout::startMvc(array(
			'layoutPath' => $this->_root .  '/application/modules/default/layouts',
			'layout' => 'main'
		));

	}

	public function initPlugins()
	{
		$this->_front->registerPlugin(new mdb_Controller_Plugin_Auth());
	}

	public function initRoutes()
	{

	}

	public function initControllers()
	{
		$this->_front->addModuleDirectory ( $this->_root . '/application/modules');
	}
}
