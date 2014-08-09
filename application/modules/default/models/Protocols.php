<?php

class Protocols extends Zend_Db_Table_Abstract {

	protected $_name = 'protocols';
	protected $_primary = 'id';
	protected $_sequence = true;

	protected $_dependentTables = array('Mice', 'Litters', 'Cages');

	public function delete($where)
    {
    	$toBeDeleted = $this->fetchAll($where);
    	foreach ($toBeDeleted as $row) {
    		if ($row->findMice()->count()) {
    			throw new Zend_Db_Table_Exception('unable to delete protocol because there are related mice');
    			return 0;
    		}
    		if ($row->findLitters()->count()) {
    			throw new Zend_Db_Table_Exception('unable to delete protocol because there are related litters');
    			return 0;
    		}
    		if ($row->findCages()->count()) {
    			throw new Zend_Db_Table_Exception('unable to delete cages because there are related cages');
    			return 0;
    		}
    	}

		// delete comments and tags
		$db = $this->getAdapter();
		$comments = new Comments();
		$tags = new Tags();
		foreach ($toBeDeleted as $row) {
			$comments->delete('ref_table = '.$db->quote(Comments::PROTOCOL).' and ref_item_id = ' . $row->id);
			$tags->delete('ref_table = '.$db->quote(Tags::PROTOCOL).' and ref_item_id = ' . $row->id);
		}

		return parent::delete($where);
    }
}