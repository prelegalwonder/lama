<?php

class mdb_Search_Mouse extends mdb_Search_Abstract {

	public function __construct ($htmld, $params = array())
	{
		// define query fields
		$name = new mdb_Search_Field_Text('assigned_id');
		$name->setLabel('Mouse ID')
			->setSqlRef('mice.assigned_id');

		$is_alive = new mdb_Search_Field_Boolean( 'is_alive' );
		$is_alive->setLabel ( 'Alive' );

		$sex = new mdb_Search_Field_ForeignKey( 'sex' );
		$sex->setLabel ( 'Sex' )
			->setMultiOptions(array('' => '', 'M' => 'Male', 'F' => 'Female') );

		$status = new mdb_Search_Field_ComboBox( 'status' );
		$status->setLabel ( 'Status' )
			->setDataStoreUrl('/mouse/suggest/field/status/format/json');

		$born_on = new mdb_Search_Field_Date( 'born_on' );
		$born_on->setLabel ( 'Born On' )
			->setSqlRef('mice.born_on');

		$born_ago = new mdb_Search_Field_Number( 'born_ago' );
		$born_ago->setLabel ( 'Born (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(mice.born_on)'));

		$weaned_on = new mdb_Search_Field_Date( 'weaned_on' );
		$weaned_on->setLabel ( 'Weaned On' )
			->setSqlRef('mice.weaned_on');

