<?php
class mdb_Log extends Zend_Log
{

	static $_instance;

	public function __construct(Zend_Log_Writer_Abstract $writer = null)
	{
		if (is_null($writer)) {
			// where do we write this message?
			if (Zend_Registry::isRegistered('system.logfile')) {
				$logfile = Zend_Registry::get('system.logfile');
				if ($logfile) {
					$writer = new Zend_Log_Writer_Stream($logfile);
				}
			}
		}
		parent::__construct($writer);
	}

	static function Write($message, $user_id = false, $severity = Zend_Log::ERR) {

		if ($user_id === false) {
			$user_id = mdb_Globals::getUserId();
		}
		try {
			if (! isset(self::$_instance)) {
				self::$_instance = new mdb_Log();
			}
			self::$_instance->log('(user id: '.$user_id.') '.$message, $severity);
		} catch (Exception $e) {
			if (Zend_Registry::isRegistered('system.logfile')) {
				mdb_Messages::add('Unable to log this error: '.$e->getMessage(), 'error');
			}
			return false;
		}
		return true;
	}
}