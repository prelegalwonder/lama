<?php

class forms_Login extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct( $options );

		$this->setName( 'login' )
			->setAction('')
			->setMethod('post');

		$username = new Zend_Dojo_Form_Element_ValidationTextBox( 'username' );
		$username->setLabel( 'Username' )
			->setRequired( true )
			->addFilter( 'StripTags' )
			->addFilter( 'StringTrim' )
			->addValidator( 'NotEmpty' );

		$password = new Zend_Dojo_Form_Element_PasswordTextBox( 'password' );
		$password->setLabel( 'Password' )
			->setRequired( true )
			->addValidator ( 'NotEmpty' );

		$redir_uri = new Zend_Form_Element_Hidden( 'redir_uri' );
		$redir_uri->setDecorators(array('ViewHelper'));

		$submit = new Zend_Dojo_Form_Element_SubmitButton( 'submit' );
		$submit->setAttrib( 'id', 'submitbutton' )
			->setLabel('Log In');

		$this->addElements( array($username, $password, $redir_uri ) );

		$this->addElements( array($submit ) );
	}
}