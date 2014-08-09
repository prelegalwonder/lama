<?php

class mdb_Search_Litter extends mdb_Search_Abstract {

	public function __construct ($htmld, $params = array())
	{
		// define query fields
		$name = new mdb_Search_Field_Text('assigned_id');
		$name->setLabel('Litter ID')
			->setSqlRef('litters.assigned_id');

		$born_on = new mdb_Search_Field_Date( 'born_on' );
		$born_on->setLabel ( 'Born On' )
			->setSqlRef('litters.born_on');

		$born_ago = new mdb_Search_Field_Number( 'born_ago' );
		$born_ago->setLabel ( 'Born (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(litters.born_on)'));

		$weaned_on = new mdb_Search_Field_Date( 'weaned_on' );
		$weaned_on->setLabel ( 'Weaned On' )
			->setSqlRef('litters.weaned_on');

		$weaned_ago = new mdb_Search_Field_Number( 'weaned_ago' );
		$weaned_ago->setLabel ( 'Weaned (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(litters.weaned_on)'));

		$strain = new mdb_Search_Field_ComboBox( 'strain_name' );
		$strain->setLabel ( 'Strain' )
			->setSqlRef('strains.strain_name')
			->setRequiredJoin('strains')
			->setDataStoreUrl('/strain/list/format/json');

		$generation = new mdb_Search_Field_ComboBox( 'generation' );
		$generation->setLabel ( 'Generation' )
			->setDataStoreUrl('/litter/suggest/field/generation/format/json');

		$cage = new mdb_Search_Field_ComboBox( 'cage' );
		$cage->setLabel ( 'Breeding Cage' )
			->setSqlRef('cages.assigned_id')
			->setRequiredJoin('cages')
			->setDataStoreUrl('/cage/list/format/json/type/breeding');

		$protocol = new mdb_Search_Field_ForeignKey( 'protocol' );
		$protocol->setLabel ( 'Protocol' )
			->setForeignTable('protocols')
			->setSqlRef('litters.protocol_id')
			->setRequiredJoin('protocols')
			->setDataStoreUrl('/protocol/list/empty/yes/format/json');

		$total_pups = new mdb_Search_Field_Number( 'total_pups' );
		$total_pups->setLabel ( 'Born pups' );

		$alive_pups = new mdb_Search_Field_Number( 'alive_pups' );
		$alive_pups->setLabel ( 'Alive at wean' );

		$total_females = new mdb_Search_Field_Number( 'total_females' );
		$total_females->setLabel ( 'Total females' )
			->setSqlRef(new Zend_Db_Expr('IFNULL(weaned_female_count,0) + IFNULL(sacrificed_female_count,0) + IFNULL(holding_female_count,0)'));

		$total_males = new mdb_Search_Field_Number( 'total_males' );
		$total_males->setLabel ( 'Total males' )
			->setSqlRef(new Zend_Db_Expr('IFNULL(weaned_male_count,0) + IFNULL(sacrificed_male_count,0) + IFNULL(holding_male_count,0)'));

		$unknown_sex = new mdb_Search_Field_Number( 'unknown_sex' );
		$unknown_sex->setLabel ( 'Unknown sex CO2' )
			->setSqlRef(new Zend_Db_Expr('IFNULL(alive_pups,0) - IFNULL(weaned_female_count,0) - IFNULL(sacrificed_female_count,0) - IFNULL(holding_female_count,0) - IFNULL(weaned_male_count,0) - IFNULL(sacrificed_male_count,0) - IFNULL(holding_male_count,0)'));

		$weaned_female_count = new mdb_Search_Field_Number( 'weaned_female_count' );
		$weaned_female_count->setLabel ( 'Weaned females' );

		$weaned_male_count = new mdb_Search_Field_Number( 'weaned_male_count' );
		$weaned_male_count->setLabel ( 'Weaned males' );

		$holding_female_count = new mdb_Search_Field_Number( 'holding_female_count' );
		$holding_female_count->setLabel ( 'Holding females' );

		$holding_male_count = new mdb_Search_Field_Number( 'holding_male_count' );
		$holding_male_count->setLabel ( 'Holding males' );

		$sacrificed_female_count = new mdb_Search_Field_Number( 'sacrificed_female_count' );
		$sacrificed_female_count->setLabel ( 'Sacrificed females' );

		$sacrificed_male_count = new mdb_Search_Field_Number( 'sacrificed_male_count' );
		$sacrificed_male_count->setLabel ( 'Sacrificed males' );

		$modified_by = new mdb_Search_Field_ForeignKey( 'user_id' );
		$modified_by->setLabel ( 'Modified By' )
			->setForeignTable('users')
			->setSqlRef('litters.user_id')
			->setDataStoreUrl('/user/list/empty/yes/format/json');

		$modified_on = new mdb_Search_Field_Date( 'lastmodified' );
		$modified_on->setLabel ( 'Modified On' )
			->setSqlRef(new Zend_Db_Expr('DATE(litters.lastmodified)'));

		$tag = new mdb_Search_Field_ComboBox( 'tag' );
		$tag->setLabel ( 'Tag' )
			->isSubselect(true)
			->setSubselect('select * from tags where tags.ref_table = \''.Tags::LITTER.'\' and tags.ref_item_id = litters.id')
			->setSqlRef('tags.tag')
			->setDataStoreUrl('/tag/list/format/json');

		$comment = new mdb_Search_Field_Text( 'comment' );
		$comment->setLabel ( 'Comment' )
			->isSubselect(true)
			->setSubselect('select * from comments where comments.ref_table = \''.Comments::LITTER.'\' and comments.ref_item_id = litters.id')
			->setSqlRef('comments.comment');

		$this->setFields(array($name, $born_on, $born_ago, $weaned_on, $weaned_ago, $strain, $generation, $cage, $protocol,
			$total_pups, $alive_pups, $total_females, $total_males, $unknown_sex, $weaned_female_count, $weaned_male_count, $holding_female_count,
			$holding_male_count, $sacrificed_female_count, $sacrificed_male_count, $tag, $comment, $modified_by, $modified_on));

		// define output fields
		$outName = new mdb_Search_OutputField('assigned_id');
		$outName->setLabel('Litter ID')
			->setIsDefault(true)
			->setIdSqlExpr('litters.id')
			->setIdSqlAs('litter_id')
			->setIsText(true)
			->setViewController('litter');

		$outBornOn = new mdb_Search_OutputField('born_on');
		$outBornOn->setLabel('Born On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('litters.born_on')
			->setSqlAs('litters_born_on');

		$outBornAgo = new mdb_Search_OutputField('born_ago');
		$outBornAgo->setLabel("Born\n(days ago)")
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(litters.born_on)')
			->setSqlAs('litters_born_ago');

		$outWeanedOn = new mdb_Search_OutputField('weaned_on');
		$outWeanedOn->setLabel('Weaned On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('litters.weaned_on')
			->setSqlAs('litters_weaned_on');

		$outWeanedAgo = new mdb_Search_OutputField('weaned_ago');
		$outWeanedAgo->setLabel("Weaned\n(days ago)")
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(litters.weaned_on)')
			->setSqlAs('litters_weaned_ago');

		$outGeneration = new mdb_Search_OutputField('generation');
		$outGeneration->setLabel('Generation')
			->setSqlExpr('litters.generation');

		$outStrain = new mdb_Search_OutputField('strain_name');
		$outStrain->setLabel('Strain')
			->setSqlExpr('strains.strain_name')
			->setSqlAs('litter_strain_name')
			->setIdSqlExpr('litters.strain_id')
			->setIdSqlAs('litters_strain_id')
			->setRequiredJoin('strains')
			->setViewController('strain');

		$outCage = new mdb_Search_OutputField('cage_assigned_id');
		$outCage->setLabel("Breeding\nCage")
			->setSqlExpr('cages.assigned_id')
			->setSqlAs('cages_assigned_id')
			->setIdSqlExpr('litters.breeding_cage_id')
			->setIdSqlAs('litters_cage_id')
			->setIsText(true)
			->setRequiredJoin('cages')
			->setViewController('breeding-cage');

		$outProtocol = new mdb_Search_OutputField('litters_protocol_name');
		$outProtocol->setLabel('Protocol')
			->setSqlExpr('protocols.protocol_name')
			->setSqlAs('litters_protocol_name')
			->setIdSqlExpr('litters.protocol_id')
			->setIdSqlAs('litters_protocol_id')
			->setIsText(true)
			->setRequiredJoin('protocols')
			->setViewController('protocol');

		$outTotalPups = new mdb_Search_OutputField('total_pups');
		$outTotalPups->setLabel("Born\npups");

		$outAliveAtWean = new mdb_Search_OutputField('alive_pups');
		$outAliveAtWean->setLabel("Alive\nat wean");

		$outTotalFemales = new mdb_Search_OutputField('total_females');
		$outTotalFemales->setLabel("Total\nfemales")
			->setSqlExpr('NULLIF(IFNULL(weaned_female_count,0) + IFNULL(sacrificed_female_count,0) + IFNULL(holding_female_count,0),0)');

		$outTotalMales = new mdb_Search_OutputField('total_males');
		$outTotalMales->setLabel("Total\nmales")
			->setSqlExpr('NULLIF(IFNULL(weaned_male_count,0) + IFNULL(sacrificed_male_count,0) + IFNULL(holding_male_count,0),0)');

		$outUnknownSex = new mdb_Search_OutputField('unknown_sex');
		$outUnknownSex->setLabel("Unknown\nSex (CO2)")
			->setSqlExpr('NULLIF(IFNULL(alive_pups,0) - IFNULL(weaned_female_count,0) - IFNULL(sacrificed_female_count,0) - IFNULL(holding_female_count,0) - IFNULL(weaned_male_count,0) - IFNULL(sacrificed_male_count,0) - IFNULL(holding_male_count,0),0)');

		$outWeanedFemales = new mdb_Search_OutputField( 'weaned_female_count' );
		$outWeanedFemales->setLabel ( "Weaned\nfemales" )
			->setSqlExpr('NULLIF(weaned_female_count,0)');

		$outWeanedMales = new mdb_Search_OutputField( 'weaned_male_count' );
		$outWeanedMales->setLabel ( "Weaned\nmales" )
			->setSqlExpr('NULLIF(weaned_male_count,0)');

		$outHoldingFemales = new mdb_Search_OutputField( 'holding_female_count' );
		$outHoldingFemales->setLabel ( "Holding\nfemales" )
			->setSqlExpr('NULLIF(holding_female_count,0)');

		$outHoldingMales = new mdb_Search_OutputField( 'holding_male_count' );
		$outHoldingMales->setLabel ( "Holding\nmales" )
			->setSqlExpr('NULLIF(holding_male_count,0)');

		$outSacrificedFemales = new mdb_Search_OutputField( 'sacrificed_female_count' );
		$outSacrificedFemales->setLabel ( "Sacrificed\nfemales" )
			->setSqlExpr('NULLIF(sacrificed_female_count,0)');

		$outSacrificedMales = new mdb_Search_OutputField( 'sacrificed_male_count' );
		$outSacrificedMales->setLabel ( "Sacrificed\nmales" )
			->setSqlExpr('NULLIF(sacrificed_male_count,0)');

		$outModifiedBy = new mdb_Search_OutputField('username');
		$outModifiedBy->setLabel('Modified By')
			->setSqlExpr('users.username')
			->setSqlAs('username')
			->setRequiredJoin('users');

		$outModifiedOn = new mdb_Search_OutputField('lastmodified');
		$outModifiedOn->setLabel('Modified On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('litters.lastmodified')
			->setSqlAs('litters_lastmodified');

		$this->setAvailableOutputFields(array($outName, $outBornOn, $outBornAgo, $outWeanedOn, $outWeanedAgo,
			$outStrain, $outGeneration, $outCage, $outProtocol, $outTotalPups, $outAliveAtWean, $outTotalFemales,
			$outTotalMales, $outUnknownSex, $outWeanedFemales, $outWeanedMales, $outHoldingFemales, $outHoldingMales,
			$outSacrificedFemales, $outSacrificedMales, $outModifiedBy, $outModifiedOn));

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

		$select->from('litters', null);
		foreach ($requiredTables as $table) {
			switch ($table) {
				case 'users':
					$select->joinLeft ( 'users', 'users.id = litters.user_id', null);
					break;
				case 'cages':
					$select->joinLeft ( 'cages', 'cages.id = litters.breeding_cage_id', null);
					break;
				case 'strains':
					$select->joinLeft ( 'strains', 'strains.id = litters.strain_id', null);
					break;
				case 'protocols':
					$select->joinLeft ( 'protocols', 'protocols.id = litters.protocol_id', null);
					break;
				default:
					throw new Exception('Unknown required join table');
			}
		}

		$select->order('litters.assigned_id');
		return $select;
	}
}