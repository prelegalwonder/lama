<?php
class mdb_Search_Field_Number extends mdb_Search_Field_Abstract
{
    protected $_queryOptions = array('', 'is' , 'is not' , 'is less than' , 'is more than');
    protected $_isSubselect = false;
    protected $_subselect;
    protected $_constraints = array();

    public function assembleWhere ()
    {
        $queryOption = $this->getSubForm()->getElement('options')->getValue();
        $value = $this->getSubForm()->getElement('value')->getValue();
        if ($queryOption == 0) {
            if ($value) {
                $queryOption = 1;
                $this->getSubForm()->getElement('options')->setValue($queryOption);
            } else {
                return null;
            }
        }
        $db = Zend_Db_Table::getDefaultAdapter();
        $sqlRef = $db->quoteIdentifier($this->getSqlRef());
        if ($this->_isSubselect) {
            $select = $this->_subselect;
            switch ($queryOption) {
                case 1:
                    if ($value == '') {
                        return 'not exists ('.$select.')';
                    } else {
                        return 'exists ('.$select.' and '.$sqlRef.' = '.$db->quote($value).')';
                    }
                    break;
                case 2:
                    if ($value == '') {
                        return 'exists ('.$select.')';
                    } else {
                        return 'not exists ('.$select.' and '.$sqlRef.' = '.$db->quote($value).')';
                    }
                    break;
                case 3:
                    if ($value == '') {
                        return 'not exists ('.$select.')';
                    } else {
                        return 'exists ('.$select.' and '.$sqlRef.' > '.$db->quote($value).')';
                    }
                    break;
                case 4:
                    if ($value == '') {
                        return 'exists ('.$select.')';
                    } else {
                        return 'exists ('.$select.' and '.$sqlRef.' < '.$db->quote($value).')';
                    }
                    break;
                default:
                    throw new Exception('unrecognized option');
            }
        } else {
            switch ($queryOption) {
                case 1:
                    if ($value == '') {
                        return $sqlRef . ' is null';
                    } else {
                        return $sqlRef . ' = ' . $db->quote($value);
                    }
                    break;
                case 2:
                    if ($value == '') {
                        return 'not '.$sqlRef . ' is null';
                    } else {
                        return $sqlRef . ' != ' . $db->quote($value);
                    }
                    break;
                case 3:
                    if ($value == '') {
                        return null;
                    } else {
                        return $sqlRef . ' < ' . $db->quote($value);
                    }
                    break;
                case 4:
                    if ($value == '') {
                        return null;
                    } else {
                        return $sqlRef . ' > ' . $db->quote($value);
                    }
                    break;
                default:
                    throw new Exception('unrecognized option');
            }
        }
    }

    public function isSubselect($flag) {
        $this->_isSubselect = (bool) $flag;
        return $this;
    }

    public function setSubselect($select) {
        $this->_subselect = $select;
        return $this;
    }

    public function valueInput ()
    {
    	if (! isset($this->_valueInput)) {
	        $valueInput = new Zend_Dojo_Form_Element_NumberTextBox('value');
	        $valueInput->removeDecorator('HtmlTag');
	        $valueInput->removeDecorator('Label');
	        $valueInput->addDecorator('HtmlTag', array('tag' => 'div' , 'style' => 'display:inline;', 'class' => 'last'));
	        foreach ($this->_constraints as $constraint => $value) {
	        	$valueInput->setConstraint($constraint, $value);
	        }
	        $this->_valueInput = $valueInput;
    	}

        return $this->_valueInput;
    }

    public function setConstraint($constraint, $value) {
    	$this->_constraints[$constraint] = $value;
    	if (isset($this->_valueInput)) {
    		$this->_valueInput->setConstraint($constraint, $value);
    	}
    	return $this;
    }
}