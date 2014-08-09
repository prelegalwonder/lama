<?php
class mdb_Search_Field_ForeignKey extends mdb_Search_Field_Abstract
{
    protected $_queryOptions = array('', 'is' , 'is not');
    protected $_dataStoreUrl;
    protected $_foreignTable;
    protected $_foreignKey= 'id';
    protected $_multiOptions;

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
		$value = new Zend_Dojo_Form_Element_FilteringSelect('value');
        $value->removeDecorator('HtmlTag');
        $value->removeDecorator('Label');
        $value->addDecorator('HtmlTag', array('tag' => 'div' , 'style' => 'display:inline;', 'class' => 'last'));
		$value->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchDelay', 200);
        if (isset($this->_multiOptions)) {
            $value->setMultiOptions($this->_multiOptions);
        } elseif (isset($this->_dataStoreUrl)) {
    		$value->addValidator(new mdb_Validate_ForeignKey($this->_foreignTable, $this->_foreignKey))
    		    ->setStoreId($this->getId().'_store')
    			->setStoreType('dojox.data.QueryReadStore')
    			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().$this->_dataStoreUrl))
    			->setDijitParam('searchAttr', 'item')
    			->setDijitParam('pageSize', 200);
        }
        return $value;
    }

    public function setDataStoreUrl($url) {
        $this->_dataStoreUrl = $url;
        return $this;
    }

    public function setForeignTable($table) {
        $this->_foreignTable = $table;
        return $this;
    }

    public function setForeignKey($key) {
        $this->_foreignKey = $key;
        return $this;
    }

    public function setMultiOptions($options) {
        $this->_multiOptions = $options;
        return $this;
    }

}