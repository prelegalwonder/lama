<?php

class forms_User extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'user' )
			->setAction('')
			->setMethod('post');

		$this->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');
		$db = Zend_Db_Table::getDefaultAdapter ();

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$username = new Zend_Dojo_Form_Element_ValidationTextBox ( 'username' );
		$username->setLabel( 'Username' )
			->setRequired( true )
			->addPrefixPath('mdb_Form_Decorator', 'mdb/Form/Decorator/', 'decorator')
			->addFilter( 'StripTags' )
			->addFilter( 'StringTrim' )
			->addValidator( 'NotEmpty' )
			->addValidator(new mdb_Validate_UniqueValue('users', null, 'id', 'username'));

		$password = new Zend_Dojo_Form_Element_PasswordTextBox ( 'password' );
		$password->setLabel ( 'Password' );

		$rolesList = array();
		foreach ($db->fetchAll('select id, role_name from roles') as $role) {
			$rolesList[$role['id']] = $role['role_name'];
		}
		$role_id = new Zend_Dojo_Form_Element_FilteringSelect('role_id');
		$role_id->setLabel('Role')
		    ->setMultiOptions($rolesList);

		$active = new Zend_Dojo_Form_Element_CheckBox('active');
		$active->setLabel( 'Active' );

		$email = new Zend_Dojo_Form_Element_ValidationTextBox( 'email' );
		$email->setLabel ( 'Email' )
			->addFilter( 'StripTags' )
			->addFilter( 'StringTrim' );

		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submit' );
		$submit->setLabel('Save')
			->setAttrib( 'id', 'submitbutton' )
			->removeDecorator('DtDdWrapper');

		$delete = new Zend_Dojo_Form_Element_Button('delete');
		$delete->setOptions(array('onClick' => "dijit.byId('deleteDialog').show()"));
		$delete->setLabel('Delete...')
			->setAttrib ( 'id', 'deletebutton' )
			->removeDecorator('DtDdWrapper');

		$this->addElements( array ($id, $username, $password, $role_id, $active, $email, $submit, $delete) );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10'))));

		$this->addDisplayGroup( array('username', 'password', 'role_id', 'active', 'email'), 'field_group' );
		$this->addDisplayGroup( array('submit', 'delete' ), 'button_group' );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements()));
		$this->getDisplayGroup('field_group')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('id' => 'field_box', 'class' => 'span-20 last')));
		$this->getDisplayGroup('button_group')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('id' => 'button_box', 'class' => 'span-20 last', 'style' => 'margin-top: 1em; margin-bottom: 1em;')));

	}
}