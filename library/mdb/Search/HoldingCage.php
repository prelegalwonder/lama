<?php

class mdb_Search_HoldingCage extends mdb_Search_Abstract {

	public function __construct ($htmld, $params = array())
	{
		// define query fields
		$cage_id = new mdb_Search_Field_Text('cages.assigned_id');
		$cage_id->setLabel('Cage ID');

		$active = new mdb_Search_Field_Boolean( 'active' );
		$active->setLabel ( 'Active' )
			->setSqlRef('holding_cages.active');

		$set_up_on = new mdb_Search_Field_Date( 'set_up_on' );
		$set_up_on->setLabel ( 'Set Up On' );

		$set_up_ago = new mdb_Search_Field_Number( 'set_up_ago' );
		$set_up_ago->setLabel ( 'Set Up (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(set_up_on)'));

		$protocol = new mdb_Search_Field_ForeignKey( 'protocol' );
		$protocol->setLabel ( 'Protocol' )
			->setForeignTable('protocols')
			->setSqlRef('cages.protocol_id')
			->setRequiredJoin('protocols')
			->setDataStoreUrl('/protocol/list/empty/yes/format/json');

		$modified_by = new mdb_Search_Field_ForeignKey( 'user_id' );
		$modified_by->setLabel ( 'Modified By' )
			->setForeignTable('users')
			->setDataStoreUrl('/user/list/empty/yes/format/json');

		$modified_on = new mdb_Search_Field_Date( 'lastmodified' );
		$modified_on->setLabel ( 'Modified On' )
			->setSqlRef(new Zend_Db_Expr('DATE(IF(cages.lastmodified > holding_cages.lastmodified, cages.lastmodified, holding_cages.lastmodified))'));

		$tag = new mdb_Search_Field_ComboBox( 'tag' );
		$tag->setLabel ( 'Tag' )
			->isSubselect(true)
			->setSubselect('select * from tags where tags.ref_table = \''.Tags::HOLDING_CAGE.'\' and tags.ref_item_id = cages.id')
			->setSqlRef('tags.tag')
			->setDataStoreUrl('/tag/list/format/json');

		$comment = new mdb_Search_Field_Text( 'comment' );
		$comment->setLabel ( 'Comment' )
			->isSubselect(true)
			->setSubselect('select * from comments where comments.ref_table = \''.Comments::HOLDING_CAGE.'\' and comments.ref_item_id = cages.id')
			->setSqlRef('comments.comment');

		$this->setFields(array($cage_id, $active, $set_up_on, $set_up_ago, $protocol, $tag, $comment, $modified_by, $modified_on));

		// define output fields
		$outName = new mdb_Search_OutputField('cages.assigned_id');
		$outName->setLabel('Cage ID')
			->setIsDefault(true)
			->setIsText(true)
			->setIdSqlExpr('cages.id')
			->setIdSqlAs('cages_id')
			->setViewController('holding-cage');

		$outActive= new mdb_Search_OutputField('active');
		$outActive->setLabel('Active')
			->setType(mdb_Search_OutputField::TYPE_BOOLEAN)
			->setSqlExpr('holding_cages.active')
			->setSqlAs('cage_is_acive');

		$outSetUpOn = new mdb_Search_OutputField('set_up_on');
		$outSetUpOn->setLabel('Set Up On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME);

		$outSetUpAgo = new mdb_Search_OutputField('set_up_ago');
		$outSetUpAgo->setLabel('Set Up (days ago)')
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(set_up_on)')
			->setSqlAs('set_up_ago');

		$outProtocol = new mdb_Search_OutputField('cage_protocol_name');
		$outProtocol->setLabel('Protocol')
			->setSqlExpr('protocols.protocol_name')
			->setSqlAs('cage_protocol_name')
			->setIdSqlExpr('cages.protocol_id')
			->setIdSqlAs('cage_protocol_id')
			->setIsText(true)
			->setRequiredJoin('protocols')
			->setViewController('protocol');

		$outModifiedBy = new mdb_Search_OutputField('username');
		$outModifiedBy->setLabel('Modified By')
			->setRequiredJoin('users')
			->setSqlExpr('users.username')
			->setSqlAs('username');

		$outModifiedOn = new mdb_Search_OutputField('lastmodified');
		$outModifiedOn->setLabel('Modified On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('IF(cages.lastmodified > holding_cages.lastmodified, cages.lastmodified, holding_cages.lastmodified)')
			->setSqlAs('cages_lastmodified');

		$this->setAvailableOutputFields(array($outName, $outActive, $outSetUpOn, $outSetUpAgo, $outProtocol, $outModifiedBy, $outModifiedOn));

		parent::__construct($htmld, $params);

	}

	public function setupSelect($select) {

		$requiredTables = array();
		// get the list of all required joins

		// check all select and all output fields
		foreach ($this->getFields() as $field) {
			if ( ! is_null($field->assembleWhere()) && ! is_null($field->getRequiredJoin()) ) {
				$requiredTables[] = $field->getRequiredJoin();
			}
		}
		foreach ($this->getOutputFields() as $field) {
			if ( ! is_null($field->getRequiredJoin()) ) {
				$requiredTables[] = $field->getRequiredJoin();
			}
		}
		$requiredTables = array_unique($requiredTables);

		$select->from('holding_cages', null);
		$select->joinInner('cages', 'cages.id = holding_cages.id', null);
		foreach ($requiredTables as $table) {
			switch ($table) {
				case 'protocols':
					$select->joinLeft ( 'protocols', 'protocols.id = cages.protocol_id', null);
					break;
				case 'users':
					$select->joinLeft ( 'users', 'users.id = cages.user_id', null);
					break;
				default:
					throw new Exception('Unknown required join table');
			}
		}

		$select->order('cages.assigned_id');
		return $select;
	}
}
