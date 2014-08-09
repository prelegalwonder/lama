<?php

class forms_Comment extends Zend_Dojo_Form {
	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'comment_form' )
			->setAction('')
			->setMethod('post');

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$ref_table = new Zend_Form_Element_Hidden ( 'ref_table' );
		$ref_table->setDecorators(array('ViewHelper'));
		$ref_item_id = new Zend_Form_Element_Hidden ( 'ref_item_id' );
		$ref_item_id->setDecorators(array('ViewHelper'));

		$comment = new Zend_Dojo_Form_Element_Textarea( 'comment' );
		$comment->setLabel ( 'Comment' )
			->setRequired ( true )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' );

//		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submitComment' );
		$submit = new Zend_Dojo_Form_Element_Button ( 'submitComment' );
		$submit->setOptions(array(
			'onClick' => 'dojo.byId("comment_form").submit()',
//			'onClick' => 'dojo.xhrPost({form:"comment_form", load:function(response, ioArgs) {dijit.byId("comment_pane").refresh(); return response;}, error:function(response, ioArgs) {alert(response); return response;} })',
		));

		$submit->setLabel('Save')
			->removeDecorator('DtDdWrapper');

		$this->addElements ( array ($comment, $submit, $id, $ref_table, $ref_item_id ) );

	}
}