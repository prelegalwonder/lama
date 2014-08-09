<?php

class HoldingCages extends Zend_Db_Table_Abstract {

	protected $_name = 'holding_cages';

	protected $_primary = 'id';

	protected $_referenceMap = array(
    	'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		),
		'Protocol' => array(
			'columns'		=> 'protocol_id',
			'refTableClass'	=> 'Protocols',
			'onUpdate'		=> self::CASCADE,
		)
	);

	public function delete($where) {

		$toBeDeleted = $this->fetchAll($where);
		foreach ($toBeDeleted as $row) {
			// raise exception if there are mice in this cage
			if ($row->findMice()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete cage when there are mice in this cage. Try deleting mice first.');
				return 0;
			}
		}

		// delete comments and tags
		$db = $this->getAdapter();
		$comments = new Comments();
		$tags = new Tags();
		$transfers = new Transfers();
		$cages = new Cages();
		foreach ($toBeDeleted as $row) {
			$comments->delete('ref_table = '.$db->quote(Comments::HOLDING_CAGE).' and ref_item_id = ' . $db->quote($row->id));
			$tags->delete('ref_table = '.$db->quote(Tags::HOLDING_CAGE).' and ref_item_id = ' . $db->quote($row->id));

			// delete transfer records where this is the only cage; set to null in otherwise
			$transfers->delete('(from_cage_id = '.$db->quote($row->id).' and to_cage_id is null) or (from_cage_id is null and to_cage_id = '.$db->quote($row->id).')');
			$transfers->update(array('from_cage_id' => null), 'from_cage_id = '.$db->quote($row->id));
			$transfers->update(array('to_cage_id' => null), 'to_cage_id = '.$db->quote($row->id));

		    $cages->delete('id = '.$row->id);
		}

		return parent::delete($where);
    }

}
