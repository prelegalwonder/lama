<?php

class Litters extends Zend_Db_Table_Abstract {

	protected $_name = 'litters';

	protected $_primary = 'id';
	protected $_sequence = true;

	protected $_dependentTables = array('Mice', 'WeaningCages');

	protected $_referenceMap = array(
		'Strain' => array(
			'columns'		=> 'strain_id',
			'refTableClass'	=> 'Strains',
			'onUpdate'		=> self::CASCADE,
		),
		'BreedingCage' => array(
			'columns'		=> 'breeding_cage_id',
			'refTableClass'	=> 'BreedingCages',
			'onUpdate'		=> self::CASCADE,
		),
		'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		),
		'Protocol' => array(
			'columns'		=> 'protocol_id',
			'refTableClass'	=> 'Protocols',
			'onUpdate'		=> self::CASCADE,
		),
		'Father' => array(
			'columns'		=> 'father_id',
			'refTableClass'	=> 'Mice',
			'onUpdate'		=> self::CASCADE,
		),
		'Mother' => array(
			'columns'		=> 'mother_id',
			'refTableClass'	=> 'Mice',
			'onUpdate'		=> self::CASCADE,
		),
		'Mother2' => array(
			'columns'		=> 'mother2_id',
			'refTableClass'	=> 'Mice',
			'onUpdate'		=> self::CASCADE,
		),
		'Mother3' => array(
			'columns'		=> 'mother3_id',
			'refTableClass'	=> 'Mice',
			'onUpdate'		=> self::CASCADE,
		)
	);

	public function delete($where) {

		$toBeDeleted = $this->fetchAll($where);
		foreach ($toBeDeleted as $row) {
			// raise exception if this litter has been weaned
			if ($row->weaned_on) {
				throw new Zend_Db_Table_Exception('Unable to delete litter ' . $row->assigned_id . ' because it was already weaned. Try unweaning first.');
				return 0;
			}

    		// raise exception if there are mice belonging to this litter
			if ($row->findMice()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete litter ' . $row->assigned_id . ' when there are mice in this litter');
				return 0;
			}

    		// raise exception if there are weaning cages belonging to this litter
			if ($row->findWeaningCages()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete litter ' . $row->assigned_id . ' when there are weaning cages associated with this litter. Try unweaning first.');
				return 0;
			}
		}

		// delete comments and tags
		$db = $this->getAdapter();
		$comments = new Comments();
		$tags = new Tags();
		foreach ($toBeDeleted as $row) {
			$comments->delete('ref_table = '.$db->quote(Comments::LITTER).' and ref_item_id = ' . $row->id);
			$tags->delete('ref_table = '.$db->quote(Tags::LITTER).' and ref_item_id = ' . $row->id);
		}

		return parent::delete($where);
    }

}