<?php

class BreedingCages extends Zend_Db_Table_Abstract {

	const BREEDING_BREEDER = 'Breeder';
	const BREEDING_STUD = 'Stud';
	const BREEDING_MALE = 'Male Rotation';
	const BREEDING_FEMALE = 'Female Rotation';

	const MATING_PAIR = 'Pair';
	const MATING_NON_PEDIGREE = 'Non-Pedigree Pair';
	const MATING_PEDIGREE = 'Pedigree Pair';
	const MATING_TRIO = 'Trio';
	const MATING_QUAD = 'Quad';

	protected $_name = 'breeding_cages';

	protected $_primary = 'id';

	protected $_referenceMap = array(
		'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		),
		'AssignedStud' => array(
			'columns'		=> 'assigned_stud_id',
			'refTableClass'	=> 'Mice',
			'onUpdate'		=> self::CASCADE,
		)

	);

	public function delete($where) {

		$toBeDeleted = $this->fetchAll($where);
		foreach ($toBeDeleted as $row) {
			// raise exception if there are mice in this cage
			if ($row->findMice()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete cage when there are mice in this cage. Try deleting or transferring mice first.');
				return 0;
			}

    		// raise exception if there are litters belonging to this cage
			if ($row->findLitters()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete breeding cage when there are litters originating in this cage. Try deleting litters first.');
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
			$comments->delete('ref_table = '.$db->quote(Comments::BREEDING_CAGE).' and ref_item_id = ' . $row->id);
			$tags->delete('ref_table = '.$db->quote(Tags::BREEDING_CAGE).' and ref_item_id = ' . $row->id);

			// delete transfer records where this is the only cage; set to null in otherwise
			$transfers->delete('(from_cage_id = '.$row->id.' and to_cage_id is null) or (from_cage_id is null and to_cage_id = '.$row->id.')');
			$transfers->update(array('from_cage_id' => null), 'from_cage_id = '.$row->id);
			$transfers->update(array('to_cage_id' => null), 'to_cage_id = '.$row->id);

			$cages->delete('id = '.$row->id);
		}

		return parent::delete($where);
    }

    public static function getBreedingTypes() {
    	return array(self::BREEDING_BREEDER, self::BREEDING_STUD, self::BREEDING_MALE, self::BREEDING_FEMALE);
    }

    public static function getMatingTypes() {
    	return array(self::MATING_PAIR, self::MATING_PEDIGREE, self::MATING_NON_PEDIGREE, self::MATING_TRIO, self::MATING_QUAD);
    }
}