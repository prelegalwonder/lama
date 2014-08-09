<?php

class Searches extends Zend_Db_Table_Abstract {

    const GO_PLACEHOLDER = 'Item ID'; // this has really nothing to do with searches - used for go button on main layout

    const SUBJECT_MOUSE = 'mouse';
    const SUBJECT_STRAIN = 'strain';
    const SUBJECT_LITTER = 'litter';
    const SUBJECT_BREEDING_CAGE = 'breeding-cage';
    const SUBJECT_HOLDING_CAGE = 'holding-cage';
    const SUBJECT_WEANING_CAGE = 'weaning-cage';
    const SUBJECT_TRANSFER = 'transfer';

	protected $_name = 'searches';
	protected $_primary = 'id';

	protected $_referenceMap = array(
    	'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		)
	);

	public function delete($where) {

		$toBeDeleted = $this->fetchAll($where);
    	$acl = mdb_Acl::getInstance();

    	foreach ($toBeDeleted as $row) {

//    		$canDeleteOtherSearches = $acl->isAllowed( Zend_Auth::getInstance ()->getIdentity ()->role_id, SearchController::ACL_RESOURCE, SearchController::DELETE_OTHER );
    		$canDeleteOtherSearches = $acl->isAllowed( Zend_Auth::getInstance ()->getIdentity ()->role_id, 'default_search', 'delete_other' );

    		if ( ! ($canDeleteOtherSearches || Zend_Auth::getInstance ()->getIdentity ()->id == $row->user_id )) {
				throw new Zend_Db_Table_Exception('Unable to delete search ' . $row->title . ' because it was not created by you.');
				return 0;
			}
		}

		$db = $this->getAdapter();

		$comments = new Comments();
		$tags = new Tags();

		foreach ($toBeDeleted as $row) {
			$comments->delete('ref_table = '.$db->quote(Comments::MOUSE).' and ref_item_id = ' . $db->quote($row->id));
			$tags->delete('ref_table = '.$db->quote(Tags::MOUSE).' and ref_item_id = ' . $db->quote($row->id));
		}

		return parent::delete($where);
	}

}
