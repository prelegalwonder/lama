<?php

class Tags extends Zend_Db_Table_Abstract {

	const STRAIN = 'strains';
	const MOUSE = 'mice';
	const LITTER = 'litters';
	const BREEDING_CAGE = 'breeding_cages';
	const HOLDING_CAGE = 'holding_cages';
	const WEANING_CAGE = 'weaning_cages';
	const TRANSFER = 'transfers';
	const PROTOCOL = 'protocols';
	const SEARCH = 'searches';

	protected $_name = 'tags';
	protected $_primary = array('ref_table', 'ref_item_id', 'tag');

	protected $_referenceMap = array(
    	'User' => array(
			'columns'		=> 'user_id',
			'refTableClass'	=> 'Users',
			'onUpdate'		=> self::CASCADE,
		)
	);

}
