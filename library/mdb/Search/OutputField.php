<?php

class mdb_Search_OutputField {

	const TYPE_DATETIME = 'datetime';
	const TYPE_BOOLEAN = 'bool';

    protected $_name;
    protected $_label;
    protected $_sqlExpr;
    protected $_sqlAs;
    protected $_viewController;
    protected $_isDefault = false;
    protected $_checkbox = null;
    protected $_idSqlExpr;
    protected $_idSqlAs;
    protected $_isText = false;
    protected $_type;
    protected $_requiredJoin;

    public function __construct ($name)
    {
        $this->_name = $name;
    }

    public function setLabel($label) {
        $this->_label = $label;
        return $this;
    }

    public function setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function setSqlExpr($sql) {
        $this->_sqlExpr = $sql;
        return $this;
    }

    public function setSqlAs($sql) {
        $this->_sqlAs = $sql;
        return $this;
    }

    public function getSqlExpr() {
        if (isset($this->_sqlExpr)) {
            return $this->_sqlExpr;
        } else {
            return $this->_name;
        }
    }

    public function getSqlAs() {
        if (isset($this->_sqlAs)) {
            return $this->_sqlAs;
        } else {
            return $this->getSqlExpr();
        }
    }

    public function getName() {
        return $this->_name;
    }

    public function getLabel() {
        return $this->_label;
    }

    public function setViewController($controller) {
        $this->_viewController = $controller;
        return $this;
    }

    public function getViewController() {
        return $this->_viewController;
    }

    public function __toString() {
        return $this->getSqlExpr();
    }

    public function setIsDefault($flag) {
        $this->_isDefault = (bool) $flag;
        return $this;
    }

    public function isDefault() {
        return $this->_isDefault;
    }

    public function select($flag) {
       $this->getElement()->checked = (bool) $flag;
    }

    public function getElement() {

        if (null === $this->_checkbox) {
            $checkbox = new Zend_Dojo_Form_Element_CheckBox($this->getName());
            $checkbox->removeDecorator('HtmlTag');
            $checkbox->getDecorator('Label')->setOptions(array('tag' => 'span', 'placement' => Zend_Form_Decorator_Abstract::APPEND));
            $checkbox->getDecorator('Label')->getPlacement();
            $checkbox->addDecorator('HtmlTag', array('tag' => 'div', 'style' => 'inline-block', 'class' => 'span-4'));
            $checkbox->setLabel($this->getLabel());

            $this->_checkbox = $checkbox;
        }

        return $this->_checkbox;
    }

    public function isSelected() {
        return $this->getElement()->checked;
    }

    public function setIdSqlExpr($sql) {
        $this->_idSqlExpr = $sql;
        return $this;
    }

    public function getIdSqlExpr() {
        return $this->_idSqlExpr;
    }

    public function setIdSqlAs($sql) {
        $this->_idSqlAs = $sql;
        return $this;
    }

    public function getIdSqlAs() {
        return $this->_idSqlAs;
    }

    public function setIsText($flag) {
        $this->_isText = $flag;
        return $this;
    }

    public function getIsText() {
        return $this->_isText;
    }

    public function setType($type) {
        $this->_type = $type;
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    public function setRequiredJoin($table) {
        $this->_requiredJoin = $table;
        return $this;
    }

    public function getRequiredJoin() {
        return $this->_requiredJoin;
    }
}