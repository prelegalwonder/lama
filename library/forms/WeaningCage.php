<?php

class forms_WeaningCage extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'weaning-cage' )
			->setAction('')
			->setMethod('post');

		$this->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');

		$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$user_id = new Zend_Form_Element_Hidden ( 'user_id' );
		$user_id->setDecorators(array('ViewHelper'));

		$litter_id = new Zend_Form_Element_Hidden ( 'litter_id' );
		$litter_id->setDecorators(array('ViewHelper'));

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
		$assigned_id->setLabel ( 'Weaning Cage ID' )
			->setRequired ( true )
			->addPrefixPath('mdb_Form_Decorator', 'mdb/Form/Decorator/', 'decorator')
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' )
			->addValidator(new mdb_Validate_UniqueValue('cages', null, 'id', 'assigned_id'));

		$sex = new Zend_Dojo_Form_Element_RadioButton('sex');
		$sex->setLabel('Sex')
			->setRequired(true)
			->setOptions(array('separator' => ' '))
			->setMultiOptions(array('M' => 'Male', 'F' => 'Female') );
		$sex->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');

		$protocol_id = new Zend_Dojo_Form_Element_FilteringSelect('protocol_id');
		$protocol_id->setLabel('Protocol')
			->setStoreId('protocol_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/protocol/list/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submit' );
		$submit->setLabel('Save')
			->setAttrib ( 'id', 'submitbutton' );

		$this->addElements ( array ($id, $user_id, $lastmodified, $assigned_id, $litter_id,
			$sex, $protocol_id, $submit) );

	}
}