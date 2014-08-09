<?php

class Mice extends Zend_Db_Table_Abstract {

	protected $_name = 'mice';

	protected $_primary = 'id';
	protected $_sequence = true;

	protected $_dependentTables = array('Transfers', 'BreedingCages', 'Litters');

	protected $_referenceMap = array(
		'Strain' => array(
			'columns'		=> 'strain_id',
			'refTableClass'	=> 'Strains',
			'onUpdate'		=> self::CASCADE,
		),
		'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		),
		'Litter' => array(
			'columns'		=> 'litter_id',
			'refTableClass'	=> 'Litters',
			'onUpdate'		=> self::CASCADE,
		),
		'Cage' => array(
			'columns'		=> 'cage_id',
			'refTableClass'	=> 'Cages',
			'onUpdate'		=> self::CASCADE,
		),
		'BreedingCage' => array(
			'columns'		=> 'cage_id',
			'refTableClass'	=> 'BreedingCages',
			'onUpdate'		=> self::CASCADE,
		),
		'WeaningCage' => array(
			'columns'		=> 'cage_id',
			'refTableClass'	=> 'WeaningCages',
			'onUpdate'		=> self::CASCADE,
		),
		'Protocol' => array(
			'columns'		=> 'protocol_id',
			'refTableClass'	=> 'Protocols',
			'onUpdate'		=> self::CASCADE,
		)
	);

	public function delete($where, $deleteMiceFromLitters = false) {

		$toBeDeleted = $this->fetchAll($where);
		foreach ($toBeDeleted as $row) {

    		// raise exception if this mouse was weaned from a litter
			if ( ! $deleteMiceFromLitters && $row->litter_id ) {
				throw new Zend_Db_Table_Exception('Unable to delete mouse ' . $row->assigned_id . ' because it was weaned from a litter. You could re-wean the litter instead.');
				return 0;
			}

			// raise exception if there are litters by this mouse
			if ($row->findLittersByFather()->count() || $row->findLittersByMother()->count() || $row->findLittersByMother2()->count() || $row->findLittersByMother3()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete mouse ' . $row->assigned_id . ' because it has litters. You must delete all litters first.');
				return 0;
			}
		}

		$db = $this->getAdapter();

		$comments = new Comments();
		$tags = new Tags();
		$transfers = new Transfers();
		$breedingCages = new BreedingCages();

		foreach ($toBeDeleted as $row) {
			$comments->delete('ref_table = '.$db->quote(Comments::MOUSE).' and ref_item_id = ' . $db->quote($row->id));
			$tags->delete('ref_table = '.$db->quote(Tags::MOUSE).' and ref_item_id = ' . $db->quote($row->id));
			$transfers->delete('mouse_id = ' . $db->quote($row->id));
			if ($row->sex == 'M') {
				$breedingCages->update(array('assigned_stud_id' => null), 'assigned_stud_id = ' . $db->quote($row->id));
			}
		}

		return parent::delete($where);
	}

	public function sacrifice($where, $status = 'sacrificed') {
		return $this->update(array('is_alive' => false, 'status' => $status, 'terminated_on' => date('Y-m-d')), $where);
	}

	public function revive($where, $status = null) {

		// a mouse can be revived only if it follows rules for transferring a mouse to breeding cage
		$revived = 0;

		$err_msg = null;
		foreach ($this->fetchAll($where) as $row) {
			if (is_null($row->cage_id) || $this->canTransfer($row->sex, $row->cage_id, $err_msg)) {
				// $this->update(array('is_alive' => true, 'status' => $status, 'terminated_on' => null), 'id = '.$row->id);
				$row->is_alive = true;
				$row->status = $status;
				$row->terminated_on = null;
				$row->save();
				if ($row->sex == 'M') {
					$this->getAdapter()->update('breeding_cages', array('assigned_stud_id' => $row->id), 'id = '.$row->cage_id);
				}
				$revived++;
			} else {
				throw new Zend_Db_Table_Exception('Unable to revive mouse ' . $row->assigned_id . ': '.$err_msg);
				return 0;
			}
		}

		return $revived;
	}

	protected function canTransfer($sex, $to_cage_id, &$err_msg = null) {

	    if (is_null($to_cage_id)) {
	        return true;
	    }

		$cages = new Cages();
		$cageRows = $cages->find ( $to_cage_id);
		if ($cageRows->count () == 0) {
			$err_msg = 'requested cage does not exist';
			return false;
		}
		$cageRow = $cageRows->current ();
		$db = $this->getAdapter();

		switch ($cageRow->cagetype) {
			case Cages::BREEDING:
				$sexCount = $db->fetchOne ( 'select count(*) from mice where is_alive and cage_id = '.$db->quote($to_cage_id).' and sex = '.$db->quote($sex));
				$breedingCages = new BreedingCages();
				$breedingCageRow = $breedingCages->find($to_cage_id)->current();

				if ($breedingCageRow === null) {
					throw new Exception('unable to transfer: this breeding cage does not seem to exist.');
				}
				if ($sex == 'M') {
					$maxSexCount = 1;
				} else {
					switch ( $breedingCageRow->mating_type ) {
						case 'Trio':
							$maxSexCount = 2;
							break;
						case 'Quad':
							$maxSexCount = 3;
							break;
						default:
							// assume it is pair
							$maxSexCount = 1;
							break;
					}
				}
				if ($sexCount >= $maxSexCount) {
					$err_msg = 'limit of '.$maxSexCount.' already reached for this sex';
					// next line to avoid Eclipse flagging above line as warning
					if (! $err_msg) { $err_msg = $err_msg; }
					return false;
				}
			case Cages::WEANING:
			default:
		}
		return true;
	}

	public function transfer($where, $to_cage_id, $notes = null, $user_id = null) {

		$transferred = 0;
		$db = $this->getAdapter();

		$err_msg = null;
		foreach ($this->fetchAll($where) as $row) {
			if ($this->canTransfer($row->sex, $to_cage_id, $err_msg)) {
				$from_cage_id = $row->cage_id;
				$row->cage_id = $to_cage_id;
				$row->user_id = $user_id;
				$row->save();
				if ($row->sex == 'M') {
					$db->update('breeding_cages', array('assigned_stud_id' => $row->id), 'id = '.$row->cage_id);
					$db->update('cages', array('user_id' => $user_id), 'cagetype = '.$db->quote(Cages::BREEDING).' and id = '.$db->quote($row->cage_id));
				}

				// create transfer record
				if (! is_null($from_cage_id) || ! is_null($to_cage_id)) {
					$xfers = new Transfers();
					$newXfer = $xfers->createRow();
					$newXfer->mouse_id = $row->id;
					$newXfer->transferred_on = date('Y-m-d');
					$newXfer->from_cage_id = $from_cage_id;
					$newXfer->to_cage_id = $to_cage_id;
					$newXfer->user_id = $user_id;
					$newXfer->notes = $notes;
					$newXfer->save();
				}
				$transferred++;
			} else {
				throw new Zend_Db_Table_Exception('Unable to transfer mouse ' . $row->assigned_id . ': '.$err_msg);
				return 0;
			}
		}

		return $transferred;
	}

	public function setGenotype($where, $genotype) {
		return $this->update(array('genotype' => $genotype), $where);
	}

	public function setStatus($where, $status) {
		return $this->update(array('status' => $status), $where);
	}
}