<?php

class Transfers extends Zend_Db_Table_Abstract {

	protected $_name = 'transfers';

	protected $_primary = 'id';
	protected $_sequence = true;

	protected $_referenceMap = array(
		'Mice' => array(
			'columns'		=> 'mouse_id',
			'refTableClass'	=> 'Mice',
			'onUpdate'		=> self::CASCADE,
		),
		'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		),
		'FromCage' => array(
			'columns'		=> 'from_cage_id',
			'refTableClass'	=> 'Cages',
			'onUpdate'		=> self::CASCADE,
		),
		'ToCage' => array(
			'columns'		=> 'to_cage_id',
			'refTableClass'	=> 'Cages',
			'onUpdate'		=> self::CASCADE,
		)
	);

	public function delete($where)
    {
    	$toBeDeleted = $this->fetchAll($where);

    	// delete comments and tags
		$db = $this->getAdapter();
		$comments = new Comments();
		$tags = new Tags();
		foreach ($toBeDeleted as $row) {
			$comments->delete('ref_table = '.$db->quote(Comments::TRANSFER).' and ref_item_id = ' . $row->id);
			$tags->delete('ref_table = '.$db->quote(Tags::TRANSFER).' and ref_item_id = ' . $row->id);
		}

		return parent::delete($where);
    }

}