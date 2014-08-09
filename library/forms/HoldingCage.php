<?php

class forms_HoldingCage extends Zend_Dojo_Form  {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'holding_cage' )
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

		$assigned_id = new Zend_Dojo_Form_Element_ValidationTextBox( 'assigned_id' );
		$assigned_id->setLabel ( 'Cage ID' )
			->setRequired( true )
			->addPrefixPath('mdb_Form_Decorator', 'mdb/Form/Decorator/', 'decorator')
			->addFilter( 'StripTags' )
			->addFilter( 'StringTrim' )
			->addValidator( 'NotEmpty' )
			->addValidator(new mdb_Validate_UniqueValue('cages', null, 'id', 'assigned_id'));

		$active = new Zend_Dojo_Form_Element_CheckBox('active');
		$active->setLabel ( 'Active' );
        $active->removeDecorator('HtmlTag');
        $active->getDecorator('Label')->setOptions(array('tag' => 'span', 'placement' => Zend_Form_Decorator_Abstract::APPEND));
        $active->getDecorator('Label')->getPlacement();
        $active->addDecorator('HtmlTag', array('tag' => 'div'));

		$set_up_on = new Zend_Dojo_Form_Element_DateTextBox ( 'set_up_on' );
		$set_up_on->setLabel ( 'Set Up On' )
			->setOptions(array('datePattern' => 'yyMMdd'));

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

		$submit = new Zend_Dojo_Form_Element_SubmitButton( 'submit' );
		$submit->setLabel('Save')
			->setAttrib ( 'id', 'submitbutton' )
			->removeDecorator('DtDdWrapper');

		$delete = new Zend_Dojo_Form_Element_Button('delete');
		$delete->setOptions(array('onClick' => "dijit.byId('deleteDialog').show()"));
		$delete->setLabel('Delete...')
			->setAttrib ( 'id', 'deletebutton' )
			->removeDecorator('DtDdWrapper');

        $this->addElements ( array ($id, $user_id, $lastmodified, $assigned_id, $active, $set_up_on,
			$protocol_id, $submit, $delete) );

		$this->addDisplayGroup ( array ('assigned_id', 'active', 'set_up_on'), 'left' );
		$this->addDisplayGroup( array ('protocol_id'), 'right' );
		$this->addDisplayGroup( array ('submit', 'delete'), 'bottom' );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements()));
		$this->getDisplayGroup('left')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10')));
		$this->getDisplayGroup('right')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10 last')));
		$this->getDisplayGroup('bottom')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-20 last', 'style' => 'margin-top: 1em; margin-bottom: 1em;')));

	}
}
