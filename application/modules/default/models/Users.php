<?php

class Users extends Zend_Db_Table_Abstract {

	const COMMENT_RESTRICT = 1;
	const COMMENT_ANON = 2;
	const COMMENT_DELETE = 3;

	protected $_name = 'users';
	protected $_primary = 'id';
	protected $_sequence = true;

	protected $_dependentTables = array('Strains', 'Comments', 'Mice', 'Transfers', 'Cages', 'Litters', 'Tags');

	public function delete($where, $commentAction = self::COMMENT_RESTRICT) {

		$db = $this->getAdapter();
		$toBeDeleted = $this->fetchAll($where);
		foreach ($toBeDeleted as $row) {
			if ($row->findMice()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete user becase they have modified mouse records.');
				return 0;
			}
			if ($row->findStrains()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete user becase they have modified strain records.');
				return 0;
			}
			if ($row->findTransfers()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete user becase they have modified mouse transfer records.');
				return 0;
			}
			if ($row->findCages()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete user becase they have modified cages.');
				return 0;
			}
			if ($row->findLitters()->count()) {
				throw new Zend_Db_Table_Exception('Unable to delete user becase they have modified litters.');
				return 0;
			}
			if ($commentAction == self::COMMENT_RESTRICT) {
				if ($db->fetchOne('select count(*) from comments where user_id = '.$db->quote($row->id))) {
					throw new Zend_Db_Table_Exception('Unable to delete user becase they recorded comments.');
					return 0;
				}
				if ($db->fetchOne('select count(*) from tags where user_id = '.$db->quote($row->id))) {
					throw new Zend_Db_Table_Exception('Unable to delete user becase they created tags.');
					return 0;
				}
			}
		}

		$comments = new Comments();
		$tags = new Tags();
		$searches = new Searches();
		$userPrefs = new UserPrefs();
		foreach ($toBeDeleted as $row) {
			if ($commentAction == self::COMMENT_ANON) {
				$comments->update(array('user_id' => null), 'user_id = ' . $db->quote($row->id));
				$tags->update(array('user_id' => null), 'user_id = ' . $db->quote($row->id));
			} elseif ($commentAction == self::COMMENT_DELETE) {
				$comments->delete('user_id = ' . $db->quote($row->id));
				$tags->delete('user_id = ' . $db->quote($row->id));
			}
			$searches->delete('user_id = ' . $db->quote($row->id)); // searches have to get deleted - anonymous searches are not accessible by anyone
			$userPrefs->delete('user_id = ' . $db->quote($row->id));
		}

		return parent::delete($where);
    }

}