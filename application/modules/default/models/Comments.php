<?php

class Comments extends Zend_Db_Table_Abstract {

	const STRAIN = 'strains';
	const MOUSE = 'mice';
	const LITTER = 'litters';
	const BREEDING_CAGE = 'breeding_cages';
	const HOLDING_CAGE = 'holding_cages';
	const WEANING_CAGE = 'weaning_cages';
	const TRANSFER = 'transfers';
	const PROTOCOL = 'protocols';
	const SEARCH = 'searches';

	protected $_name = 'comments';
	protected $_primary = 'id';
	protected $_sequence = true;

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
		$canDeleteOthers = $acl->isAllowed( Zend_Auth::getInstance()->getIdentity()->role_id, 'default_comment', 'delete_other' );
		foreach ($toBeDeleted as $row) {
			// raise exception if this comment was created by a different user or this user has no permissions to delete other people's comments
			if (! $canDeleteOthers && $row->user_id != Zend_Auth::getInstance()->getIdentity()->id ) {
				throw new Zend_Db_Table_Exception('Unable to delete comment because you can only delete your own.');
				return 0;
			}
		}

		return parent::delete($where);
    }

}
