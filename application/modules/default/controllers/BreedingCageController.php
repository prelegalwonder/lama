<?php

require_once 'SearchController.php';

class BreedingCageController extends mdb_Controller {

    const ACL_RESOURCE = 'default_breeding-cage';

    protected $_suggestFields = array ('mating_type', 'breeding_type' );
	protected $_table = 'breeding_cages';
	protected $_json = array ('suggest' );

    public function indexAction() {
		$this->view->title = "Breeding Cages";

		$acl = mdb_Acl::getInstance ();
		$this->view->canNew = $acl->isAllowed ( $this->_role_id, $this->getRequest()->getModuleName().'_'.$this->getRequest()->getControllerName(), 'new' );
		$this->view->canSearch = $acl->isAllowed ( $this->_role_id, SearchController::ACL_RESOURCE, 'view' );

		$select = Zend_Db_Table::getDefaultAdapter()->select()
			->from('breeding_cages',
				array('id', 'breeding_type', 'mating_type', 'active', 'set_up_on' ))
			->joinInner('cages',
				'breeding_cages.id = cages.id',
				array('assigned_id', 'greatest(cages.lastmodified, breeding_cages.lastmodified) as lastmodified') )
			->order('lastmodified desc')
			->limit(20);

		$this->view->recently_modified_breeding_cages = $select->query()->fetchAll();
	}

	public function newAction() {
		$this->view->title = "New Breeding Cage";

		$form = new forms_BreedingCage ( );
		$form->active->setValue(true);
		$form->set_up_on->setValue (date('Y-m-d'));
		$form->removeElement('delete');
		$this->view->form = $form;

		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		if ($this->_request->isPost ()) {
			if ($form->isValid ( $formData )) {
				$formValues = $form->getValues();
				if ($formValues['protocol_id'] == 0) {
					$formValues['protocol_id'] = null;
				}
				// create master cage
				$cages = new Cages();
				$cageRow = $cages->createRow();
				$cageRow->assigned_id = $formValues['assigned_id'];
				$cageRow->protocol_id = $formValues['protocol_id'];
				$cageRow->cagetype = Cages::BREEDING;
				$cageRow->user_id = $this->_user_id;
				$cageRow->save();

				// create breeding cage
				$breedingCages = new BreedingCages( );
				$breedingCageRow = $breedingCages->createRow ();
				$breedingCageRow->id = $cageRow->id;
				$breedingCageRow->breeding_type = $formValues['breeding_type'];
				$breedingCageRow->mating_type = $formValues['mating_type'];
				$breedingCageRow->set_up_on = $formValues['set_up_on'];
				$breedingCageRow->active = (bool) $formValues['active'];
				$breedingCageRow->save ();

				mdb_Messages::add ( 'added breeding cage "' . $form->getValue ( 'assigned_id' ) . '"' );

				$this->_redirect ( '/breeding-cage/view/id/' . $cageRow->id );
				return;
			} else {
				$form->populate ( $formData );
			}
		}
	}

