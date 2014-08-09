<?php
require_once 'SearchController.php';

class MouseController extends mdb_Controller {

    const ACL_RESOURCE = 'default_mouse';
	const MODIFY_SACRIFICE = "sacrifice";
	const MODIFY_GENOTYPE = "change genotype";
	const MODIFY_STATUS = "change status";

	protected $_table = 'mice';
	protected $_json = array('list', 'suggest');
	protected $_suggestFields = array('status', 'genotype', 'generation', 'ear_mark');

	public function indexAction() {
		$this->view->title = "Mice";

		$acl = mdb_Acl::getInstance ();
		$this->view->canNew = $acl->isAllowed ( $this->_role_id, $this->getRequest()->getModuleName().'_'.$this->getRequest()->getControllerName(), 'new' );
		$this->view->canSearch = $acl->isAllowed ( $this->_role_id, SearchController::ACL_RESOURCE, 'view' );


		$select = Zend_Db_Table::getDefaultAdapter()->select()
			->from('mice',
				array('id', 'assigned_id as mouse_id', 'sex', 'born_on','pcr_on', 'is_alive', 'status', 'lastmodified', 'genotype', 'strain_id', 'cage_id' ))
			->joinLeft('strains',
				'mice.strain_id = strains.id',
				array('strain_name'))
			->joinLeft('cages',
			        'mice.cage_id = cages.id',
				array('assigned_id'))
			->order('mice.lastmodified desc');
			//->limit(20000000);

		/* try {
			$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_DbSelect ( $select ) );
			$paginator->setItemCountPerPage ( 20 );
			$paginator->setCurrentPageNumber ( $pageNumber );
			$this->view->recently_modified_mice = $paginator;
		} catch (Zend_Db_Exception $e) {
			$this->view->search_error = $e->getMessage();
			mdb_Log::Write('unable to get mice: '.$e->__toString());
        } */

		$this->view->recently_modified_mice = $select->query()->fetchAll();
}

	public function newAction() {
		$this->view->title = "New Mouse";

		$form = new forms_Mouse ( );
 		$form->getDisplayGroup('chimera_group')
			->getDecorator('HtmlTag')
			->setOption('style', 'display:none;');
		$form->removeElement('delete');

		$this->view->form = $form;

		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		if ($this->_request->isPost ()) {
			// $formData = $this->_request->getPost ();
			if ($form->isValid ( $formData )) {

				$mice = new Mice ( );
				$row = $mice->createRow ();
				foreach ( $form->getValues() as $key => $value ) {
					if ( $row->__isset($key) && ! in_array($key, array('id', 'user_id', 'lastmodified', 'cage_id')) ) {
						if (in_array($key, array('born_on', 'pcr_on', 'weaned_on', 'terminated_on', 'protocol_id', 'strain_id', 'chimera_perc_esc', 'chimera_perc_escblast')) && $value == '') {
							$value = null;
						}
						$row->__set($key, $value);
					}
				}
				if ( strtolower(substr($row->assigned_id, 0, 4)) == 'mems') {
					$form->getElement('is_chimera')->setValue(true);
				}
				if (! $form->getElement('is_chimera')->getValue()) {
					$row->is_chimera = false;
					$row->chimera_is_germline = null;
					$row->chimera_is_founderline = null;
					$row->chimera_perc_esc = null;
					$row->chimera_perc_escblast = null;
				}
				if (! $row->is_alive && is_null($row->terminated_on)) {
					$row->terminated_on = date('Y-m-d');
				}
				$row->user_id = $this->_user_id;
				$row->save ();

				mdb_Messages::add ( 'added mouse "' . $form->getValue ( 'assigned_id' ) . '"' );

				// if cage is selected, try to transfer it
				$cage_id = $form->getValue('cage_id');
				if ($cage_id) {
					$this->_redirect ( '/mouse/transfer/xfer_mouse_id/' . $row->id . '/xfer_cage_id/' .$cage_id . '/xfer_redirect/themouse');
				} else {
					$this->_redirect ( '/mouse/view/id/' . $row->id );
				}
				return;
			} else {
				$form->populate ( $formData );
			}
		} else {
			$form->is_alive->setValue(true);
		}
	}

