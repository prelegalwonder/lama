<?php
class mdb_Search_Field_Boolean extends mdb_Search_Field_Abstract
{
    protected $_queryOptions = array('', 'yes' , 'no');

    public function assembleWhere ()
    {
        $queryOption = $this->getSubForm()->getElement('options')->getValue();
        //$value = $this->getSubForm()->getElement('value')->getValue();
        $db = Zend_Db_Table::getDefaultAdapter();
        $sqlRef = $db->quoteIdentifier($this->getSqlRef());
        switch ($queryOption) {
            case 0:
                return null;
                break;
            case 1:
                return $sqlRef;
                break;
            case 2:
                return 'not '.$sqlRef;
                break;
            default:
                throw new Exception('unrecognized option');
        }
    }

    public function valueInput ()
    {
        return null;
    }
}