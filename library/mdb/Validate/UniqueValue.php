<?php

require_once 'Zend/Validate/Abstract.php';

class mdb_Validate_UniqueValue extends Zend_Validate_Abstract {

	const EXISTS = 'exists';
	const DB_ERR = 'db_err';

	protected $_table;
	protected $_id;
	protected $_id_col;
	protected $_value_col;
	protected $_where;

	protected $_messageVariables = array (
		'table' => '_table',
		'id' => '_id',
		'id_col' => '_id_col',
		'value_col' => '_value_col',
		'where' => '_where',
	);

	protected $_messageTemplates = array (
		self::EXISTS => "'%value%' is already used",
		self::DB_ERR => "database error while validating value" );

	public function __construct($table, $id, $id_col = 'id', $value_col) {
		$this->table = $table;
		$this->value_col = $value_col;
		$this->id_col = $id_col;
		$this->id = $id;
	}

	public function isValid($value) {

		$this->_setValue ( $value );

		try {
			$db = Zend_Db_Table::getDefaultAdapter ();

			if (is_null($this->id)) {
				$id_where = $db->quoteIdentifier($this->id_col).' is not null ';
			} else {
				$id_where = $db->quoteIdentifier($this->id_col).' != '.$db->quote($this->id);
			}
			if ( $this->where ) {
				$id_where.= ' and '.$this->where;
			}

			if ($db->fetchOne('select count(*) from '.$db->quoteIdentifier($this->table).' where '.$id_where.' and '.$db->quoteIdentifier($this->value_col).' = '.$db->quote($value))) {
				$this->_error ( self::EXISTS );
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