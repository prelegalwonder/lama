<?php

class forms_Protocol extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'protocol' )
			->setAction('')
			->setMethod('post');

		$this->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$user_id = new Zend_Form_Element_Hidden ( 'user_id' );
		$user_id->setDecorators(array('ViewHelper'));

		$lastmodified = new Zend_Form_Element_Hidden ( 'lastmodified' );
		$lastmodified->setDecorators(array('ViewHelper'));

		$protocol_name = new Zend_Dojo_Form_Element_ValidationTextBox ( 'protocol_name' );
		$protocol_name->setLabel ( 'Protocol' )
			->setRequired ( true )
			->addPrefixPath('mdb_Form_Decorator', 'mdb/Form/Decorator/', 'decorator')
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' )
			->addValidator(new mdb_Validate_UniqueValue('protocols', null, 'id', 'protocol_name'));

		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submit' );
		$submit->setLabel('Save')
			->setAttrib ( 'id', 'submitbutton' )
			->removeDecorator('DtDdWrapper');

		$delete = new Zend_Dojo_Form_Element_Button('delete');
		$delete->setOptions(array('onClick' => "dijit.byId('deleteDialog').show()"));
		$delete->setLabel('Delete...')
			->setAttrib ( 'id', 'deletebutton' )
			->removeDecorator('DtDdWrapper');

		$this->addElements ( array ($id, $user_id, $lastmodified, $protocol_name, $submit, $delete) );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10'))));

		$this->addDisplayGroup( array('protocol_name'), 'field_group' );
		$this->addDisplayGroup( array('submit', 'delete' ), 'button_group' );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements()));
		$this->getDisplayGroup('field_group')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('id' => 'field_box', 'class' => 'span-20 last')));
		$this->getDisplayGroup('button_group')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('id' => 'button_box', 'class' => 'span-20 last', 'style' => 'margin-top: 1em; margin-bottom: 1em;')));

	}
}