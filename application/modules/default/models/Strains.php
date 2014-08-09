<?php

class Strains extends Zend_Db_Table_Abstract {

	protected $_name = 'strains';
	protected $_primary = 'id';
	protected $_sequence = true;

	protected $_dependentTables = array('Mice', 'Litters');

	protected $_referenceMap = array(
		'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		),
		'AssignedUser' => array(
			'columns'		=> 'assigned_user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		)
	);

    public function delete($where)
    {
    	$toBeDeleted = $this->fetchAll($where);
    	foreach ($toBeDeleted as $row) {
    		if ($row->findMice()->count()) {
    			throw new Zend_Db_Table_Exception('unable to delete strain while there are mice in this strain');
    			return 0;
    		}
    		if ($row->findLitters()->count()) {
    			throw new Zend_Db_Table_Exception('unable to delete strain while there are litters in this strain');
    			return 0;
    		}
    	}

		// delete comments and tags
		$db = $this->getAdapter();
		$comments = new Comments();
		$tags = new Tags();
		foreach ($toBeDeleted as $row) {
			$comments->delete('ref_table = '.$db->quote(Comments::STRAIN).' and ref_item_id = ' . $row->id);
			$tags->delete('ref_table = '.$db->quote(Tags::STRAIN).' and ref_item_id = ' . $row->id);
		}

		return parent::delete($where);
    }

}