	public function viewAction() {
		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canSave = $acl->isAllowed ( $this->_role_id, $aclResource, 'save' );
		$this->view->canDelete = $acl->isAllowed ( $this->_role_id, $aclResource, 'delete' );

		$this->view->canTransfer = $acl->isAllowed ( $this->_role_id, $this->getRequest ()->getModuleName () . '_mouse', 'transfer' );
		$this->view->canSacrifice = $acl->isAllowed ( $this->_role_id, $this->getRequest ()->getModuleName () . '_mouse', 'sacrifice' );
		$this->view->canRevive = $acl->isAllowed ( $this->_role_id, $this->getRequest ()->getModuleName () . '_mouse', 'revive' );

		$db = Zend_Db_Table::getDefaultAdapter();

		$form = new forms_BreedingCage( );
		$form->getElement('assigned_id')
			->setAttrib('disabled', '+disabled+')
			->addDecorator('Lock')
			->getDecorator('HtmlTag')
    		->setOption('style', 'display:inline;');
    	if (! $this->view->canDelete) {
    			$form->removeElement('delete');
    	}
		$formData = mdb_Globals::stripslashes($this->_request->getParams());
		if ($this->_request->isPost ()) {
		    if ($this->view->canSave) {
				//$formData = $this->_request->getPost ();
				if (! array_key_exists('assigned_id', $formData)) {
				     $form->removeElement('assigned_id');
				} else {
				    $form->getElement('assigned_id')->getValidator('UniqueValue')->id = $formData['id'];
				}
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$formValues = $form->getValues();

					// create master cage
					$cages = new Cages();
					$cageRows = $cages->find( $id );

					if ($cageRows->count () == 0) {
						mdb_Messages::add ( 'there is no such cage', 'error' );
						$this->_redirect ( '/breeding-cage' );
						return;
					}
					$cageRow = $cageRows->current ();

					$breedingCages = new BreedingCages( );
					$breedingCageRows = $breedingCages->find( $id );
					if ($breedingCageRows->count () == 0) {
						mdb_Messages::add ( 'there is no such cage', 'error' );
						$this->_redirect ( '/breeding-cage' );
						return;
					}
					$breedingCageRow = $breedingCageRows->current ();

					// if this row has been modified since user retreived the data, reject the save
					// should really reload current data or ask if save anyway or what?
					if ( $formValues['lastmodified'] != max($breedingCageRow->lastmodified, $cageRow->lastmodified) ) {
						mdb_Messages::add ( 'This record was modified since you retreived original data. Current data retreived now.' );
					} else {
					    if (array_key_exists('assigned_id', $formValues)) {
    						$cageRow->assigned_id = $formValues['assigned_id'];
    					}
    					if ($formValues['protocol_id'] == 0) {
    						$formValues['protocol_id'] = null;
    					}
						$cageRow->protocol_id = $formValues['protocol_id'];
						// not necessary when saving existing record
						// $cageRow->cagetype = Cages::BREEDING;
						$cageRow->user_id = $this->_user_id;
						try {
							$cageRow->save ();
							$cageRowSaved = true;
						} catch (Exception $e) {
							$cageRowSaved = false;
							mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
							mdb_Log::Write('unable to save: '.$e->__toString());
						}

						$deactivating = ($breedingCageRow->active && ! $formValues['active']);
						$breedingCageRow->breeding_type = $formValues['breeding_type'];
						$breedingCageRow->mating_type = $formValues['mating_type'];
						$breedingCageRow->set_up_on = ($formValues['set_up_on'] == '' ? null : $formValues['set_up_on']);
						$breedingCageRow->active = (bool) $formValues['active'];
						try {
							$breedingCageRow->save ();
							$breedingCageRowSaved = true;
						} catch (Exception $e) {
							$breedingCageRowSaved = false;
							mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
							mdb_Log::Write('unable to save: '.$e->__toString());
						}

						if ($cageRowSaved && $breedingCageRowSaved) {
							mdb_Messages::add ( 'saved cage '.$cageRow->assigned_id );
						}

						// if we are deactivating the cage, sac all living inhabitants
						if ($deactivating) {
							$mice = new Mice();
							$mice->sacrifice('is_alive && cage_id = '.$id);
						}

					}
					// redirect anyway: Post/Redirect/Get pattern
					$this->_redirect ( '/breeding-cage/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify breeding cages', 'error' );
				$this->_redirect ( '/breeding-cage/view/id/' . $id );
			}
			// implicit return
		}

		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		$id = ( int ) $this->_request->getParam( 'id', 0 );
		$assigned_id = $this->_request->getParam( 'cage', '' );
		if ($id > 0 || $assigned_id != '') {
			if ($id) {
				$where = 'id = '.$db->quote($id);
			} else {
				$where = 'assigned_id = ' . $db->quote($assigned_id) . ' and cagetype = ' . $db->quote(Cages::BREEDING);
			}
			$cages = new Cages( );
			$cageRow = $cages->fetchRow($where);
			// does this cage exist?
			if ($cageRow == null) {
				mdb_Messages::add ( 'there is no such cage', 'error' );
				$this->_redirect ( '/breeding-cage' );
				return;
			}
			$id = $cageRow->id;
			$assigned_id = $cageRow->assigned_id;
			$this->view->cage_id = $id;

			$breedingCages = new BreedingCages();
			$breedingCageRow = $breedingCages->fetchRow('id ='.$id);
			// does this cage exist?
			if ($breedingCageRow == null) {
				mdb_Messages::add( 'there is no such cage', 'error' );
				$this->_redirect( '/breeding-cage' );
				return;
			}

			$this->view->title = 'Breeding Cage ' . $cageRow->assigned_id;

			if ($this->_request->isPost ()) {
				$form->populate( $formData );
			} else {
				$form->populate( $cageRow->toArray() );
				$form->populate( $breedingCageRow->toArray() );
				$form->getElement('lastmodified')->setValue( max($cageRow->lastmodified, $breedingCageRow->lastmodified) );
			}
			if ($breedingCageRow->active) {
				$form->getElement('active')->setLabel('Active (deactivating will kill all breeders)');
			}

			// $form->setAction ( $this->view->url ( array ('controller' => 'mouse', 'action' => 'save' ), null, true ) );
			$lastmodifiedby = $form->getValue('user_id');
			if (! $lastmodifiedby ) {
				$this->view->lastmodifiedby = "nobody in particular";
			} elseif ($lastmodifiedby == $this->_user_id) {
				$this->view->lastmodifiedby = 'you';
			} else {
				$this->view->lastmodifiedby = $db->fetchOne ( "SELECT username from users where id = " . $lastmodifiedby );
			}
			$lastmodified = $form->getValue('lastmodified');
			if ($lastmodified == 0) {
				$this->view->lastmodified = "sometime ago";
			} else {
				$this->view->lastmodified = $lastmodified;
			}
			$this->view->deleteURL = $this->view->url ( array ('controller' => 'breeding-cage', 'action' => 'delete', 'id' => $id ), null, true );

			// list breeders in this cage
			$breeders = $db->select()
			->from('mice',
				array('id', 'assigned_id', 'sex', 'is_alive', 'strain_id', 'genotype', 'born_on', 'generation', 'datediff(curdate(), mice.born_on) as born_days' ))
			->joinLeft('strains',
				'mice.strain_id = strains.id',
				array('strain_name', 'promoter'))
			->where('mice.cage_id = '.$id)
			->order(array('is_alive desc', 'sex'));

			$this->view->breeders = $breeders->query()->fetchAll();

			// who is the assigned stud?
			$this->view->assigned_stud_id = $breedingCageRow->assigned_stud_id;
			if ($this->view->assigned_stud_id) {
				$this->view->assigned_stud_cmmt_id = $db->fetchOne ( "SELECT assigned_id from mice where id = " . $this->view->assigned_stud_id );
			}

			// list litters from this cage
			$litters = $db->select()
			->from('litters',
				array('id', 'assigned_id', 'born_on', 'weaned_on',  'total_pups',  'alive_pups', 'weaned_male_count', 'weaned_female_count', 'holding_male_count', 'holding_female_count', 'sacrificed_male_count', 'sacrificed_female_count' ))
			->where('breeding_cage_id = '.$id)
			->order('born_on', 'assigned_id');

			$this->view->litters = $litters->query()->fetchAll();

			$aclResource = $this->getRequest ()->getModuleName () . '_' . 'litter';
			$this->view->canCreateLitter = $acl->isAllowed ( $this->_role_id, $aclResource, 'new' );

			// get related cages
			$paren_pos = strpos($assigned_id, '(');
			if (! $paren_pos) {
				$paren_pos = strlen($assigned_id);
			}
			$assigned_id_root = substr($assigned_id, 0, $paren_pos);
			$where_related = 'assigned_id like '.$db->quote($assigned_id_root.'(%');
			if ($breedingCageRow->assigned_stud_id) {
				$where_related .= ' || assigned_stud_id = '.$breedingCageRow->assigned_stud_id;
			}
			$related_cages = $db->select()
				->from('cages',
					array('id', 'assigned_id' ))
				->joinInner('breeding_cages', 'breeding_cages.id = cages.id')
				->where($where_related)
				->order('assigned_id');

			$this->view->related_cages = $related_cages->query()->fetchAll();
			// we don't actually want to list anything for 1 match, which will be this litter
			if (count($this->view->related_cages) == 1) {
				$this->view->related_cages = array();
			}

			// warning for cages older then 6 months
			$daysOld = floor((time() - strtotime($breedingCageRow->set_up_on))/(60*60*24));
			if ( $breedingCageRow->active && $daysOld > 180 ) {
				$this->view->old_cage_notify = 'This cage was set up '.$daysOld.' days old and is still active.';
			}

			$xfers_select = $db->select()
				->from('transfers',
					array('id', 'mouse_id', 'transferred_on', 'user_id', 'from_cage_id', 'to_cage_id', 'notes' ))
				->joinLeft('mice',
					'mice.id = transfers.mouse_id',
					array('mice.assigned_id as mouse_assigned_id'))
				->joinLeft('cages as cages_from',
					'transfers.from_cage_id = cages_from.id',
					array('cages_from.assigned_id as from_cage_assigned_id', 'cages_from.cagetype as from_cagetype'))
				->joinLeft('cages as cages_to',
					'transfers.to_cage_id = cages_to.id',
					array('cages_to.assigned_id as to_cage_assigned_id', 'cages_to.cagetype as to_cagetype'))
				->joinLeft('users',
					'transfers.user_id = users.id',
					array('username'))
				->where('(from_cage_id = '.$db->quote($id).' and (cages_to.cagetype is null or cages_to.cagetype = '.$db->quote(Cages::BREEDING).')) or to_cage_id = '.$db->quote($id))
				->order('transfers.id');;

			$this->view->transfers = $xfers_select->query()->fetchAll();

		} else {
			// no strain id specified in URL
			$this->_redirect ( '/breeding-cage' );
			return;
		}
	}

