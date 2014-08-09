<?php

class forms_Transfer extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'transfer' )
			->setAction('')
			->setMethod('post');

		$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

		$redirect = new Zend_Form_Element_Hidden ( 'redirect' );
		$redirect->setDecorators(array('ViewHelper'));

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$user_id = new Zend_Form_Element_Hidden ( 'user_id' );
		$user_id->setDecorators(array('ViewHelper'));

		$lastmodified = new Zend_Form_Element_Hidden ( 'lastmodified' );
		$lastmodified->setDecorators(array('ViewHelper'));

		$notes = new Zend_Dojo_Form_Element_Textarea ( 'notes' );
		$notes->setLabel ( 'Reason' )->
			addFilter ( 'StripTags' )->
			addFilter ( 'StringTrim' );

		$mouse_id = new Zend_Dojo_Form_Element_FilteringSelect('mouse_id');
		$mouse_id->setLabel('Mouse')
			->setStoreId('mouse_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/mouse/list/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$to_cage_id = new Zend_Dojo_Form_Element_FilteringSelect('to_cage_id');
		$to_cage_id->setLabel('To Cage')
			->setStoreId('to_cage_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/cage/list/format/json/empty/yes/type/all'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$from_cage_id = new Zend_Dojo_Form_Element_FilteringSelect('from_cage_id');
		$from_cage_id->setLabel('From Cage')
			->setStoreId('from_cage_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/cage/list/format/json/empty/yes/type/all'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$transferred_on = new Zend_Dojo_Form_Element_DateTextBox ( 'transferred_on' );
		$transferred_on->setLabel ( 'Transferred on' )
			->setOptions(array('datePattern' => 'yyMMdd'));

		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submit' );
		$submit->setLabel('Save')
			->setAttrib ( 'id', 'submitbutton' )
			->removeDecorator('DtDdWrapper');

		$delete = new Zend_Dojo_Form_Element_Button('delete');
		$delete->setOptions(array('onClick' => "dijit.byId('deleteDialog').show()"));
		$delete->setLabel('Delete...')
			->setAttrib ( 'id', 'deletebutton' )
			->removeDecorator('DtDdWrapper');


		$this->addElements ( array ($id, $user_id, $lastmodified, $mouse_id, $to_cage_id, $from_cage_id, $transferred_on ) );

		$left_group = $this->addDisplayGroup ( array ('mouse_id', 'transferred_on'), 'local' );
		$left_group->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10'))));

		$right_group = $this->addDisplayGroup( array ('from_cage_id', 'to_cage_id' ), 'jax' );
		$right_group->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10 last'))));

		$notes_subform = new Zend_Form_SubForm();
		$notes_subform->addElement($notes);
		$notes_subform->setDecorators(array( new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_HtmlTag(array('class' => 'span-20'))));

		$this->addSubForm($notes_subform, 'notes_subform');

		$this->addElements ( array($submit, $delete) );

	}
}