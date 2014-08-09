<?php
class forms_UserSettings extends Zend_Dojo_Form
{

	public function __construct ($options = null) {

		parent::__construct($options);
		$this->setName('user_settings')->setAction('')->setMethod('post');
		$this->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');
		$search_go_input_suggest = new Zend_Dojo_Form_Element_CheckBox('search_go_input_suggest');
		$search_go_input_suggest->setLabel('Suggest existing item IDs in "Enter an Item ID:" search field');
		$search_go_input_suggest->removeDecorator('HtmlTag');
		$search_go_input_suggest->getDecorator('Label')->setOptions(array('tag' => 'span' , 'placement' => Zend_Form_Decorator_Abstract::APPEND));
		$search_go_input_suggest->getDecorator('Label')->getPlacement();
		$search_go_input_suggest->addDecorator('HtmlTag', array('tag' => 'div'));

		$interface_table_expand = new Zend_Dojo_Form_Element_CheckBox('interface_table_expand');
		$interface_table_expand->setLabel('Expand tables horizontally to fit content if needed');
		$interface_table_expand->removeDecorator('HtmlTag');
		$interface_table_expand->getDecorator('Label')->setOptions(array('tag' => 'span' , 'placement' => Zend_Form_Decorator_Abstract::APPEND));
		$interface_table_expand->getDecorator('Label')->getPlacement();
		$interface_table_expand->addDecorator('HtmlTag', array('tag' => 'div'));

		$submit = new Zend_Dojo_Form_Element_SubmitButton('submit');
		$submit->setLabel('Save')->setAttrib('id', 'savesettings')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-20')));
		$this->addElements(array($search_go_input_suggest, $interface_table_expand));
		$this->addDisplayGroup(array('search_go_input_suggest', 'interface_table_expand'), 'user_interface', array('legend' => 'User Interface'));
		$this->addElement($submit);
	}
}