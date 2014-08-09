<?php

require_once 'Zend/Validate/Abstract.php';

class mdb_Validate_ForeignKey extends Zend_Validate_Abstract {

	const NOT_EXISTS = 'not_exists';
	const DB_ERR = 'db_err';

	protected $_table;
	protected $_value;
	protected $_id_col;

	protected $_messageVariables = array (
		'table' => '_table',
		'value' => '_value',
		'id_col' => '_id_col');

	protected $_messageTemplates = array (
		self::NOT_EXISTS => "'%value%' does not exist in '%table%'",
		self::DB_ERR => "database error while validating value" );

	public function __construct($table, $id_col = 'id') {
		$this->table = $table;
		$this->id_col = $id_col;
	}

	public function isValid($value) {

		$this->_setValue ( $value );

		try {
			$db = Zend_Db_Table::getDefaultAdapter ();

			if (0 == $db->fetchOne('select count(*) from '.$db->quoteIdentifier($this->table).' where '.$db->quoteIdentifier($this->id_col).' = '.$db->quote($value))) {
				$this->_error ( self::NOT_EXISTS );
				return false;
			}
		} catch (Zend_Db_Exception $e) {
			$this->_messageTemplates[self::DB_ERR] = $e->getMessage();
			$this->_error ( self::DB_ERR );
			return false;
		}

		return true;
	}
}