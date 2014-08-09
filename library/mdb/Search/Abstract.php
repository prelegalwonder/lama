<?php

abstract class mdb_Search_Abstract
{
    protected $_htmlId;
    protected $_form = null;
    protected $_fields = array();
    protected $_select;
    protected $_availableOutputFields = array();
    protected $_limit;

    public function __construct ($htmld, $params = array())
    {
        $this->_htmlId = $htmld;
        if (count($params)) {
            $this->setParams($params);
        }
    }

    public function __toString ()
    {
        return $this->getForm()->render();
    }

    public function setParams ($params)
    {
        $this->getForm()->populate($params);
        return $this;
    }

    public function getForm ()
    {
        if (null === $this->_form) {
            $form = new Zend_Dojo_Form();

            $form->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');

            $form->setName($this->_htmlId)
				->setAction('')
	            ->setMethod('post');
            $form->removeDecorator('HtmlTag');

			$submit_reason = new Zend_Form_Element_Hidden( 'search_submit_reason' );
			$submit_reason->setDecorators(array('ViewHelper'));
			$form->addElement($submit_reason);

			$search_title = new Zend_Dojo_Form_Element_TextBox('search_title');
            $search_title->removeDecorator('HtmlTag');
            $search_title->getDecorator('Label')->setOption('tag', 'span');
            $search_title->setLabel ( 'Title:' )
    			->addFilter ( 'StringTrim' )
    			->setAttrib('style', 'width:700px;')
    			->addValidator ( 'NotEmpty' )
    			->addValidator(new mdb_Validate_UniqueValue('searches', null, 'id', 'title'));
            $form->addElement($search_title);

            $search_public = new Zend_Dojo_Form_Element_CheckBox ( 'search_public' );
            $search_public->setLabel ( 'Allow other users to see this search' );
            $search_public->removeDecorator('HtmlTag');
            $search_public->getDecorator('Label')->setOptions(array('tag' => 'span', 'placement' => Zend_Form_Decorator_Abstract::APPEND));
            $search_public->getDecorator('Label')->getPlacement();
            $search_public->addDecorator('HtmlTag', array('tag' => 'div'));
            $form->addElement($search_public);

		    $searchForm = new Zend_Form_SubForm();
            $searchForm->setLegend('Search Fields');
		    $searchForm->removeDecorator('DtDdWrapper');
		    $searchForm->removeDecorator('HtmlTag');
		    $searchForm->setAttrib('style', 'padding: 0; margin-top:1.5em; border:0;');
		    $form->addSubForm($searchForm, 'search_details');

            foreach ($this->_fields as $field) {
                $searchForm->addSubForm($field->getSubForm(), $field->getId());
            }

		    $outputForm = new Zend_Form_SubForm();
            $outputForm->setLegend('Output Columns');
		    $outputForm->removeDecorator('DtDdWrapper');
		    $searchForm->removeDecorator('HtmlTag');
		    $outputForm->setAttrib('style', 'padding: 0; margin-top:1.5em; border:0;');
		    $form->addSubForm($outputForm, 'search_output_fields');

		    $every_fifth = 0;
            foreach ($this->getAvailableOutputFields() as $outputField) {
                $every_fifth++;
                $element = $outputField->getElement();
                $outputForm->addElement($outputField->getElement());
                if ($every_fifth == 5) {
                    $every_fifth = 0;
                    $element->getDecorator('HtmlTag')->setOption('class', 'span-4 last');
                }
                $outputForm->addElement($element);
            }

            $limit = new Zend_Dojo_Form_Element_NumberSpinner('search_result_limit');
            $limit->setLabel('Limit resuts to')
            	->setAttrib('style', 'width:8em;')
    			->setConstraint('places', 0)
    			->setConstraint('min', 1)
    			->setConstraint('max', 100000);
            $limit->removeDecorator('HtmlTag');
            $limit->getDecorator('Label')->setOption('tag', 'span');
            $limit->addDecorator('HtmlTag', array('tag' => 'div', 'style' => 'margin-bottom:1em;'));
            $form->addElement($limit);

            $saveButton = new Zend_Dojo_Form_Element_SubmitButton('save');
            $saveButton->setOptions(array('onClick' => "document.getElementById('search_submit_reason').value = 'save'; dojo.byId('".$form->getName()."').submit()"));
            $saveButton->setLabel('Save')
                ->removeDecorator('DtDdWrapper');
            $form->addElement($saveButton);

            $searchButton = new Zend_Dojo_Form_Element_Button('search');
            $searchButton->setOptions(array('onClick' => "document.getElementById('search_submit_reason').value = 'search'; dojo.byId('".$form->getName()."').submit()"));
            $searchButton->setLabel('Search')
                ->removeDecorator('DtDdWrapper');
            $form->addElement($searchButton);

            $exportButton = new Zend_Dojo_Form_Element_Button('export');
            $exportButton->setOptions(array('onClick' => "dijit.byId('exportDialog').show()"));
            $exportButton->setLabel('Export...')
                ->removeDecorator('DtDdWrapper');
            $form->addElement($exportButton);

			$export_add_t = new Zend_Form_Element_Hidden( 'export_add_t' );
			$export_add_t->setDecorators(array('ViewHelper'));
			$form->addElement($export_add_t);
			$export_add_hyperlink = new Zend_Form_Element_Hidden ( 'export_add_hyperlink' );
			$export_add_hyperlink->setDecorators(array('ViewHelper'));
			$form->addElement($export_add_hyperlink);
			$export_submit = new Zend_Form_Element_Hidden( 'export_submit' );
			$export_submit->setDecorators(array('ViewHelper'));
			$form->addElement($export_submit);

            $deleteButton = new Zend_Dojo_Form_Element_Button('delete');
            $deleteButton->setOptions(array('onClick' => "dijit.byId('deleteDialog').show()"));
            $deleteButton->setLabel('Delete...')
                ->removeDecorator('DtDdWrapper');
            $form->addElement($deleteButton);

            $this->_form = $form;
        }
        return $this->_form;
    }