	public function deleteAction() {

		$this->view->title = "Delete Mouse";

		$id = ( int ) $this->_request->getParam ( 'id' );

		if ($id) {
			$mice = new Mice ( );

			$rows = $mice->find ( $id );
			if ($rows->count ()) {
				$row = $rows->current ();
				try {
					$row->delete ();
					mdb_Messages::add ( 'mouse deleted' );
				} catch (Zend_Db_Exception $e) {
					mdb_Messages::add ( $e->getMessage(), 'error' );
					mdb_Log::Write('unable to delete: '.$e->__toString());
					$this->_redirect ( '/mouse/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'there is no such mouse', 'error' );
			}
		}
		$this->_redirect ( '/mouse' );
	}

	public function viewAction() {
		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canSave = $acl->isAllowed ( $this->_role_id, $aclResource, 'save' );
		$this->view->canDelete = $acl->isAllowed ( $this->_role_id, $aclResource, 'delete' );
		$this->view->canTransfer = $acl->isAllowed ( $this->_role_id, $aclResource, 'transfer' );
		$this->view->canRevive = $acl->isAllowed ( $this->_role_id, $aclResource, 'revive' );
		$this->view->canSacrifice = $acl->isAllowed ( $this->_role_id, $aclResource, 'sacrifice' );

		$form = new forms_Mouse ( );
		$form->getElement('assigned_id')
		    ->setAttrib('disabled', '+disabled+')
		    ->addDecorator('Lock')
    		    ->getDecorator('HtmlTag')
    		    ->setOption('style', 'display:inline;');
		$form->removeElement('cage_id');
    	if (! $this->view->canDelete) {
    			$form->removeElement('delete');
    	}

		$db = Zend_Db_Table::getDefaultAdapter ();
		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		if ($this->_request->isPost ()) {

			if ($this->view->canSave) {
				// $formData = $this->_request->getPost ();
				if (! array_key_exists('assigned_id', $formData)) {
				     $form->removeElement('assigned_id');
				} else {
    				$form->getElement('assigned_id')->getValidator('UniqueValue')->id = $formData['id'];
				}
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$mice = new Mice ( );
					$rows = $mice->find ( $id );
					// does this mouse exist?
					if ($rows->count () == 0) {
						mdb_Messages::add ( 'there is no such mouse', 'error' );
						$this->_redirect ( '/mouse' );
						return;
					}
					$row = $rows->current ();

					// if there is change in sex, detect whether if it is allowed
					$performSexChange = $row->sex != $form->getValue('sex');
					if ( $performSexChange ) {
						// sex change is allowed only if:
						// - mouse doesn't come from a litter (ie was created manually)
						// - there are no litters with this mouse as parent
						// - breeding cage rules would allow this mouse to have new sex (that is, respect max # of male/female breeders)
						$canChangeSex = true;

						if ( $row->litter_id ) {
							mdb_Messages::add ( 'unable to save: sex cannot be changed in mice that come from litters.', 'error' );
							$canChangeSex = false;
						}
						if ( $db->fetchOne('select count(*) from litters where father_id='.$id.' or mother_id='.$id.' or mother2_id='.$id.' or mother3_id='.$id) > 0 ) {
							mdb_Messages::add ( 'unable to save: sex cannot be changed in mice that are parents.', 'error' );
							$canChangeSex = false;
						}

						if ($row->cage_id) {
							if ($form->getValue('sex') == 'M') {
								// is there alredy another stud in this cage?
								if ( $db->fetchOne('select count(*) from mice where sex = \'M\' and is_alive and cage_id = '.$row->cage_id.' and id != '.$id) ) {
									mdb_Messages::add ( 'unable to save: that cage already has a living male.', 'error' );
									$canChangeSex = false;
								}
							} else {
								switch ( $db->fetchOne ( "SELECT mating_type FROM breeding_cages where id = ".$row->cage_id) ) {
									case BreedingCages::MATING_TRIO:
										$max_females = 2;
										break;
									case BreedingCages::MATING_QUAD:
										$max_females = 3;
										break;
									default:
										// assume it is pair
										$max_females = 1;
										break;
								}
								if ( $db->fetchOne('select count(*) from mice where sex = \'F\' and is_alive and cage_id = '.$row->cage_id.' and id != '.$id) >= $max_females) {
									mdb_Messages::add ( 'unable to save: that cage already has maximum number of females as per specified mating type.', 'error' );
									$canChangeSex = false;
								}

							}
						}

						if (! $canChangeSex) {
							$this->_redirect ( '/mouse/view/id/' . $id );
						}
					}

					$exclude_fields = array('id', 'user_id', 'lastmodified');
					// also exclude values that are inherited from litter
					if ($row->litter_id) {
						//$exclude_fields = array_merge($exclude_fields, array('sex', 'born_on', 'weaned_on', 'strain_id', 'generation'));
						$exclude_fields = array_merge($exclude_fields, array('sex', 'born_on', 'weaned_on', 'generation'));
					}

					foreach ( $form->getValues() as $key => $value ) {
						if ( $row->__isset($key) && ! in_array($key, $exclude_fields) ) {
							//if (in_array($key, array('born_on', 'weaned_on', 'terminated_on', 'protocol_id', 'strain_id', 'chimera_perc_esc', 'chimera_perc_escblast')) && $value == '') {
							if (in_array($key, array('born_on', 'weaned_on', 'terminated_on', 'protocol_id', 'chimera_perc_esc', 'chimera_perc_escblast')) && $value == '') {
								$value = null;
							}
							$row->__set($key, $value);
						}
					}

					if (! $form->getElement('is_chimera')->getValue()) {
						$row->is_chimera = false;
						$row->chimera_is_germline = null;
						$row->chimera_is_founderline = null;
						$row->chimera_perc_esc = null;
						$row->chimera_perc_escblast = null;
					}
					$row->user_id = $this->_user_id;
					if ($row->is_alive) {
						$row->terminated_on = null;
					} elseif (is_null($row->terminated_on)) {
						$row->terminated_on = date('Y-m-d');
					}

					try {
						$row->save ();
						mdb_Messages::add ( 'saved mouse "' . $row->assigned_id . '"' );
						if ($performSexChange) {
							// if sex was changed, may have to adjust cages
							// if this mouse is a male now, assign it as stud to the current cage.
							if ($row->sex == 'M') {
								if ($row->cage_id) {
									$db->update('breeding_cages', array('assigned_stud_id' => $id), 'id = '.$row->cage_id);
								}
							} else {
								$db->update('breeding_cages', array('assigned_stud_id' => null), 'assigned_stud_id = '.$id);
							}
						}
					} catch (Zend_Db_Exception $e) {
						mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
						mdb_Log::Write('unable to save: '.$e->__toString());
					}

					// redirect anyway: Post/Redirect/Get pattern
					$this->_redirect ( '/mouse/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify mice', 'error' );
				$this->_redirect ( '/mouse/view/id/' . $id );
			}
		}

		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$assigned_id = $this->_request->getParam ( 'mouse', '' );
		if ($id > 0 || $assigned_id != '') {
			$mice = new Mice ( );
			if ($id) {
				$where = 'id = '.$db->quote($id);
			} else {
				$where = 'assigned_id = ' . $db->quote($assigned_id);
			}
			$row = $mice->fetchRow($where);
			// does this mouse exist?
			if ($row == null) {
				mdb_Messages::add ( 'there is no such mouse', 'error' );
				$this->_redirect ( '/mouse' );
				return;
			}
			$this->view->title = 'Mouse ' . $row->assigned_id;
			if ($id == 0) {
				$id = $row->id;
			}
			$this->view->mouse_id = $id;
			if ($this->_request->isPost ()) {
				$form->populate ( $formData );
			} else {
				$form->populate ( $row->toArray () );
			}
			if ($row->litter_id) {
				// $form->assigned_id->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
				$form->sex->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
				$form->born_on->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
				$form->weaned_on->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
				//$form->strain_id->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
				$form->generation->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
			}

			if (! $form->is_chimera->getValue()) {
    			$form->getDisplayGroup('chimera_group')
	    			->getDecorator('HtmlTag')
		    		->setOption('style', 'display:none;');
			}
			$lastmodifiedby = $form->getElement('user_id')->getValue();
			if ( ! $lastmodifiedby ) {
				$this->view->lastmodifiedby = "nobody in particular";
			} elseif ($lastmodifiedby == $this->_user_id) {
				$this->view->lastmodifiedby = 'you';
			} else {
				$this->view->lastmodifiedby = $db->fetchOne ( "SELECT username from users where id = " . $lastmodifiedby );
			}
			$lastmodified = $form->getElement('lastmodified')->getValue();
			if ($lastmodified == 0) {
				$this->view->lastmodified = "sometime ago";
			} else {
				$this->view->lastmodified = $lastmodified;
			}
			$this->view->deleteURL = $this->view->url ( array ('controller' => 'mouse', 'action' => 'delete', 'id' => $id ), null, true );

			$this->view->is_alive = $row->is_alive;

			$this->view->parents = array();

			if ($row->litter_id) {
				$litters = new Litters();
				$litterRow = $litters->fetchRow('id = '.$row->litter_id);
				if ($litterRow) {

					// list litter info
					$this->view->litter_id = $litterRow->id;
					$this->view->assigned_litter_id = $litterRow->assigned_id;

					// don't delete mice that come from litters.
					$this->view->canDelete = false;
					$form->removeElement('delete');

					$parentIds = array();
					if ($litterRow->father_id) {
						$parentIds[] = $litterRow->father_id;
					}
					if ($litterRow->mother_id) {
						$parentIds[] = $litterRow->mother_id;
					}
					if ($litterRow->mother2_id) {
						$parentIds[] = $litterRow->mother2_id;
					}
					if ($litterRow->mother3_id) {
						$parentIds[] = $litterRow->mother3_id;
					}
					if (count($parentIds)) {
						$parents = $db->select()
						->from('mice',
							array('id', 'assigned_id', 'sex', 'is_alive', 'strain_id', 'genotype', 'born_on', 'generation', 'datediff(curdate(), mice.born_on) as born_days' ))
						->joinLeft('strains',
							'mice.strain_id = strains.id',
							array('strain_name', 'promoter'))
						->where('mice.id in ('. implode(',', $parentIds) .')')
						->order(array('sex', 'mice.assigned_id'));

						$this->view->parents = $parents->query()->fetchAll();
					}
				} else {
					mdb_Messages::add ( 'this mouse should have come from a litter, but related litter info is missing', 'warning' );
				}
			}

			$xfers_select = $db->select()
				->from('transfers',
					array('id', 'mouse_id', 'transferred_on', 'user_id', 'from_cage_id', 'to_cage_id', 'notes' ))
				->joinLeft('cages as cages_from',
					'transfers.from_cage_id = cages_from.id',
					array('cages_from.assigned_id as from_cage_assigned_id', 'cages_from.cagetype as from_cagetype'))
				->joinLeft('cages as cages_to',
					'transfers.to_cage_id = cages_to.id',
					array('cages_to.assigned_id as to_cage_assigned_id', 'cages_to.cagetype as to_cagetype'))
				->joinLeft('users',
					'transfers.user_id = users.id',
					array('username'))
				->where('transfers.mouse_id = ' . $id)
				->order('transfers.id');;

			$this->view->transfers = $xfers_select->query()->fetchAll();

			// details about current cage

			$this->view->current_cage_id = $row->cage_id;
			if ($this->view->current_cage_id) {
				$this->view->current_cage_assigned_id = $db->fetchOne ( "SELECT assigned_id from cages where id = " . $this->view->current_cage_id );
				$this->view->current_cagetype = $db->fetchOne ( "SELECT cagetype from cages where id = " . $this->view->current_cage_id );
			}

			// get litters parented by this mouse
			$litters_select = $db->select()
				->from('litters',
					array('id', 'assigned_id', 'born_on', 'weaned_on', 'alive_pups', 'lastmodified', 'strain_id' ))
				->joinLeft('strains',
					'litters.strain_id = strains.id',
					array('strain_name'))
				->where('father_id = ' . $id . ' or mother_id = ' . $id . ' or mother2_id = ' . $id . ' or mother3_id = ' . $id);;

			$this->view->litters = $litters_select->query()->fetchAll();

		} else {
			// no strain id specified in URL
			$this->_redirect ( '/mouse' );
			return;
		}
	}

	public function searchAction() {

		$this->view->title = "Search Mice";

		$query = mdb_Globals::stripslashes ( $this->_request->getParam ( 'q' ) );

		// if there is a mouse with this assigned_id, just to straight there
		if ($query != '') {
			$db = Zend_Db_Table::getDefaultAdapter();
			$single_result = $db->fetchOne('select id from mice where assigned_id = '.$db->quote($query));
			if ($single_result) {
				$this->_redirect ( '/mouse/view/id/' . $single_result );
			}

			$pageNumber = $this->_request->getParam ( 'page', 1 );

			$this->view->query = $query;

			if ( substr($query, 0, 1) == '!' ) {
				$query = substr($query, 1);
				$select = $db->select ()
				->from ( 'mice')
				->joinLeft('strains',
					'mice.strain_id = strains.id',
					'strain_name')
				->where ( $query )
				->order ( 'mice.assigned_id' );
			} else {
				$select = $db->select ()
					->from ( 'mice' )
					->joinLeft('strains',
						'mice.strain_id = strains.id',
						'strain_name')
					->where ( 'MATCH (mice.assigned_id, status, genotype, generation, chip) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) or MATCH (strain_name, promoter) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) or exists (select * from comments where ref_table = '.$db->quote(Comments::MOUSE).' and ref_item_id = mice.id and MATCH (comment) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) )' )
					->order ( 'mice.assigned_id' );
			}

			try {
				$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_DbSelect ( $select ) );
				$paginator->setItemCountPerPage ( 1000 );
				$paginator->setCurrentPageNumber ( $pageNumber );
				$this->view->paginator = $paginator;
			} catch (Zend_Db_Exception $e) {
				$this->view->search_error = $e->getMessage();
				mdb_Log::Write('unable to search: '.$e->__toString());
			}
		}
	}

	public function listAction() {

        $sex = strtoupper($this->_request->getParam ( 'sex' ));

        if ($sex == 'M' || $sex == 'F') {
            $where = 'sex = \''.$sex.'\'';
        } else {
            $where = null;
        }

		$this->listItems('mice', 'id', 'assigned_id', $where);
	}

	public function sacrificeAction() {

		$id = ( int ) $this->_request->getParam('id', 0);
		if ( $id ) {
			$mice = new Mice ( );
			$mice->sacrifice('id = '.$id);
			$this->_redirect ($this->_request->getParam('redirect', '/mouse/view/id/' . $id) );
		} else {
			mdb_Messages::add ( 'no mouse selected for sacrifice', 'error' );
			$this->_redirect ($this->_request->getParam('redirect', '/mouse') );
		}
	}

	public function reviveAction() {

		$id = ( int ) $this->_request->getParam('id', 0);
		if ( $id ) {
			$mice = new Mice ( );
			try {
				$mice->revive('id = '.$id);
			} catch (Zend_Db_Exception $e) {
				mdb_Messages::add ( $e->getMessage(), 'error' );
			}
			$this->_redirect ($this->_request->getParam('redirect', '/mouse/view/id/' . $id) );
		} else {
			mdb_Messages::add ( 'no mouse selected to revive', 'error' );
			$this->_redirect ($this->_request->getParam('redirect', '/mouse') );
		}
	}

	public function transferAction() {

		$id = $this->_request->getParam('xfer_mouse_id');
		$to_cage_id = $this->_request->getParam('xfer_cage_id', null);
		$notes = $this->_request->getParam('xfer_notes', '');
		$redirect = $this->_request->getParam('xfer_redirect', '/');

		if ($redirect == 'themouse') {
			$redirect = '/mouse/view/id/'.$id;
		} elseif ($redirect == 'thecage') {
			$redirect = '/breeding-cage/view/id/'.$to_cage_id;
		} elseif ($redirect == 'thecage') {
			$redirect = '/holding-cage/view/id/'.$to_cage_id;
		}

		if ( $id ) {
			$mice = new Mice ( );
			try {
				$mice->transfer('id = '.$id, $to_cage_id, $notes, $this->_user_id);
				mdb_Messages::add ( 'mouse transferred' );
			} catch (Zend_Db_Exception $e) {
				mdb_Messages::add ( $e->getMessage(), 'error' );
				mdb_Log::Write('unable to transfer: '.$e->__toString());
			}
		} else {
			mdb_Messages::add ( 'no mouse selected to transfer', 'error' );
		}
		$this->_redirect ($redirect);

	}

	public function modifyselectedAction() {

		$selectedMice = array();

		foreach ($this->_request->getParams() as $param => $value ) {
			if ( substr($param,0,9) == 'selected-' && $value > 0 ) {
				$selectedMice[] = $value;
			}
		}

		if (count($selectedMice)) {
			try {
				$mice = new Mice ( );
				$action = $this->_request->getParam('modify_action');
				$where = 'id in ('.implode(',', $selectedMice).')';
				switch ($action) {
				case self::MODIFY_SACRIFICE:
					$mice->sacrifice($where);
					break;
				case self::MODIFY_GENOTYPE:
					$mice->setGenotype($where, $this->_request->getParam('genotype'));
					break;
				case self::MODIFY_STATUS:
					$status = $this->_request->getParam('status');
					if ($this->_request->getParam('also_kill')) {
						$mice->sacrifice($where, $status);
					} else {
						$mice->setStatus($where, $status);
					}
					break;
				default:
					mdb_Messages::add ( 'I do not know how to '.$action, 'error' );
					break;
				}
			} catch (Zend_Db_Exception $e) {
				mdb_Messages::add ( $e->getMessage(), 'error' );
				mdb_Log::Write('unable to modift selected mice: '.$e->__toString());
			}
		} else {
			mdb_Messages::add ( 'no mice selected', 'error' );
		}

		$this->_redirect ($this->_request->getParam('redirect', '/mouse') );
	}

}
