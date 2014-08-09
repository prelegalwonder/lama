<?php
class mdb_Search_Field_ComboBox extends mdb_Search_Field_Text
{
    protected $_dataStoreUrl;
    protected $_multiOptions;
    protected $_storeType = 'dojox.data.QueryReadStore';

    public function valueInput ()
    {
		$value = new Zend_Dojo_Form_Element_ComboBox('value');
        $value->removeDecorator('HtmlTag');
        $value->removeDecorator('Label');
        $value->addDecorator('HtmlTag', array('tag' => 'div' , 'style' => 'display:inline;', 'class' => 'last'));
		$value->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true');
		if (isset($this->_dataStoreUrl)) {
			$value->setStoreId($this->getId().'_store')
				->setDijitParam('searchAttr', 'item')
				->setDijitParam('searchDelay', 200)
				->setDijitParam('pageSize', 200)
				->setStoreType($this->_storeType)
				->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().$this->_dataStoreUrl));
		} elseif (is_array($this->_multiOptions)) {
			$value->setMultiOptions($this->_multiOptions);
		}
			return $value;
    }

    public function setDataStoreUrl($url) {
        $this->_dataStoreUrl = $url;
        return $this;
    }

    public function setMultiOptions($options) {
        $this->_multiOptions = $options;
        return $this;
    }

    public function setStoreType($type) {
        $this->_storeType = $type;
        return $this;
    }
}