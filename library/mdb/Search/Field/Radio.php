<?php
class mdb_Search_Field_Radio extends mdb_Search_Field_Abstract
{
    protected $_queryOptions = array('', 'is' , 'is not');
    protected $_multiOptions = array();

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
                    return 'not '.$sqlRef.' is null';
                } else {
                    return $sqlRef . ' != ' . $db->quote($value).' or '.$sqlRef . ' is null';
                }
                break;
            default:
                throw new Exception('unrecognized option');
        }
    }

    public function valueInput ()
    {
		$value = new Zend_Dojo_Form_Element_RadioButton('value');
        $value->removeDecorator('HtmlTag');
        $value->removeDecorator('Label');
        $value->addDecorator('HtmlTag', array('tag' => 'div' , 'style' => 'display:inline;', 'class' => 'last'));
		$value->setMultiOptions($this->_multiOptions)
		    ->setOptions(array('separator' => ' '));

        return $value;
    }

    public function setMultiOptions($options) {
        $this->_multiOptions = $options;
        return $this;
    }
}