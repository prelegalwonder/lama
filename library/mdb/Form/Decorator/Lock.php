<?php

require_once 'Zend/Form/Decorator/Abstract.php';

class mdb_Form_Decorator_Lock extends Zend_Form_Decorator_Abstract
{

    public function render($content)
    {
    	$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

        $element = $this->getElement();
        $id = $element->getId();

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();

        $lock = '<button class="small" dojoType="dijit.form.Button" onClick="if (dijit.byId(\''.$id.'\').disabled) { dijit.byId(\''.$id.'\').setDisabled(false); dijit.byId(\''.$id.'\').focus(); document.getElementById(\''.$id.'_lock_icon\').src = \''.$baseUrl.'/images/unlock.png\';} else { dijit.byId(\''.$id.'\').setDisabled(true);  document.getElementById(\''.$id.'_lock_icon\').src = \''.$baseUrl.'/images/lock.png\';};" title="Click to toggle editing of '.$element->getLabel().'"><img id="'.$id.'_lock_icon" src="'.$baseUrl.'/images/lock.png" /></button>';

        switch ($placement) {
            case self::PREPEND:
                return $lock . $separator . $content;
            case self::APPEND:
                return $content . $separator . $lock;
        }
        return $content . $separator . $lock;
    }
}
