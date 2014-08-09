<?php

class mdb_Search_Transfer extends mdb_Search_Abstract {

    public function __construct ($htmld, $params = array())
    {
        // define query fields
        $mouse = new mdb_Search_Field_ComboBox( 'mouse_assigned_id' );
		$mouse->setLabel ( 'Mouse' )
			->setSqlRef('mice.assigned_id')
			->setRequiredJoin('mice')
			->setDataStoreUrl('/mouse/list/format/json');

		$from_cage = new mdb_Search_Field_ComboBox( 'from_cage' );
		$from_cage->setLabel ( 'From Cage' )
			->setSqlRef('from_cages.assigned_id')
			->setRequiredJoin('cages')
			->setDataStoreUrl('/cage/list/format/json/type/all');

		$to_cage = new mdb_Search_Field_ComboBox( 'to_cage' );
		$to_cage->setLabel ( 'To Cage' )
			->setSqlRef('to_cages.assigned_id')
			->setRequiredJoin('cages')
			->setDataStoreUrl('/cage/list/format/json/type/all');

		$xfer_date = new mdb_Search_Field_Date( 'transferred_on' );
		$xfer_date->setLabel ( 'Transferred On' );

		$xfer_ago = new mdb_Search_Field_Number( 'xfer_ago' );
		$xfer_ago->setLabel ( 'Transferred (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(transferred_on)'));

        $notes = new mdb_Search_Field_Text('notes');
        $notes->setLabel('Reason');

        $modified_by = new mdb_Search_Field_ForeignKey( 'user_id' );
		$modified_by->setLabel ( 'Modified By' )
		    ->setForeignTable('users')
		    ->setSqlRef('transfers.user_id')
		    ->setDataStoreUrl('/user/list/empty/yes/format/json');

		$modified_on = new mdb_Search_Field_Date( 'lastmodified' );
		$modified_on->setLabel ( 'Modified On' )
		    ->setSqlRef('transfers.lastmodified');

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

		$this->setFields(array($mouse, $from_cage, $to_cage, $xfer_date, $xfer_ago,
			$notes, $tag, $comment, $modified_by, $modified_on));

		// define output fields
		$outMouse = new mdb_Search_OutputField('mouse_assigned_id');
		$outMouse->setLabel('Mouse')
		    ->setIsDefault(true)
		    ->setSqlExpr('mice.assigned_id')
			->setSqlAs('mouse_assigned_id')
			->setIdSqlExpr('transfers.mouse_id')
			->setIdSqlAs('transfers_mouse_id')
			->setIsText(true)
			->setRequiredJoin('mice')
			->setViewController('mouse');

		$outFromCage = new mdb_Search_OutputField('from_cage');
		$outFromCage->setLabel('From Cage')
		    ->setIsDefault(true)
		    ->setSqlExpr('from_cages.assigned_id')
			->setSqlAs('from_cages_assigned_id')
			->setIdSqlExpr('from_cage_id')
			->setIsText(true)
			->setRequiredJoin('cages')
			->setViewController('cage');

		$outToCage = new mdb_Search_OutputField('to_cage');
		$outToCage->setLabel('To Cage')
		    ->setIsDefault(true)
		    ->setSqlExpr('to_cages.assigned_id')
			->setSqlAs('to_cages_assigned_id')
			->setIdSqlExpr('to_cage_id')
			->setIsText(true)
			->setRequiredJoin('cages')
			->setViewController('cage');

		$outTransferredOn = new mdb_Search_OutputField('transferred_on');
		$outTransferredOn->setLabel('Transferred On')
		    ->setIsDefault(true)
		    ->setType(mdb_Search_OutputField::TYPE_DATETIME);

		$outTransferredAgo = new mdb_Search_OutputField('transferred_ago');
		$outTransferredAgo->setLabel('Trans. (days ago)')
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(transferred_on)')
			->setSqlAs('xferred_ago');

		$outNotes = new mdb_Search_OutputField('notes');
		$outNotes->setLabel('Reason');

		$outModifiedBy = new mdb_Search_OutputField('username');
		$outModifiedBy->setLabel('Modified By')
			->setRequiredJoin('users')
			->setSqlExpr('users.username')
		    ->setSqlAs('username');

		$outModifiedOn = new mdb_Search_OutputField('lastmodified');
		$outModifiedOn->setLabel('Modified On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('transfers.lastmodified')
		    ->setSqlAs('transfers_lastmodified');

		$this->setAvailableOutputFields(array($outMouse, $outFromCage, $outToCage,
			$outTransferredOn, $outTransferredAgo, $outNotes, $outModifiedBy, $outModifiedOn));

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

		$select->from('transfers', null);
		foreach ($requiredTables as $table) {
			switch ($table) {
				case 'mice':
					$select->joinLeft ( 'mice', 'mice.id = transfers.mouse_id', null);
					break;
				case 'cages':
					$select->joinLeft ( 'cages as from_cages', 'from_cages.id = from_cage_id', null);
					$select->joinLeft ( 'cages as to_cages', 'to_cages.id = to_cage_id', null);
					break;
				case 'users':
					$select->joinLeft ( 'users', 'users.id = transfers.user_id', null);
					break;
				default:
					throw new Exception('Unknown required join table');
			}
		}

		$select->order('transferred_on');
		return $select;
	}
}