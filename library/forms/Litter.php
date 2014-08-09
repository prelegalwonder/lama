<?php

class forms_Litter extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'litter' )
			->setAction('')
			->setMethod('post');

		$this->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');

		$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$user_id = new Zend_Form_Element_Hidden ( 'user_id' );
		$user_id->setDecorators(array('ViewHelper'));

		$breeding_cage_id = new Zend_Form_Element_Hidden ( 'breeding_cage_id' );
		$breeding_cage_id->setDecorators(array('ViewHelper'));

		$father_id = new Zend_Form_Element_Hidden ( 'father_id' );
		$father_id->setDecorators(array('ViewHelper'));

		$mother_id = new Zend_Form_Element_Hidden ( 'mother_id' );
		$mother_id->setDecorators(array('ViewHelper'));

		$mother2_id = new Zend_Form_Element_Hidden ( 'mother2_id' );
		$mother2_id->setDecorators(array('ViewHelper'));

		$mother3_id = new Zend_Form_Element_Hidden ( 'mother3_id' );
		$mother3_id->setDecorators(array('ViewHelper'));

		$lastmodified = new Zend_Form_Element_Hidden ( 'lastmodified' );
		$lastmodified->setDecorators(array('ViewHelper'));

		$assigned_id = new Zend_Dojo_Form_Element_ValidationTextBox ( 'assigned_id' );
		$assigned_id->setLabel ( 'Litter ID' )
			->setRequired ( true )
			->addPrefixPath('mdb_Form_Decorator', 'mdb/Form/Decorator/', 'decorator')
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' )
			->addValidator(new mdb_Validate_UniqueValue('litters', null, 'id', 'assigned_id'));

		$born_on = new Zend_Dojo_Form_Element_DateTextBox ( 'born_on' );
		$born_on->setLabel ( 'Born on' )
			->setOptions(array('datePattern' => 'yyMMdd'));

		$weaned_on = new Zend_Dojo_Form_Element_DateTextBox ( 'weaned_on' );
		$weaned_on->setLabel ( 'Weaned on' )
			->setOptions(array('datePattern' => 'yyMMdd'));

		$total_pups = new Zend_Dojo_Form_Element_NumberTextBox ( 'total_pups' );
		$total_pups->setLabel ( 'Total pups' )
			->setRequired ( false )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( new Zend_Validate_Int() );

		$alive_pups = new Zend_Dojo_Form_Element_NumberTextBox ( 'alive_pups' );
		$alive_pups->setLabel ( 'Alive pups' )
			->setRequired ( false )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( new Zend_Validate_Int() );

		$strain_id = new Zend_Dojo_Form_Element_FilteringSelect('strain_id');
		$strain_id->setLabel('Strain')
			->setStoreId('strain_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/strain/list/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$generation = new Zend_Dojo_Form_Element_ComboBox('generation');
		$generation->setLabel('Generation')
			->setStoreId('generation_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/mouse/suggest/field/generation/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$protocol_id = new Zend_Dojo_Form_Element_FilteringSelect('protocol_id');
		$protocol_id->setLabel('Protocol')
			->setStoreId('breeding_cage_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/protocol/list/empty/yes/format/json'))
			->setAutocomplete(false)
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

		$this->addElements ( array ($id, $user_id, $lastmodified, $assigned_id, $breeding_cage_id, $weaned_on,
		$father_id, $mother_id, $mother2_id, $mother3_id, $born_on, $strain_id, $generation, $protocol_id, $total_pups, $alive_pups, $submit, $delete) );

		$this->addDisplayGroup ( array ('assigned_id', 'born_on', 'weaned_on'), 'left' );
		$this->addDisplayGroup( array ('strain_id', 'generation', 'protocol_id', 'total_pups', 'alive_pups'), 'right' );
		$this->addDisplayGroup( array ('submit', 'delete'), 'bottom' );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements()));
		$this->getDisplayGroup('left')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10')));
		$this->getDisplayGroup('right')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10 last')));
		$this->getDisplayGroup('bottom')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-20 last', 'style' => 'margin-top: 1em; margin-bottom: 1em;')));
	}
}