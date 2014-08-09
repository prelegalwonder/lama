<?php

class mdb_Search_BreedingCage extends mdb_Search_Abstract {

	public function __construct ($htmld, $params = array())
	{
		// define query fields
		$cage_id = new mdb_Search_Field_Text('cages.assigned_id');
		$cage_id->setLabel('Cage ID');

		$active = new mdb_Search_Field_Boolean( 'active' );
		$active->setLabel ( 'Active' )
			->setSqlRef('breeding_cages.active');

		$set_up_on = new mdb_Search_Field_Date( 'set_up_on' );
		$set_up_on->setLabel ( 'Set Up On' );

		$set_up_ago = new mdb_Search_Field_Number( 'set_up_ago' );
		$set_up_ago->setLabel ( 'Set Up (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(set_up_on)'));

		$breeding_type = new mdb_Search_Field_ComboBox( 'breeding_type' );
		$breeding_type->setLabel ( 'Breeding Type' )
			->setStoreType('dojo.data.ItemFileReadStore')
			->setDataStoreUrl('/breeding-cage/suggest/field/breeding_type/format/json');

		$mating_type = new mdb_Search_Field_ComboBox( 'mating_type' );
		$mating_type->setLabel ( 'Mating Type' )
			->setStoreType('dojo.data.ItemFileReadStore')
			->setDataStoreUrl('/breeding-cage/suggest/field/mating_type/format/json');

		$stud_strain = new mdb_Search_Field_ComboBox( 'male_breeder_strain' );
		$stud_strain->setLabel ( 'Male Strain' )
			->isSubselect(true)
			->setSubselect('select * from mice inner join strains on mice.strain_id = strains.id where mice.id = breeding_cages.assigned_stud_id')
			->setSqlRef('strains.strain_name')
			->setDataStoreUrl('/strain/list/format/json');

		$female_strain = new mdb_Search_Field_ComboBox( 'female_breeder_strain' );
		$female_strain->setLabel ( 'Female Strain' )
			->isSubselect(true)
//			->setSubselect('select * from mice inner join strains on mice.strain_id = strains.id where mice.sex = \'F\' and is_alive and mice.cage_id = breeding_cages.id')
			->setSubselect('select * from mice inner join strains on mice.strain_id = strains.id where mice.sex = \'F\' and mice.cage_id = breeding_cages.id')
			->setSqlRef('strains.strain_name')
			->setDataStoreUrl('/strain/list/format/json');

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
			->setSqlRef(new Zend_Db_Expr('DATE(IF(cages.lastmodified > breeding_cages.lastmodified, cages.lastmodified, breeding_cages.lastmodified))'));

		$tag = new mdb_Search_Field_ComboBox( 'tag' );
		$tag->setLabel ( 'Tag' )
			->isSubselect(true)
			->setSubselect('select * from tags where tags.ref_table = \''.Tags::BREEDING_CAGE.'\' and tags.ref_item_id = cages.id')
			->setSqlRef('tags.tag')
			->setDataStoreUrl('/tag/list/format/json');

		$comment = new mdb_Search_Field_Text( 'comment' );
		$comment->setLabel ( 'Comment' )
			->isSubselect(true)
			->setSubselect('select * from comments where comments.ref_table = \''.Comments::BREEDING_CAGE.'\' and comments.ref_item_id = cages.id')
			->setSqlRef('comments.comment');

		$this->setFields(array($cage_id, $active, $set_up_on, $set_up_ago, $breeding_type, $mating_type,
			$female_strain, $stud_strain, $protocol, $tag, $comment, $modified_by, $modified_on));

		// define output fields
		$outName = new mdb_Search_OutputField('cages.assigned_id');
		$outName->setLabel('Cage ID')
			->setIsDefault(true)
			->setIsText(true)
			->setIdSqlExpr('cages.id')
			->setIdSqlAs('cages_id')
			->setViewController('breeding-cage');

		$outActive= new mdb_Search_OutputField('active');
		$outActive->setLabel('Active')
			->setType(mdb_Search_OutputField::TYPE_BOOLEAN)
			->setSqlExpr('breeding_cages.active')
			->setSqlAs('cage_is_acive');

		$outBreedingType = new mdb_Search_OutputField('breeding_type');
		$outBreedingType->setLabel('Breeding Type');

		$outMatingType = new mdb_Search_OutputField('mating_type');
		$outMatingType->setLabel('Mating Type');

		$outSetUpOn = new mdb_Search_OutputField('set_up_on');
		$outSetUpOn->setLabel('Set Up On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME);

		$outSetUpAgo = new mdb_Search_OutputField('set_up_ago');
		$outSetUpAgo->setLabel('Set Up (days ago)')
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(set_up_on)')
			->setSqlAs('set_up_ago');

		$outAssignedMaleStrain = new mdb_Search_OutputField('male_breeder_strain');
		$outAssignedMaleStrain->setLabel('Male Strain')
			->setSqlExpr('(select strain_name from mice inner join strains on mice.strain_id = strains.id where mice.id = breeding_cages.assigned_stud_id)')
			->setSqlAs('male_breeder_strain')
			->setIdSqlExpr('(select strain_id from mice where mice.id = breeding_cages.assigned_stud_id)')
			->setIdSqlAs('male_breeder_strain_id')
			->setIsText(true)
			->setViewController('strain');

		$outFirstFemaleStrain = new mdb_Search_OutputField('female_breeder_strain');
		$outFirstFemaleStrain->setLabel('Female Strain')
//			->setSqlExpr('(select strain_name from mice inner join strains on mice.strain_id = strains.id where mice.sex = \'F\' and is_alive and mice.cage_id = breeding_cages.id limit 1)')
			->setSqlExpr('(select strain_name from mice inner join strains on mice.strain_id = strains.id where mice.sex = \'F\' and mice.cage_id = breeding_cages.id limit 1)')
			->setSqlAs('female_breeder_strain')
//			->setIdSqlExpr('(select strain_id from mice where mice.sex = \'F\' and is_alive and mice.cage_id = breeding_cages.id limit 1)')
			->setIdSqlExpr('(select strain_id from mice where mice.sex = \'F\' and mice.cage_id = breeding_cages.id limit 1)')
			->setIdSqlAs('female_breeder_strain_id')
			->setIsText(true)
			->setViewController('strain');

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
			->setSqlExpr('IF(cages.lastmodified > breeding_cages.lastmodified, cages.lastmodified, breeding_cages.lastmodified)')
			->setSqlAs('cages_lastmodified');

		$this->setAvailableOutputFields(array($outName, $outActive, $outSetUpOn, $outSetUpAgo, $outBreedingType,
			$outMatingType, $outFirstFemaleStrain, $outAssignedMaleStrain, $outProtocol, $outModifiedBy, $outModifiedOn));

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

		$select->from('breeding_cages', null);
		$select->joinInner('cages', 'cages.id = breeding_cages.id', null);
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