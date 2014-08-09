<?php

class mdb_Search_Strain extends mdb_Search_Abstract {

    public function __construct ($htmld, $params = array())
    {
        // define query fields
        $name = new mdb_Search_Field_Text('strain_name');
        $name->setLabel('Strain ID');

		$bems = new mdb_Search_Field_ComboBox( 'bems' );
		$bems->setLabel ( 'bEMS #' )
		    ->setDataStoreUrl('/strain/suggest/field/bems/format/json');

		$pems = new mdb_Search_Field_ComboBox( 'pems' );
		$pems->setLabel ( 'pEMS #' )
		    ->setDataStoreUrl('/strain/suggest/field/pems/format/json');

		$backbone_pems = new mdb_Search_Field_ComboBox( 'backbone_pems' );
		$backbone_pems->setLabel ( 'Backbone pEMS' )
		    ->setDataStoreUrl('/strain/suggest/field/backbone_pems/format/json');

		$esc_line = new mdb_Search_Field_ComboBox( 'esc_line' );
		$esc_line->setLabel ( 'ESC Line' )
		    ->setDataStoreUrl('/strain/suggest/field/esc_line/format/json');

		$promoter = new mdb_Search_Field_ComboBox( 'promoter' );
		$promoter->setLabel ( 'Mini/MaxiP' )
		    ->setDataStoreUrl('/strain/suggest/field/promoter/format/json');

		$reporter = new mdb_Search_Field_ComboBox( 'reporter' );
		$reporter->setLabel ( 'Reporter' )
		    ->setDataStoreUrl('/strain/suggest/field/reporter/format/json');

		$jax_strain_name = new mdb_Search_Field_ComboBox( 'jax_strain_name' );
		$jax_strain_name->setLabel ( 'JAX Strain Name' )
		    ->setDataStoreUrl('/strain/suggest/field/jax_strain_name/format/json');

		$jax_store_number = new mdb_Search_Field_ComboBox( 'jax_store_number' );
		$jax_store_number->setLabel ( 'JAX Store Number' )
		    ->setDataStoreUrl('/strain/suggest/field/jax_store_number/format/json');

		$jax_generation = new mdb_Search_Field_ComboBox( 'jax_generation' );
		$jax_generation->setLabel ( 'JAX Generation' )
		    ->setDataStoreUrl('/strain/suggest/field/jax_generation/format/json');

		$jax_genotype = new mdb_Search_Field_ComboBox( 'jax_genotype' );
		$jax_genotype->setLabel ( 'JAX Generation' )
		    ->setDataStoreUrl('/strain/suggest/field/jax_genotype/format/json');

		$jax_url = new mdb_Search_Field_ComboBox( 'jax_url' );
		$jax_url->setLabel ( 'JAX URL' )
		    ->setDataStoreUrl('/strain/suggest/field/jax_url/format/json');

		$description = new mdb_Search_Field_Text( 'description' );
		$description->setLabel ( 'Description' );

		$assigned_user_id = new mdb_Search_Field_ForeignKey( 'assigned_user_id' );
		$assigned_user_id->setLabel ( 'Assigned To' )
		    ->setForeignTable('users')
		    ->setDataStoreUrl('/user/list/empty/yes/format/json');

		$grant = new mdb_Search_Field_ComboBox( 'grant' );
		$grant->setLabel ( 'Grant' )
		    ->setDataStoreUrl('/strain/suggest/field/grant/format/json');

		$location = new mdb_Search_Field_ComboBox( 'location' );
		$location->setLabel ( 'Location' )
		    ->setDataStoreUrl('/strain/suggest/field/location/format/json');

		$modified_by = new mdb_Search_Field_ForeignKey( 'user_id' );
		$modified_by->setLabel ( 'Modified By' )
		    ->setForeignTable('users')
		    ->setDataStoreUrl('/user/list/empty/yes/format/json');

		$modified_on = new mdb_Search_Field_Date( 'lastmodified' );
		$modified_on->setLabel ( 'Modified On' )
		    ->setSqlRef(new Zend_Db_Expr('DATE(strains.lastmodified)'));

		$tag = new mdb_Search_Field_ComboBox( 'tag' );
		$tag->setLabel ( 'Tag' )
		    ->isSubselect(true)
		    ->setSubselect('select * from tags where tags.ref_table = \''.Tags::STRAIN.'\' and tags.ref_item_id = strains.id')
		    ->setSqlRef('tags.tag')
		    ->setDataStoreUrl('/tag/list/format/json');

		$comment = new mdb_Search_Field_Text( 'comment' );
		$comment->setLabel ( 'Comment' )
		    ->isSubselect(true)
		    ->setSubselect('select * from comments where comments.ref_table = \''.Comments::STRAIN.'\' and comments.ref_item_id = strains.id')
		    ->setSqlRef('comments.comment');

		$this->setFields(array($name, $pems, $bems, $promoter, $esc_line, $backbone_pems, $reporter, $jax_strain_name, $jax_store_number, $jax_generation, $jax_genotype, $jax_url, $grant, $location, $description, $assigned_user_id, $tag, $comment, $modified_by, $modified_on));

		// define output fields
		$outName = new mdb_Search_OutputField('strain_name');
		$outName->setLabel('Strain ID')
		    ->setIsDefault(true)
		    ->setIsText(true)
		    ->setIdSqlExpr('strains.id')
		    ->setIdSqlAs('strains_id')
		    ->setViewController('strain');

		$outBems = new mdb_Search_OutputField('bems');
		$outBems->setLabel('bEMS #');

		$outPems = new mdb_Search_OutputField('pems');
		$outPems->setLabel('pEMS #');

		$outBackbone_pems = new mdb_Search_OutputField( 'backbone_pems' );
		$outBackbone_pems->setLabel ( 'Backbone pEMS' );

		$outEsc_line = new mdb_Search_OutputField( 'esc_line' );
		$outEsc_line->setLabel ( 'ESC Line' );

		$outPromoter = new mdb_Search_OutputField( 'promoter' );
		$outPromoter->setLabel ( 'Mini/MaxiP' );

		$outReporter = new mdb_Search_OutputField( 'reporter' );
		$outReporter->setLabel ( 'Reporter' );

		$outJax_strain_name = new mdb_Search_OutputField( 'jax_strain_name' );
		$outJax_strain_name->setLabel ( 'JAX Strain Name' );

		$outJax_store_number = new mdb_Search_OutputField( 'jax_store_number' );
		$outJax_store_number->setLabel ( 'JAX Store Number' );

		$outJax_generation = new mdb_Search_OutputField( 'jax_generation' );
		$outJax_generation->setLabel ( 'JAX Generation' );

		$outJax_genotype = new mdb_Search_OutputField( 'jax_genotype' );
		$outJax_genotype->setLabel ( 'JAX Generation' );

		$outJax_url = new mdb_Search_OutputField( 'jax_url' );
		$outJax_url->setLabel ( 'JAX URL' );

		$outDescription = new mdb_Search_OutputField( 'description' );
		$outDescription->setLabel ( 'Description' );

		$outGrant = new mdb_Search_OutputField( 'grant' );
		$outGrant->setLabel ( 'Grant' );

		$outLocation = new mdb_Search_OutputField( 'location' );
		$outLocation->setLabel ( 'Location' );

		$outAssignedUser = new mdb_Search_OutputField('assigned_username');
		$outAssignedUser->setLabel('Assigned To')
			->setRequiredJoin('assigned_users')
			->setSqlExpr('assigned_users.username')
		    ->setSqlAs('assigned_username');

		$outModifiedBy = new mdb_Search_OutputField('username');
		$outModifiedBy->setLabel('Modified By')
			->setRequiredJoin('users')
			->setSqlExpr('users.username')
		    ->setSqlAs('username');

		$outModifiedOn = new mdb_Search_OutputField('lastmodified');
		$outModifiedOn->setLabel('Modified On')
			->setType(mdb_Search_OutputField::TYPE_DATETIME)
			->setSqlExpr('strains.lastmodified')
		    ->setSqlAs('strains_lastmodified');

		$this->setAvailableOutputFields(array($outName, $outPems, $outBems, $outPromoter, $outEsc_line, $outBackbone_pems, $outReporter, $outJax_strain_name, $outJax_store_number, $outJax_generation, $outJax_genotype, $outJax_url, $outGrant, $outLocation, $outDescription, $outAssignedUser, $outModifiedBy, $outModifiedOn));

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

		$select->from('strains', null);
		foreach ($requiredTables as $table) {
			switch ($table) {
				case 'users':
					$select->joinLeft ( 'users', 'users.id = strains.user_id', null);
					break;
				case 'assigned_users':
					$select->joinLeft ( 'users as assigned_users', 'assigned_users.id = strains.assigned_user_id', null);
					break;
				default:
					throw new Exception('Unknown required join table');
			}
		}

		$select->order('strain_name');
		return $select;
	}
}