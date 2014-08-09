<?php

class Cages extends Zend_Db_Table_Abstract {

	const BREEDING = 'breeding';
	const WEANING = 'weaning';
	const HOLDING = 'holding';

	protected $_name = 'cages';

	protected $_primary = 'id';
	protected $_sequence = true;

	protected $_dependentTables = array('BreedingCages', 'WeaningCages', 'HoldingCages', 'Mice', 'Transfers', 'Litters');

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
}
