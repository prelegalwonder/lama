<?php

class forms_LitterParents extends Zend_Dojo_Form {

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

		$lastmodified = new Zend_Form_Element_Hidden ( 'lastmodified' );
		$lastmodified->setDecorators(array('ViewHelper'));

		$father_id = new Zend_Dojo_Form_Element_FilteringSelect('father_id');
		$father_id->setLabel('Father')
			->setStoreId('father_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/mouse/list/sex/m/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$mother_id = new Zend_Dojo_Form_Element_FilteringSelect('mother_id');
		$mother_id->setLabel('Mother')
			->setStoreId('mother_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/mouse/list/sex/f/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$mother2_id = new Zend_Dojo_Form_Element_FilteringSelect('mother2_id');
		$mother2_id->setLabel('Mother 2')
			->setStoreId('mother2_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/mouse/list/sex/f/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$mother3_id = new Zend_Dojo_Form_Element_FilteringSelect('mother3_id');
		$mother3_id->setLabel('Mother 3')
			->setStoreId('mother3_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/mouse/list/sex/f/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$lastmodified = new Zend_Form_Element_Hidden ( 'lastmodified' );
		$lastmodified->setDecorators(array('ViewHelper'));

		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submit' );
		$submit->setLabel('Save')
			->setAttrib ( 'id', 'submitbutton' );

		$this->addElements ( array ($id, $user_id, $lastmodified, $father_id, $mother_id, $mother2_id, $mother3_id, $submit) );
	}
}