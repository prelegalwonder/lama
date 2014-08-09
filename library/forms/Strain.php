<?php

class forms_Strain extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'strain' )
			->setAction('')
			->setMethod('post');

		$this->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$user_id = new Zend_Form_Element_Hidden ( 'user_id' );
		$user_id->setDecorators(array('ViewHelper'));

		$lastmodified = new Zend_Form_Element_Hidden ( 'lastmodified' );
		$lastmodified->setDecorators(array('ViewHelper'));

		$strain_name = new Zend_Dojo_Form_Element_ValidationTextBox ( 'strain_name' );
		$strain_name->setLabel ( 'Strain ID' )
			->setRequired ( true )
			->addPrefixPath('mdb_Form_Decorator', 'mdb/Form/Decorator/', 'decorator')
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' )
			->addValidator(new mdb_Validate_UniqueValue('strains', null, 'id', 'strain_name'));

		$bems = new Zend_Dojo_Form_Element_TextBox( 'bems' );
		$bems->setLabel ( 'bEMS #' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$pems = new Zend_Dojo_Form_Element_TextBox( 'pems' );
		$pems->setLabel ( 'pEMS #' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$promoter = new Zend_Dojo_Form_Element_TextBox ( 'promoter' );
		$promoter->setLabel ( 'Mini/MaxiP' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$esc_line = new Zend_Dojo_Form_Element_ComboBox('esc_line');
		$esc_line->setLabel('ESC Line')
			->setStoreId('esc_line_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/strain/suggest/field/esc_line/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$backbone_pems = new Zend_Dojo_Form_Element_TextBox ( 'backbone_pems' );
		$backbone_pems->setLabel ( 'Backbone pEMS' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$reporter = new Zend_Dojo_Form_Element_TextBox ( 'reporter' );
		$reporter->setLabel ( 'Reporter' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$jax_strain_name = new Zend_Dojo_Form_Element_TextBox ( 'jax_strain_name' );
		$jax_strain_name->setLabel ( 'JAX strain name' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$jax_store_number = new Zend_Dojo_Form_Element_TextBox ( 'jax_store_number' );
		$jax_store_number->setLabel ( 'JAX store number' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$jax_generation = new Zend_Dojo_Form_Element_TextBox ( 'jax_generation' );
		$jax_generation->setLabel ( 'JAX generation' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$jax_genotype = new Zend_Dojo_Form_Element_TextBox ( 'jax_genotype' );
		$jax_genotype->setLabel ( 'JAX genotype' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$jax_url = new Zend_Dojo_Form_Element_TextBox ( 'jax_url' );
		$jax_url->setLabel ( 'JAX URL' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$description = new Zend_Dojo_Form_Element_Textarea ( 'description' );
		$description->setLabel ( 'Strain Description' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$grant = new Zend_Dojo_Form_Element_ComboBox('grant');
		$grant->setLabel('Grant')
			->setStoreId('grant_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/strain/suggest/field/grant/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$assigned_user_id = new Zend_Dojo_Form_Element_FilteringSelect('assigned_user_id');
		$assigned_user_id->setLabel('Assigned To')
			->addValidator(new mdb_Validate_ForeignKey('users'))
			->setStoreId('assigned_user_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/user/list/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$location = new Zend_Dojo_Form_Element_ComboBox('location');
		$location->setLabel('Location')
			->setStoreId('location_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/strain/suggest/field/location/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submit' );
		$submit->setLabel('Save')
			->setAttrib ( 'id', 'submitbutton' )
			->removeDecorator('DtDdWrapper');

		$delete = new Zend_Dojo_Form_Element_Button('delete');
		$delete->setOptions(array('onClick' => "dijit.byId('deleteDialog').show()"));
		$delete->setLabel('Delete...')
			->setAttrib ( 'id', 'deletebutton' )
			->removeDecorator('DtDdWrapper');

		$this->addElements ( array ($id, $user_id, $lastmodified, $strain_name, $pems,  $bems, $promoter, $esc_line, $backbone_pems,
			$reporter, $jax_strain_name, $jax_store_number, $jax_generation, $jax_genotype, $jax_url, $assigned_user_id, $grant, $location ) );

		$this->addDisplayGroup ( array ('strain_name', 'pems', 'bems', 'promoter', 'esc_line', 'backbone_pems', 'reporter' ), 'left' );

        $this->addDisplayGroup( array ('jax_strain_name', 'jax_store_number', 'jax_generation', 'jax_genotype', 'jax_url', 'assigned_user_id', 'grant', 'location' ), 'right' );

		$this->addElements ( array($description, $submit, $delete) );

		$this->addDisplayGroup( array ('description', 'submit', 'delete'), 'bottom' );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements()));
		$this->getDisplayGroup('left')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10')));
		$this->getDisplayGroup('right')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10 last')));
		$this->getDisplayGroup('bottom')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-20 last')));

	}
}