<?php

class mdb_Controller extends Zend_Controller_Action {

	protected $_user_id = null;
	protected $_role_id = null;
	protected $_suggestFields = array();
	protected $_json = array();
	protected $_ajax = array();

	public function init() {
		$this->initView ();
		$this->view->baseUrl = $this->_request->getBaseUrl ();
		if (Zend_Auth::getInstance ()->hasIdentity ()) {
			$this->_user_id = Zend_Auth::getInstance ()->getIdentity ()->id;
			$this->_role_id = Zend_Auth::getInstance ()->getIdentity ()->role_id;
		}

		if (count($this->_json)) {
    		$contextSwitch = $this->_helper->getHelper( 'ContextSwitch' );
    		foreach ($this->_json as $function_name) {
    		    $contextSwitch->addActionContext( $function_name, 'json' );
    		}
    		$contextSwitch->initContext();
	    }
		if (count($this->_ajax)) {
    		$contextSwitch = $this->_helper->getHelper( 'AjaxContext' );
    		foreach ($this->_ajax as $function_name) {
    		    $contextSwitch->addActionContext( $function_name, 'html' );
    		}
    		$contextSwitch->initContext();
	    }

		parent::init ();
	}

	protected function listItems($table = null, $id_col = 'id', $item_col = 'assigned_id', $add_where = null) {

		$db = Zend_Db_Table::getDefaultAdapter();

		if (! $table) {
		    $table = $this->_table;
		}
		if (! $table) {
		    throw new Exception('table not specified');
		}

		$empty = $this->_request->getParam('empty', 'no');
		$start = $this->_request->getParam('start', null);
		$count = $this->_request->getParam('count', 'Infinity');
		if ($count == 'Infinity') {
			$count = null;
		}

		$id = $this->_request->getParam('id', null);
		if (is_null($id)) {
			$item = $this->_request->getParam('item', '%');
			if (substr($item, -1) == '*') {
				$item[strlen($item)-1]  = '%';
			}
			$where_focus = $db->quoteIdentifier($item_col);
			$where = $where_focus.' like '.$db->quote($item);
			if ($empty == 'never') {
					$where .= ' and not '.$where_focus.' is null and '.$where_focus. ' != \'\'';
			}
		} else {
			$item = null;
			$where = $db->quoteIdentifier($id_col).' = '.$db->quote($id);
		}
		if ($add_where) {
		    $where .= ' and '.$add_where;
		}

		$select = $db->select()
			->from($table,
				array($id_col.' as id', $item_col.' as item' ))
			->where($where);

		if ($empty == 'yes' && (($id == '' && is_null($item)) || (is_null($id) && ($item == '' || ($item == '%' && (is_null($start) || $start == 0)))))) {
			$selectEmpty = $db->select()
			->from(null,
				array('id' => new Zend_Db_Expr('null'), 'item' => new Zend_Db_Expr("''")));
			$select = $db->select()->union(array($select, $selectEmpty));
		}

		$select->order('item')
		    ->distinct(true)
			->limit($count, $start);

		$this->view->identifier = 'id';
		$this->view->label = 'item';
		$this->view->items = $select->query()->fetchAll();
	}

	public function suggestAction() {

		$field = mdb_Globals::stripslashes ( $this->_request->getParam ( 'field' ) );

		if (in_array($field, $this->_suggestFields, true)) {
			$this->listItems($this->_table, $field, $field);
		} else {
		    throw new Exception('Unable to suggest anything for '.$field);
		}
	}
}
