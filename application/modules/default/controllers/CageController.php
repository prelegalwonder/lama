<?php
require_once 'SearchController.php';

class CageController extends mdb_Controller {

    const ACL_RESOURCE = 'default_cage';

    protected $_json = array ('list');

	public function viewAction() {

		$db = Zend_Db_Table::getDefaultAdapter();

		$id = mdb_Globals::stripslashes($this->_request->getParam('id'));
		$assigned_id = mdb_Globals::stripslashes($this->_request->getParam('cage'));

		if ($id) {
			$where = 'id = '.$db->quote($id);
		} elseif ($assigned_id) {
			$where = 'assigned_id = ' . $db->quote($assigned_id);
		} else {
			mdb_Messages::add ( 'there is no such cage' );
			$this->_redirect('/');
		}
		$cages = new Cages ( );
		$cageRow = $cages->fetchRow($where);
		// does this cage exist?
		if ($cageRow == null) {
			mdb_Messages::add ( 'there is no such cage', 'error' );
			$this->_redirect ( '/' );
			return;
		}
		switch ($cageRow->cagetype) {
			case Cages::BREEDING:
				$this->_redirect('/breeding-cage/view/id/'.$cageRow->id);
			case Cages::WEANING:
				$this->_redirect('/weaning-cage/view/id/'.$cageRow->id);
			case Cages::HOLDING:
				$this->_redirect('/holding-cage/view/id/'.$cageRow->id);
			default:
				mdb_Messages::add ( 'unknown cage type: '.$cageRow->cagetype );
				$this->_redirect('/');

		}
	}

	public function listAction() {
		$db = Zend_Db_Table::getDefaultAdapter();

		$empty = $this->_request->getParam('empty', 'no');
		$cagetype = $this->_request->getParam('type', Cages::BREEDING or Cages::HOLDING);
		//$cagetype = $this->_request->getParam('type', Cages::HOLDING);
		$start = $this->_request->getParam('start', null);
		$count = $this->_request->getParam('count', 'Infinity');
		if ($count == 'Infinity') {
			$count = null;
		}
		$mouse_id = $this->_request->getParam('mouse', null);
		//$max_age = (int) $this->_request->getParam('maxage', '180');
		$exclude_cage_id = $this->_request->getParam('exclude', null);

		$id = $this->_request->getParam('id', null);
		if (is_null($id)) {
			$item = $this->_request->getParam('item', '%');
			if (substr($item, -1) == '*') {
				$item[strlen($item)-1]  = '%';
			}
			$where = 'cages.assigned_id like '.$db->quote($item);
		} else {
			$item = null;
			$where = 'cages.id = '.$db->quote($id);
		}
		if ($exclude_cage_id) {
			$where .= ' and cages.id != '.$db->quote($exclude_cage_id);
		}

		$union = array();
		if ($cagetype == 'all' || strpos($cagetype, Cages::BREEDING) !== false) {
			$union[] = $db->select()
				->from('cages',
					array('id', 'assigned_id as item' ))
				->joinInner('breeding_cages', 'cages.id = breeding_cages.id', '')
				->where($where);
		}

		if ($cagetype == 'all' || strpos($cagetype, Cages::HOLDING) !== false) {
			$union[] = $db->select()
				->from('cages',
					array('id', 'assigned_id as item' ))
				->joinInner('holding_cages', 'cages.id = holding_cages.id', '')
				->where($where);
		}

		if ($cagetype == 'all' || strpos($cagetype, Cages::WEANING) !== false) {
			$union[] = $db->select()
				->from('cages',
					array('id', 'assigned_id as item' ))
				->joinInner('weaning_cages', 'cages.id = weaning_cages.id', '')
				->where($where);
		}

		// weanback is a fake cage type used to indicate that weaning cages that a particular mouse might have come from should be listed
		if (strpos($cagetype, 'weanback') !== false) {
			$union[] = $db->select()
				->from('cages',
					array('id', 'assigned_id as item' ))
				->joinInner('weaning_cages', 'cages.id = weaning_cages.id', '')
				->joinInner('mice', 'mice.litter_id = weaning_cages.litter_id and mice.sex = weaning_cages.sex and mice.id ='.$db->quote($mouse_id), '')
				// ->where('('.$where.') and born_on > date_sub(current_date, interval '.$db->quote($max_age).' day)');
				->where($where);
		}

		if ($empty == 'yes' && (($id == '' && is_null($item)) || (is_null($id) && ($item == '' || ($item == '%' && (is_null($start) || $start == 0)))))) {
			$union[] = $db->select()
			->from(null,
				array('id' => new Zend_Db_Expr('null'), 'item' => new Zend_Db_Expr("''")));
		}

		$select = $db->select()->union($union);

		$select->order('item')
			->limit($count, $start);

		$this->view->identifier = 'id';
		$this->view->label = 'item';
		$this->view->items = $select->query()->fetchAll();
	}
}
