<?php

class mdb_Globals
{

	const VERSION = '1.0';
	const VERSION_MAJOR = '1';
	const VERSION_MINOR = '0';
	const VERSION_RELEASE = '0';

	// hardcoded system defaults are in mdb_Defaults class

	protected static $_config;
	protected static $_userPrefs;
	protected static $_defaultUserPrefs;

	static function getConfig() {
		return self::$_config;
	}

	static function setConfig(Zend_Config $config) {
		self::$_config = $config;
		return self::$_config;
	}
	static function getConfigIndex($index, $default = null) {
		if (is_array($index)) {
			$exploded = $index;
		} else {
			$exploded = explode('.', $index);
		}
		if ($exploded) {
			$config = self::$_config;
			$lastItem = array_pop($exploded);
			foreach ($exploded as $item) {
				$config = $config->$item;
				if (! ($config instanceof Zend_Config)) {
					return $default;
				}
			}
			return $config->get($lastItem, $default);
		}
		return $default;
	}

	static function formatDateTime ($datetime) {

		if (is_null($datetime)) {
			return '';
		}
		if (is_string($datetime)) {
			$datetime = strtotime($datetime);
		}
		$datepart = date('d-M-y', $datetime);
		$timepart = date('g:i a', $datetime);
		if ($timepart == '12:00 am') {
			$timepart = '';
		} else {
			$timepart = ' ' . $timepart;
		}
		return $datepart . $timepart;
	}

	static function stripslashes ($data) {

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = self::stripslashes($value);
			}
			return $data;
		} elseif (is_string($data)) {
			return stripslashes($data);
		} else {
			return $data;
		}
	}

	private static function checkIP ($ip) {

		if (! empty($ip) && ip2long($ip) != - 1 && ip2long($ip) != false) {
			$private_ips = array(array('0.0.0.0' , '2.255.255.255') , array('10.0.0.0' , '10.255.255.255') , array('127.0.0.0' , '127.255.255.255') , array('169.254.0.0' , '169.254.255.255') , array('172.16.0.0' , '172.31.255.255') , array('192.0.2.0' , '192.0.2.255') , array('192.168.0.0' , '192.168.255.255') , array('255.255.255.0' , '255.255.255.255'));
			foreach ($private_ips as $r) {
				$min = ip2long($r[0]);
				$max = ip2long($r[1]);
				if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max))
					return false;
			}
			return true;
		} else {
			return false;
		}
	}

	static function getClientIP () {

		if (array_key_exists("HTTP_CLIENT_IP", $_SERVER) && checkIP($_SERVER["HTTP_CLIENT_IP"])) {
			return $_SERVER["HTTP_CLIENT_IP"];
		}
		if (array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)) {
			foreach (explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
				if (self::checkIP(trim($ip))) {
					return $ip;
				}
			}
		}
		if (array_key_exists("HTTP_X_FORWARDED", $_SERVER) && self::checkIP($_SERVER["HTTP_X_FORWARDED"])) {
			return $_SERVER["HTTP_X_FORWARDED"];
		} elseif (array_key_exists("HTTP_X_CLUSTER_CLIENT_IP", $_SERVER) && self::checkIP($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) {
			return  $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
		} elseif (array_key_exists("HTTP_FORWARDED_FOR", $_SERVER) && self::checkIP($_SERVER["HTTP_FORWARDED_FOR"])) {
			return  $_SERVER["HTTP_FORWARDED_FOR"];
		} elseif (array_key_exists("HTTP_FORWARDED", $_SERVER) && self::checkIP($_SERVER["HTTP_FORWARDED"])) {
			return  $_SERVER["HTTP_FORWARDED"];
		} else {
			return  $_SERVER["REMOTE_ADDR"];
		}
	}

	static function getUserPrefs() {

		if (! isset(self::$_userPrefs)) {
			$userPrefs = self::getDefaultUserPrefs();
			if (Zend_Auth::getInstance()->hasIdentity()) {
				// override with this user's preferences
				$db = Zend_Db_Table::getDefaultAdapter();
				$prefs = array();
				if ($db) {
					try {
						$prefs = $db->fetchAssoc('select preference, value from user_prefs where user_id = ?', Zend_Auth::getInstance()->getIdentity()->id);
					} catch (Zend_Db_Adapter_Exception $e) {
						mdb_Log::Write('unable to get user preferences: '.$e->__toString());
					}
				}
				foreach ($prefs as $pref) {
					$userPrefs[$pref['preference']] = $pref['value'];
				}
			}
			self::$_userPrefs = $userPrefs;
		}

		return self::$_userPrefs;
	}

	static function getUserPref($preference, $default = null) {
		if (array_key_exists($preference, self::getUserPrefs())) {
			return self::$_userPrefs[$preference];
		} else {
			return $default;
		}
	}

	static function getDefaultUserPrefs() {

		if (! isset(self::$_defaultUserPrefs)) {
			$userPrefs = mdb_Defaults::getUserPrefs();
			// override with default user preferences from database
			$db = Zend_Db_Table::getDefaultAdapter();
			$prefs = array();
			if ($db) {
				try {
					$prefs = $db->fetchAssoc('select preference, value from default_user_prefs');
				} catch (Zend_Db_Adapter_Exception $e) {
					mdb_Log::Write('unable to get default user preferences: '.$e->__toString());
				}
			}
			foreach ($prefs as $pref) {
				$userPrefs[$pref['preference']] = $pref['value'];
			}
			self::$_defaultUserPrefs = $userPrefs;
		}

		return self::$_defaultUserPrefs;
	}

	static function getDefaultUserPref($preference, $default = null) {
		if (array_key_exists($preference, self::getDefaultUserPrefs())) {
			return self::$_defaultUserPrefs[$preference];
		} else {
			return $default;
		}
	}

	static function getUserId() {
		$user_id = null;
		if (Zend_Auth::getInstance ()->hasIdentity ()) {
			$user_id = Zend_Auth::getInstance ()->getIdentity ()->id;
		}
		return $user_id;
	}

	static function getRoleId() {
		$role_id = null;
		if (Zend_Auth::getInstance ()->hasIdentity ()) {
			$role_id = Zend_Auth::getInstance ()->getIdentity ()->role_id;
		}
		return $role_id;
	}
}
