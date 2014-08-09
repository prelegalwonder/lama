<?php

abstract class mdb_Search_Field_Abstract {

    protected $_htmlId;
    protected $_descriptiveName;
    protected $_table;
    protected $_sqlRef;
    protected $_label;
    protected $_queryOptions = array();
    protected $_selectedOption;
    protected $_subForm = null;
	protected $_valueInput;
    protected $_requiredJoin = null;

    public function __construct($id) {
        if (! $id) {
            throw new Exception('must specify html id');
        }
        $this->_htmlId = $id;
    }

    public function setLabel($label) {
        $this->_label = $label;
        return $this;
    }

    public function setSqlRef($sql) {
        $this->_sqlRef = $sql;
        return $this;
    }

    public function getSqlRef() {
        if (isset($this->_sqlRef)) {
            return $this->_sqlRef;
        } else {
            return $this->_htmlId;
        }
    }
    public function getLabel() {
        return $this->_label;
    }

    public function getSubForm() {

        if (null === $this->_subForm) {
            $subForm = new Zend_Form_SubForm();

            $subForm->setName($this->getId());
            $subForm->removeDecorator('HtmlTag');
            $subForm->removeDecorator('DtDdWrapper');
            $subForm->removeDecorator('Fieldset');
            $subForm->addDecorator('Htmltag', array('tag' => 'div', 'class' => 'span-20 last'));

            $label = new Zend_Form_Element_Hidden('label');
            $label->setLabel($this->getLabel());
            $label->getDecorator('Label')->setOption('tag', 'span');
            $label->removeDecorator('HtmlTag');
            $label->addDecorator('HtmlTag', array('tag' => 'div', 'style' => 'display:inline;', 'class' => 'span-3'));

            $options = new Zend_Dojo_Form_Element_FilteringSelect('options', array('style' => 'width:140px;'));
            $options->setMultiOptions($this->_queryOptions);
            $options->removeDecorator('HtmlTag');
            $options->removeDecorator('Label');
            $options->addDecorator('HtmlTag', array('tag' => 'div', 'style' => 'display:inline;'));

            $subForm->addElements(array($label, $options));

            $value = $this->valueInput();
            if (is_object($value)) {
                $subForm->addElement($value);
            }

            $this->_subForm = $subForm;
        }
        return $this->_subForm;
    }

    public function getId() {
        return $this->_htmlId;
    }

    // return sql where snippet
    abstract public function assembleWhere();

    // returns Zend_Form_Element used to input data (text box, filtering select, etc)
    abstract public function valueInput();

    public function setRequiredJoin($table) {
        $this->_requiredJoin = $table;
        return $this;
    }

    public function getRequiredJoin() {
        return $this->_requiredJoin;
    }

}