		$weaned_ago = new mdb_Search_Field_Number( 'weaned_ago' );
		$weaned_ago->setLabel ( 'Weaned (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(mice.weaned_on)'));

		$terminated_on = new mdb_Search_Field_Date( 'terminated_on' );
		$terminated_on->setLabel ( 'Terminated On' )
			->setSqlRef('mice.terminated_on');

		$terminated_ago = new mdb_Search_Field_Number( 'terminated_ago' );
		$terminated_ago->setLabel ( 'Terminated (days ago)' )
			->setSqlRef(new Zend_Db_Expr('TO_DAYS(CURDATE()) - TO_DAYS(mice.terminated_on)'));

		$strain = new mdb_Search_Field_ComboBox( 'strain_name' );
		$strain->setLabel ( 'Strain' )
			->setSqlRef('strains.strain_name')
			->setRequiredJoin('strains')
			->setDataStoreUrl('/strain/list/format/json');

		$genotype = new mdb_Search_Field_ComboBox( 'genotype' );
		$genotype->setLabel ( 'Genotype' )
			->setDataStoreUrl('/mouse/suggest/field/genotype/format/json');

		$generation = new mdb_Search_Field_ComboBox( 'generation' );
		$generation->setLabel ( 'Generation' )
			->setDataStoreUrl('/mouse/suggest/field/generation/format/json');

		$protocol = new mdb_Search_Field_ForeignKey( 'protocol' );
		$protocol->setLabel ( 'Protocol' )
			->setForeignTable('protocols')
			->setSqlRef('mice.protocol_id')
			->setRequiredJoin('protocols')
			->setDataStoreUrl('/protocol/list/empty/yes/format/json');

		$cage = new mdb_Search_Field_ComboBox( 'cage' );
		$cage->setLabel ( 'Cage' )
			->setSqlRef('cages.assigned_id')
			->setRequiredJoin('cages')
			->setDataStoreUrl('/cage/list/format/json/type/all');

		$litter = new mdb_Search_Field_ComboBox( 'litter_assigned_id' );
		$litter->setLabel ( 'Litter' )
			->setSqlRef('litters.assigned_id')
			->setRequiredJoin('litters')
			->setDataStoreUrl('/litter/list/format/json');

		$ear_mark = new mdb_Search_Field_ComboBox( 'ear_mark' );
		$ear_mark->setLabel ( 'Ear Mark' )
			->setDataStoreUrl('/mouse/suggest/field/ear_mark/format/json');

		$chip = new mdb_Search_Field_ComboBox( 'chip' );
		$chip->setLabel ( 'Chip Number' )
			->setDataStoreUrl('/mouse/suggest/field/chip/format/json');

		$is_chimera = new mdb_Search_Field_Boolean( 'is_chimera' );
		$is_chimera->setLabel ( 'Chimera' );

		$chimera_is_germline = new mdb_Search_Field_Boolean( 'chimera_is_germline' );
		$chimera_is_germline->setLabel ( 'Germline' );

		$chimera_is_founderline = new mdb_Search_Field_Boolean( 'chimera_is_founderline' );
		$chimera_is_founderline->setLabel ( 'Founderline' );

		$chimera_perc_esc = new mdb_Search_Field_Number( 'chimera_perc_esc' );
		$chimera_perc_esc->setLabel ( '% ESC' )
   			->setConstraint('min', 0)
   			->setConstraint('max', 100);

		$chimera_perc_escblast = new mdb_Search_Field_Number( 'chimera_perc_escblast' );
		$chimera_perc_escblast->setLabel ( '% ESC/Blast' )
   			->setConstraint('min', 0)
   			->setConstraint('max', 100);

		$chimera_score = new mdb_Search_Field_Number( 'chimera_score' );
		$chimera_score->setLabel ( 'Chimera Score' )
			->setSqlRef(new Zend_Db_Expr('(IFNULL(mice.chimera_perc_esc,0) + IFNULL(mice.chimera_perc_escblast,0)/2)'))
			->setConstraint('min', 0)
   			->setConstraint('max', 150);

		$modified_by = new mdb_Search_Field_ForeignKey( 'user_id' );
		$modified_by->setLabel ( 'Modified By' )
			->setForeignTable('users')
			->setSqlRef('mice.user_id')
			->setDataStoreUrl('/user/list/empty/yes/format/json');

		$modified_on = new mdb_Search_Field_Date( 'lastmodified' );
		$modified_on->setLabel ( 'Modified On' )
			->setSqlRef(new Zend_Db_Expr('DATE(mice.lastmodified)'));

		$tag = new mdb_Search_Field_ComboBox( 'tag' );
		$tag->setLabel ( 'Tag' )
			->isSubselect(true)
			->setSubselect('select * from tags where tags.ref_table = \''.Tags::MOUSE.'\' and tags.ref_item_id = mice.id')
			->setSqlRef('tags.tag')
			->setDataStoreUrl('/tag/list/format/json');

		$comment = new mdb_Search_Field_Text( 'comment' );
		$comment->setLabel ( 'Comment' )
			->isSubselect(true)
			->setSubselect('select * from comments where comments.ref_table = \''.Comments::MOUSE.'\' and comments.ref_item_id = mice.id')
			->setSqlRef('comments.comment');

		$this->setFields(array($name, $is_alive, $sex, $status, $born_on, $born_ago, $weaned_on, $weaned_ago,
			$terminated_on, $terminated_ago, $strain, $genotype, $generation, $litter, $cage, $protocol,
			$ear_mark, $chip, $is_chimera, $chimera_is_germline, $chimera_is_founderline, $chimera_perc_esc,
			$chimera_perc_escblast, $chimera_score, $tag, $comment, $modified_by, $modified_on));

		// define output fields
		$outName = new mdb_Search_OutputField('assigned_id');
		$outName->setLabel('Mouse ID')
			->setIsDefault(true)
			->setIdSqlExpr('mice.id')
			->setIdSqlAs('mouse_id')
			->setIsText(true)
			->setViewController('mouse');

		$outSex = new mdb_Search_OutputField('sex');
		$outSex->setLabel('Sex')
			->setSqlExpr('mice.sex');

		$outStatus = new mdb_Search_OutputField('status');
		$outStatus->setLabel('Status')
			->setSqlExpr('mice.status');

		$outAlive = new mdb_Search_OutputField('alive');
		$outAlive->setLabel('Alive')
			->setType(mdb_Search_OutputField::TYPE_BOOLEAN)
			->setSqlExpr('mice.is_alive')
			->setSqlAs('mouse_is_alive');

		$outBornOn = new mdb_Search_OutputField('born_on');
		$outBornOn->setLabel('Born On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('mice.born_on')
			->setSqlAs('mice_born_on');

		$outBornAgo = new mdb_Search_OutputField('born_ago');
		$outBornAgo->setLabel('Born (days ago)')
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(mice.born_on)')
			->setSqlAs('mice_born_ago');

		$outWeanedOn = new mdb_Search_OutputField('weaned_on');
		$outWeanedOn->setLabel('Weaned On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('mice.weaned_on')
			->setSqlAs('mice_weaned_on');

		$outWeanedAgo = new mdb_Search_OutputField('weaned_ago');
		$outWeanedAgo->setLabel('Weaned (days ago)')
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(mice.weaned_on)')
			->setSqlAs('mice_weaned_ago');

		$outTerminatedOn = new mdb_Search_OutputField('terminated_on');
		$outTerminatedOn->setLabel('Terminated On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('mice.terminated_on')
			->setSqlAs('mice_terminated_on');

		$outTerminatedAgo = new mdb_Search_OutputField('terminated_ago');
		$outTerminatedAgo->setLabel('Termin. (days ago)')
			->setSqlExpr('TO_DAYS(CURDATE()) - TO_DAYS(mice.terminated_on)')
			->setSqlAs('mice_terminated_ago');

		$outGenotype = new mdb_Search_OutputField('genotype');
		$outGenotype->setLabel('Genotype')
			->setSqlExpr('mice.genotype');

		$outGeneration = new mdb_Search_OutputField('generation');
		$outGeneration->setLabel('Generation')
			->setSqlExpr('mice.generation');

		$outStrain = new mdb_Search_OutputField('strain_name');
		$outStrain->setLabel('Strain')
			->setSqlExpr('strains.strain_name')
			->setSqlAs('mouse_strain_name')
			->setIdSqlExpr('mice.strain_id')
			->setIdSqlAs('mice_strain_id')
			->setRequiredJoin('strains')
			->setViewController('strain');

		$outPromoter = new mdb_Search_OutputField('strain_promoter');
		$outPromoter->setLabel('Strain: Mini/MaxiP')
			->setSqlExpr('strains.promoter')
			->setRequiredJoin('strains');

		$outLitter = new mdb_Search_OutputField('litter_assigned_id');
		$outLitter->setLabel('Litter')
			->setSqlExpr('litters.assigned_id')
			->setSqlAs('litter_assigned_id')
			->setIdSqlExpr('mice.litter_id')
			->setIdSqlAs('mice_litter_id')
			->setIsText(true)
			->setRequiredJoin('litters')
			->setViewController('litter');

		$outCage = new mdb_Search_OutputField('cage_assigned_id');
		$outCage->setLabel('Cage')
			->setSqlExpr('cages.assigned_id')
			->setSqlAs('cages_assigned_id')
			->setIdSqlExpr('mice.cage_id')
			->setIdSqlAs('mice_cage_id')
			->setIsText(true)
			->setRequiredJoin('cages')
			->setViewController('cage');

		$outProtocol = new mdb_Search_OutputField('mice_protocol_name');
		$outProtocol->setLabel('Protocol')
			->setSqlExpr('protocols.protocol_name')
			->setSqlAs('mice_protocol_name')
			->setIdSqlExpr('mice.protocol_id')
			->setIdSqlAs('mice_protocol_id')
			->setIsText(true)
			->setRequiredJoin('protocols')
			->setViewController('protocol');

		$outEarmark = new mdb_Search_OutputField('ear_mark');
		$outEarmark->setLabel('Ear Mark')
			->setSqlExpr('mice.ear_mark');

		$outChip = new mdb_Search_OutputField('chip');
		$outChip->setLabel('Chip Number')
			->setSqlExpr('mice.chip');

		$outIsChimera = new mdb_Search_OutputField('is_chimera');
		$outIsChimera->setLabel('Is Chimera')
			->setType(mdb_Search_OutputField::TYPE_BOOLEAN)
			->setSqlAs('mouse_is_chimera');

		$outChimeraIsFounderline = new mdb_Search_OutputField('chimera_is_founderline');
		$outChimeraIsFounderline->setLabel('Founderline')
			->setType(mdb_Search_OutputField::TYPE_BOOLEAN)
			->setSqlAs('mouse_chimera_is_founderline');

		$outChimeraIsGermline = new mdb_Search_OutputField('chimera_is_germline');
		$outChimeraIsGermline->setLabel('Germline')
			->setType(mdb_Search_OutputField::TYPE_BOOLEAN)
			->setSqlAs('mouse_chimera_is_germline');

		$outChimeraPercEsc = new mdb_Search_OutputField('chimera_perc_esc');
		$outChimeraPercEsc->setLabel('% ESC')
			->setSqlExpr('mice.chimera_perc_esc');

		$outChimeraPercEscBlast = new mdb_Search_OutputField('chimera_perc_escblast');
		$outChimeraPercEscBlast->setLabel('% ESC/Blast')
			->setSqlExpr('mice.chimera_perc_escblast');

		$outChimeraScore = new mdb_Search_OutputField('chimera_score');
		$outChimeraScore->setLabel('Chimera Score')
			->setSqlExpr('(IFNULL(mice.chimera_perc_esc,0) + IFNULL(mice.chimera_perc_escblast,0)/2)')
			->setSqlAs('chimera_score');

		$outModifiedBy = new mdb_Search_OutputField('username');
		$outModifiedBy->setLabel('Modified By')
			->setSqlExpr('users.username')
			->setSqlAs('username')
			->setRequiredJoin('users');

		$outModifiedOn = new mdb_Search_OutputField('lastmodified');
		$outModifiedOn->setLabel('Modified On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('mice.lastmodified')
			->setSqlAs('mice_lastmodified');

		$this->setAvailableOutputFields(array($outName, $outAlive, $outSex, $outStatus, $outStrain, $outPromoter,
			$outBornOn, $outBornAgo, $outWeanedOn, $outWeanedAgo, $outTerminatedOn, $outTerminatedAgo, $outGenotype, $outGeneration,
			$outLitter, $outCage, $outProtocol, $outEarmark, $outChip, $outIsChimera, $outChimeraIsGermline, $outChimeraIsFounderline,
			$outChimeraPercEsc, $outChimeraPercEscBlast, $outChimeraScore, $outModifiedBy, $outModifiedOn));

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

		$select->from('mice', null);
		foreach ($requiredTables as $table) {
			switch ($table) {
				case 'users':
					$select->joinLeft ( 'users', 'users.id = mice.user_id', null);
					break;
				case 'litters':
					$select->joinLeft ( 'litters', 'litters.id = mice.litter_id', null);
					break;
				case 'cages':
					$select->joinLeft ( 'cages', 'cages.id = mice.cage_id', null);
					break;
				case 'strains':
					$select->joinLeft ( 'strains', 'strains.id = mice.strain_id', null);
					break;
				case 'protocols':
					$select->joinLeft ( 'protocols', 'protocols.id = mice.protocol_id', null);
					break;
				default:
					throw new Exception('Unknown required join table');
			}
		}

		$select->order('mice.assigned_id');
		return $select;
	}
}