<?php

class UserPrefs extends Zend_Db_Table_Abstract {

	protected $_name = 'global_prefs';

	protected $_primary = 'preference';
	protected $_sequence = false;
}