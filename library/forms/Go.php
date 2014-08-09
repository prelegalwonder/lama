<?php

class forms_Go extends Zend_Dojo_Form {
	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'go' )
			->setAction(Zend_Controller_Front::getInstance()->getBaseUrl().'/search/go')
			->setMethod('post');
		
		$item = new Zend_Dojo_Form_Element_TextBox( 'item' );
		$item->setLabel ( '' )
			->setRequired ( true )
			->setAttrib('style', 'width:90px; padding:3px;')
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );
			
		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submitGo' );
		$submit->setLabel('Go')
			->setAttrib('style', 'margin-top: 1px;');

		$this->addElements ( array ($item, $submit ) );		
	
	}
}