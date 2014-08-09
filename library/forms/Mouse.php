<?php

class forms_Mouse extends Zend_Dojo_Form {

	public function __construct($options = null) {
		parent::__construct ( $options );
		$this->setName ( 'mouse' )
			->setAction('')
			->setMethod('post');

		$this->addElementPrefixPath('mdb_Validate', 'mdb/Validate', 'validate');

		$baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

		$id = new Zend_Form_Element_Hidden ( 'id' );
		$id->setDecorators(array('ViewHelper'));

		$user_id = new Zend_Form_Element_Hidden ( 'user_id' );
		$user_id->setDecorators(array('ViewHelper'));

		$lastmodified = new Zend_Form_Element_Hidden ( 'lastmodified' );
		$lastmodified->setDecorators(array('ViewHelper'));

		$assigned_id = new Zend_Dojo_Form_Element_ValidationTextBox ( 'assigned_id' );
		$assigned_id->setLabel ( 'Mouse ID' )
			->setRequired ( true )
			->addPrefixPath('mdb_Form_Decorator', 'mdb/Form/Decorator/', 'decorator')
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' )
			->addValidator ( 'NotEmpty' )
			->addValidator(new mdb_Validate_UniqueValue('mice', null, 'id', 'assigned_id'));

		$is_alive = new Zend_Dojo_Form_Element_CheckBox ( 'is_alive' );
		$is_alive->setLabel ( 'Alive' );
		$is_alive->removeDecorator('HtmlTag');
        $is_alive->getDecorator('Label')->setOptions(array('tag' => 'span', 'placement' => Zend_Form_Decorator_Abstract::APPEND));
        $is_alive->getDecorator('Label')->getPlacement();
        $is_alive->addDecorator('HtmlTag', array('tag' => 'div'));

		$status = new Zend_Dojo_Form_Element_ComboBox('status');
		$status->setLabel('Status')
			->setStoreId('status_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/mouse/suggest/field/status/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$sex = new Zend_Dojo_Form_Element_RadioButton('sex');
		$sex->setLabel('Sex')
			->setRequired(true)
			->setOptions(array('separator' => ' '))
			->setMultiOptions(array('M' => 'Male', 'F' => 'Female') );

		$born_on = new Zend_Dojo_Form_Element_DateTextBox ( 'born_on' );
		$born_on->setLabel ( 'Born on' )
			->setOptions(array('datePattern' => 'dd-MMM-yy'));

		$pcr_on = new Zend_Dojo_Form_Element_DateTextBox ( 'pcr_on' );
		$pcr_on->setLabel ( 'PCR on' )
			->setOptions(array('datePattern' => 'dd-MMM-yy'));

		$weaned_on = new Zend_Dojo_Form_Element_DateTextBox ( 'weaned_on' );
		$weaned_on->setLabel ( 'Weaned on' )
			->setOptions(array('datePattern' => 'yyMMdd'));

		$terminated_on = new Zend_Dojo_Form_Element_DateTextBox ( 'terminated_on' );
		$terminated_on->setLabel ( 'Terminated on' )
			->setOptions(array('datePattern' => 'yyMMdd'));

		$strain_id = new Zend_Dojo_Form_Element_FilteringSelect('strain_id');
		$strain_id->setLabel('Strain')
			->setStoreId('strain_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/strain/list/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$genotype = new Zend_Dojo_Form_Element_ComboBox('genotype');
		$genotype->setLabel('Genotype')
			->setStoreId('genotype_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/mouse/suggest/field/genotype/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$generation = new Zend_Dojo_Form_Element_ComboBox('generation');
		$generation->setLabel('Generation')
			->setStoreId('generation_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/mouse/suggest/field/generation/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$ear_mark = new Zend_Dojo_Form_Element_ComboBox('ear_mark');
		$ear_mark->setLabel('Ear mark')
			->setStoreId('ear_mark_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => Zend_Controller_Front::getInstance()->getBaseUrl().'/mouse/suggest/field/ear_mark/format/json'))
			->setAutocomplete(false)
			->setRegisterInArrayValidator(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$protocol_id = new Zend_Dojo_Form_Element_FilteringSelect('protocol_id');
		$protocol_id->setLabel('Protocol')
			->setStoreId('protocol_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/protocol/list/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$chip = new Zend_Dojo_Form_Element_TextBox ( 'chip' );
		$chip->setLabel ( 'Chip Number' )
			->addFilter ( 'StripTags' )
			->addFilter ( 'StringTrim' );

//		$chimera_subform = new Zend_Form_SubForm();

		$is_chimera = new Zend_Dojo_Form_Element_CheckBox ( 'is_chimera' );
		$is_chimera->setLabel ( 'Is chimera' );
		$is_chimera->setAttrib('onclick', "javascript: document.getElementById('chimera_box').style.display = (this.checked ? '' : 'none');");
		$is_chimera->removeDecorator('HtmlTag');
        $is_chimera->getDecorator('Label')->setOptions(array('tag' => 'span', 'placement' => Zend_Form_Decorator_Abstract::APPEND));
        $is_chimera->getDecorator('Label')->getPlacement();
        $is_chimera->addDecorator('HtmlTag', array('tag' => 'div'));

		$chimera_is_germline = new Zend_Dojo_Form_Element_CheckBox ( 'chimera_is_germline' );
		$chimera_is_germline->setLabel ( 'Germline' );
		$chimera_is_germline->removeDecorator('HtmlTag');
        $chimera_is_germline->getDecorator('Label')->setOptions(array('tag' => 'span', 'placement' => Zend_Form_Decorator_Abstract::APPEND));
        $chimera_is_germline->getDecorator('Label')->getPlacement();
        $chimera_is_germline->addDecorator('HtmlTag', array('tag' => 'div'));

		$chimera_is_founderline = new Zend_Dojo_Form_Element_CheckBox ( 'chimera_is_founderline' );
		$chimera_is_founderline->setLabel ( 'Founderline' );
		$chimera_is_founderline->removeDecorator('HtmlTag');
        $chimera_is_founderline->getDecorator('Label')->setOptions(array('tag' => 'span', 'placement' => Zend_Form_Decorator_Abstract::APPEND));
        $chimera_is_founderline->getDecorator('Label')->getPlacement();
        $chimera_is_founderline->addDecorator('HtmlTag', array('tag' => 'div'));

		$chimera_perc_esc = new Zend_Dojo_Form_Element_NumberTextBox( 'chimera_perc_esc' );
		$chimera_perc_esc->setLabel ( '% ESC' )
			->setOptions(array('onChange' => 'dojo.byId("chimera_score_div").innerHTML = "<strong>Chimera Score: "+(parseInt("0"+dijit.byId("chimera_perc_esc").getValue(),10) + parseInt("0"+dijit.byId("chimera_perc_escblast").getValue(),10)/2)+"</strong>"'))
			->setConstraint('places', 0)
			->setConstraint('min', 0)
			->setConstraint('max', 100);

		$chimera_perc_escblast = new Zend_Dojo_Form_Element_NumberTextBox( 'chimera_perc_escblast' );
		$chimera_perc_escblast->setLabel ( '% ESC/Blast' )
			->setOptions(array('onChange' => 'dojo.byId("chimera_score_div").innerHTML = "<strong>Chimera Score: "+(parseInt("0"+dijit.byId("chimera_perc_esc").getValue(),10) + parseInt("0"+dijit.byId("chimera_perc_escblast").getValue(),10)/2)+"</strong>"'))
			->setConstraint('places', 0)
			->setConstraint('min', 0)
			->setConstraint('max', 100);

		$chimera_score = new Zend_Form_Element_Hidden( 'chimera_score' );
		$chimera_score->setDecorators(array('ViewHelper'));
		$chimera_score->addDecorator('HtmlTag', array('tag' => 'div', 'id' => 'chimera_score_div', 'placement' => Zend_Form_Decorator_Abstract::APPEND));

		$cage_id = new Zend_Dojo_Form_Element_FilteringSelect('cage_id');
		$cage_id->setLabel('Breeding Cage')
			->setStoreId('breeding_cage_store')
			->setStoreType('dojox.data.QueryReadStore')
			->setStoreParams(array('url' => $baseUrl.'/cage/list/type/breeding/empty/yes/format/json'))
			->setAutocomplete(false)
			->setDijitParam('hasDownArrow', 'true')
			->setDijitParam('searchAttr', 'item')
			->setDijitParam('searchDelay', 200)
			->setDijitParam('pageSize', 200);

		$submit = new Zend_Dojo_Form_Element_SubmitButton ( 'submit' );
		$submit->setLabel('Save')
			->setAttrib ( 'id', 'savemouse' )
			->removeDecorator('DtDdWrapper');

		$delete = new Zend_Dojo_Form_Element_Button('delete');
		$delete->setOptions(array('onClick' => "dijit.byId('deleteDialog').show()"));
		$delete->setLabel('Delete...')
			->setAttrib ( 'id', 'deletebutton' )
			->removeDecorator('DtDdWrapper');

		$this->addElements( array($id, $user_id, $lastmodified, $assigned_id, $is_alive, $status, $born_on, $weaned_on, $terminated_on,
			$sex, $strain_id, $genotype, $generation, $protocol_id, $ear_mark, $chip, $cage_id, $pcr_on ));

		$this->addElement($is_chimera);

		$this->addElements(array($chimera_is_germline, $chimera_is_founderline, $chimera_perc_esc, $chimera_perc_escblast, $chimera_score));

		$this->addElements( array($submit, $delete) );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10'))));

		$this->addDisplayGroup( array('assigned_id', 'sex', 'is_alive', 'status', 'born_on', 'weaned_on', 'terminated_on', 'is_chimera'), 'left' );
        $this->addDisplayGroup( array('strain_id', 'genotype', 'generation', 'protocol_id', 'ear_mark', 'chip', 'cage_id', 'pcr_on' ), 'right' );
		$this->addDisplayGroup( array('chimera_is_germline', 'chimera_is_founderline', 'chimera_perc_esc', 'chimera_perc_escblast', 'chimera_score' ), 'chimera_group' );
		$this->addDisplayGroup( array('submit', 'delete' ), 'button_group' );

		$this->setDisplayGroupDecorators(array( new Zend_Form_Decorator_FormElements()));
		$this->getDisplayGroup('left')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10')));
		$this->getDisplayGroup('right')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('class' => 'span-10 last')));
		$this->getDisplayGroup('chimera_group')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('id' => 'chimera_box', 'class' => 'span-20 last')));
		$this->getDisplayGroup('button_group')->addDecorator(new Zend_Form_Decorator_HtmlTag(array('id' => 'button_box', 'class' => 'span-20 last', 'style' => 'margin-top: 1em; margin-bottom: 1em;')));
	}
}
