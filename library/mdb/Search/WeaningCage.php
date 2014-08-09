<?php

class mdb_Search_WeaningCage extends mdb_Search_Abstract {

    public function __construct ($htmld, $params = array())
    {
        // define query fields
        $cage_id = new mdb_Search_Field_Text('cages.assigned_id');
        $cage_id->setLabel('Cage ID');

        $litter = new mdb_Search_Field_ComboBox( 'litter_assigned_id' );
		$litter->setLabel ( 'Litter' )
			->setSqlRef('litters.assigned_id')
			->setRequiredJoin('litters')
			->setDataStoreUrl('/litter/list/format/json');

		$sex = new mdb_Search_Field_ForeignKey( 'sex' );
		$sex->setLabel ( 'Sex' )
			->setMultiOptions(array('' => '', 'M' => 'Male', 'F' => 'Female') );

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
		    ->setSqlRef(new Zend_Db_Expr('DATE(IF(cages.lastmodified > weaning_cages.lastmodified, cages.lastmodified, weaning_cages.lastmodified))'));

		$tag = new mdb_Search_Field_ComboBox( 'tag' );
		$tag->setLabel ( 'Tag' )
		    ->isSubselect(true)
		    ->setSubselect('select * from tags where tags.ref_table = \''.Tags::WEANING_CAGE.'\' and tags.ref_item_id = cages.id')
		    ->setSqlRef('tags.tag')
		    ->setDataStoreUrl('/tag/list/format/json');

		$comment = new mdb_Search_Field_Text( 'comment' );
		$comment->setLabel ( 'Comment' )
		    ->isSubselect(true)
		    ->setSubselect('select * from comments where comments.ref_table = \''.Comments::WEANING_CAGE.'\' and comments.ref_item_id = cages.id')
		    ->setSqlRef('comments.comment');

		$this->setFields(array($cage_id, $litter, $sex, $protocol, $tag, $comment, $modified_by, $modified_on));

		// define output fields
		$outName = new mdb_Search_OutputField('cages.assigned_id');
		$outName->setLabel('Cage ID')
		    ->setIsDefault(true)
		    ->setIsText(true)
		    ->setIdSqlExpr('cages.id')
		    ->setIdSqlAs('cages_id')
		    ->setViewController('weaning-cage');

		$outLitter = new mdb_Search_OutputField('litter_assigned_id');
		$outLitter->setLabel('Litter')
			->setSqlExpr('litters.assigned_id')
			->setSqlAs('litter_assigned_id')
			->setIdSqlExpr('weaning_cages.litter_id')
			->setIdSqlAs('weaning_litter_id')
			->setIsText(true)
			->setRequiredJoin('litters')
			->setViewController('litter');

		$outSex = new mdb_Search_OutputField('sex');
		$outSex->setLabel('Sex')
			->setSqlExpr('weaning_cages.sex');

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
			->setSqlExpr('IF(cages.lastmodified > weaning_cages.lastmodified, cages.lastmodified, weaning_cages.lastmodified)')
		    ->setSqlAs('cages_lastmodified');

		$this->setAvailableOutputFields(array($outName, $outLitter, $outSex, $outProtocol, $outModifiedBy, $outModifiedOn));

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

		$select->from('weaning_cages', null);
		$select->joinInner('cages', 'cages.id = weaning_cages.id', null);
		foreach ($requiredTables as $table) {
			switch ($table) {
				case 'litters':
					$select->joinLeft ( 'litters', 'litters.id = weaning_cages.litter_id', null);
					break;
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