    // generate sql statement
    public function assemble ()
    {
        return $this->getSelect()->assemble();
    }

    public function query ()
    {
        return $this->getSelect()->query();
    }

    public function getSelect() {
        if (null === $this->_select) {
            $select = new Zend_Db_Select(Zend_Db_Table::getDefaultAdapter());
            $select = $this->setupSelect($select);
            $select->columns($this->getOutputFieldsSql());
            foreach ($this->_fields as $field) {
                $where = $field->assembleWhere();
                if ($where) {
                    $select->where($where);
                }
            }
            if ($this->_limit) {
            	$select->limit($this->_limit);
            }
            $this->_select = $select;
        }
        return $this->_select;
    }

    public function getFields ()
    {
        return $this->_fields;
    }

    public function getField ($key)
    {
        $key = (string) $key;
        if (isset($this->_fields[$key])) {
            return $this->_fields[$key];
        }
        return null;
    }

    public function clearFields() {
        $this->_fields = array();
    }

    public function setFields (array $fields)
    {
        $this->clearFields();
        foreach ($fields as $field) {
            $this->addField($field);
        }
        return $this;
    }

    public function addField (mdb_Search_Field_Abstract $field)
    {
        $field_id = $field->getId();

        if ($field_id) {
            $this->_fields[(string) $field_id] = $field;
        } else {
            $this->_fields[] = $field;
        }
        return $this;
    }

    public function removeField ($key)
    {
        if (null !== $this->getField($key)) {
            unset($this->_fields[$key]);
            return true;
        }
        return false;
    }

    abstract protected function setupSelect($select);

    public function setAvailableOutputFields (array $fields)
    {
        $this->_availableOutputFields = array();
        foreach ($fields as $field) {
            $this->addAvailableOutputField($field);
        }
        return $this;
    }

    public function getAvailableOutputFields ()
    {
        return $this->_availableOutputFields;
    }

    public function addAvailableOutputField (mdb_Search_OutputField $field)
    {
        $name = $field->getName();

        if ($name) {
            $this->_availableOutputFields[(string) $name] = $field;
        } else {
            $this->_availableOutputFields[] = $field;
        }
        return $this;
    }

    public function getDefaultOutputFields() {

        $fields = array();

        foreach ($this->_availableOutputFields as $outputField) {
            if ($outputField->isDefault()) {
                $fields[] = $outputField;
            }
        }
        return $fields;
    }

    public function resetOutputFieldsToDefault() {
        foreach ($this->getAvailableOutputFields() as $outputField) {
            $outputField->select($outputField->isDefault());
        }
    }

    public function getOutputFields() {

        $outputFields = array();
        foreach ($this->getAvailableOutputFields() as $outputField) {
            if ($outputField->isSelected()) {
                $outputFields[] = $outputField;
            }
        }
        return $outputFields;
    }

    public function getOutputFieldsSql() {

        $sql = array();
        foreach ($this->getOutputFields() as $field) {
            $snip = $field->getSqlExpr();
            if ($field->getSqlAs()) {
                $snip .= ' as '.$field->getSqlAs();
            }
            $sql[] = $snip;
            if ($field->getIdSqlExpr()) {
                $snip = $field->getIdSqlExpr();
                if ($field->getIdSqlAs()) {
                    $snip .= ' as '.$field->getIdSqlAs();
                }
                $sql[] = $snip;
            }
        }
        return $sql;
    }

    public function getLimit() {
    	return $this->_limit;
    }

    public function setLimit($limit) {
    	$this->_limit = (int) $limit;
    	if (is_object($this->_form)) {
    		 $this->_form->getElement('search_result_limit')->setValue($this->_limit);
    	}
    	if (is_object($this->_select)) {
    		$this->_select->limit($this->_limit);
    	}
    }
}