	public function suggestAction() {

		$field = mdb_Globals::stripslashes ( $this->_request->getParam ( 'field' ) );

		if ($field == 'breeding_type') {
			$types = BreedingCages::getBreedingTypes();
		} elseif ($field == 'mating_type') {
			$types = BreedingCages::getMatingTypes();
		} else {
		    parent::suggestAction();
		    return;
		}
		$this->view->identifier = 'id';
		$this->view->label = 'item';
		$items = array();
		foreach ($types as $type) {
			$items[] = array('id' => $type, 'item' => $type);
		}
		$this->view->items = $items;
	}

	public function deleteAction() {

		$this->view->title = "Delete Breeding Cage";

		$id = ( int ) $this->_request->getParam( 'id' );
		if ($id) {
			$breeding_cages = new BreedingCages ( );

			$rows = $breeding_cages->find ( $id );
			if ($rows->count ()) {
				$row = $rows->current ();
				try {
					$row->delete ();
					mdb_Messages::add ( 'breeding cage deleted' );
				} catch (Exception $e) {
					mdb_Messages::add ( $e->getMessage(), 'error' );
					mdb_Log::Write('unable to delete: '.$e->__toString());
					$this->_redirect ( '/breeding-cage/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'there is no such breeding cage', 'error' );
			}
			$this->_redirect ( '/breeding-cage' );
		} else {
			$this->_redirect ( '/breeding-cage/view/id/' . $id );
		}
	}

	public function unassignstudAction() {

		$id = ( int ) $this->_request->getParam ( 'id' );

		$db = Zend_Db_Table::getDefaultAdapter();

		if (! $id) {
			mdb_Messages::add ( 'You must specify a cage to unassign stud from', 'error' );
			$this->_redirect ( '/breeding-cage' );
			return;
		}

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$assigned_id = $this->_request->getParam ( 'cage', '' );
		if ($id > 0 || $assigned_id != '') {
			if ($id) {
				$where = 'id ='.$id;
			} else {
				$where = 'assigned_id = ' . $db->quote($assigned_id) . ' and cagetype = ' . $db->quote(Cages::BREEDING);
			}
			$cages = new Cages ( );
			$cageRow = $cages->fetchRow($where);
			// does this cage exist?
			if ($cageRow == null) {
				mdb_Messages::add ( 'there is no such cage', 'error' );
				$this->_redirect ( '/breeding-cage' );
				return;
			}
			if ($id == 0) {
				$id = $cageRow->id;
			}
			$this->view->cage_id = $id;

			$breedingCages = new BreedingCages ( );
			$breedingCageRow = $breedingCages->fetchRow('id ='.$id);
			// does this cage exist?
			if ($breedingCageRow == null) {
				mdb_Messages::add ( 'there is no such breeding cage', 'error' );
				$this->_redirect ( '/breeding-cage' );
				return;
			}

			// cannot unassign a stud if it is alive and in the cage.
			if ($breedingCageRow->assigned_stud_id) {
				if ( $db->fetchOne('select count(*) from mice where is_alive and cage_id = '.$id.' and id = '.$breedingCageRow->assigned_stud_id) ) {
					mdb_Messages::add ( 'cannot unassign a living stud from the cage it is currently in', 'error' );
					$this->_redirect ( '/breeding-cage/view/id/'.$id );
					return;
				}
			}

			// now unassign stud
			$breedingCageRow->assigned_stud_id = null;
			$breedingCageRow->save();
			$this->_redirect ( '/breeding-cage/view/id/'.$id );
		} else {
			mdb_Messages::add ( 'You must specify a cage to unassign stud from', 'error' );
			$this->_redirect ( '/breeding-cage' );
		}
	}

	public function searchAction() {

		$this->view->title = "Search Breeding Cages";

		$query = mdb_Globals::stripslashes ( $this->_request->getParam ( 'q' ) );

		// if there is a breeding cage with this assigned_id, just to straight there
		if ($query != '') {
			$db = Zend_Db_Table::getDefaultAdapter();
			$single_result = $db->fetchOne('select id from cages where cagetype = \'breeding\' and assigned_id = '.$db->quote($query));
			if ($single_result) {
				$this->_redirect ( '/breeding-cage/view/id/' . $single_result );
			}

			$pageNumber = $this->_request->getParam ( 'page', 1 );

			$this->view->query = $query;

			if ( substr($query, 0, 1) == '!' ) {
				$query = substr($query, 1);
				$select = $db->select ()
				->from ( 'cages',
					array('id', 'assigned_id', 'protocol_id'))
				->joinInner('breeding_cages',
					'cages.id = breeding_cages.id',
					array( 'breeding_type', 'mating_type', 'active', 'set_up_on', 'greatest(cages.lastmodified, breeding_cages.lastmodified) as lastmodified'))
				->where ( $query )
				->order ( 'assigned_id' );
			} else {
				$select = $db->select ()
				->from ( 'cages',
					array('id', 'assigned_id', 'protocol_id'))
				->joinInner('breeding_cages',
					'cages.id = breeding_cages.id',
					array( 'breeding_type', 'mating_type', 'active', 'set_up_on', 'greatest(cages.lastmodified, breeding_cages.lastmodified) as lastmodified'))
				->where ( 'MATCH (assigned_id) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) or MATCH (mating_type, breeding_type) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) or exists (select * from comments where ref_table = '.$db->quote(Comments::BREEDING_CAGE).' and ref_item_id = breeding_cages.id and MATCH (comment) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) )' )
				->order ( 'assigned_id' );
			}

			try {
				$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_DbSelect ( $select ) );
				$paginator->setItemCountPerPage ( 1000 );
				$paginator->setCurrentPageNumber ( $pageNumber );
				$this->view->paginator = $paginator;
			} catch (Zend_Db_Statement_Exception $e) {
				$this->view->search_error = $e->getMessage();
				mdb_Log::Write('search error: '.$e->__toString());
			}
		}
	}

}
