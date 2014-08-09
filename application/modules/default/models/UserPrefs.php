<?php

class UserPrefs extends Zend_Db_Table_Abstract {

	protected $_name = 'user_prefs';

	protected $_primary = array('user_id', 'preference');
	protected $_sequence = false;

	protected $_referenceMap = array(
    	'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		),
	);
}