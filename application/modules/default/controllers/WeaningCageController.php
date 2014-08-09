<?php
require_once 'SearchController.php';

class WeaningCageController extends mdb_Controller {

    const ACL_RESOURCE = 'default_weaning-cage';

	public function indexAction() {
		$this->view->title = "Weaning Cages";
		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canNew = $acl->isAllowed ( $this->_role_id, $aclResource, 'new' );
		$this->view->canSearch = $acl->isAllowed ( $this->_role_id, SearchController::ACL_RESOURCE, 'view' );

		$select = Zend_Db_Table::getDefaultAdapter()->select()
			->from('weaning_cages',
				array('id', 'sex', 'litter_id' ))
			->joinInner('cages',
				'weaning_cages.id = cages.id',
				array('assigned_id', 'greatest(cages.lastmodified, weaning_cages.lastmodified) as lastmodified') )
			->joinLeft('litters',
				'litters.id = weaning_cages.litter_id',
				array('assigned_id as litter_assigned_id', 'weaned_on') )
			->order('lastmodified desc')
			->limit(20);

		$this->view->recently_modified_weaning_cages = $select->query()->fetchAll();
	}

	public function viewAction() {

		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canSave = $acl->isAllowed ( $this->_role_id, $aclResource, 'save' );

		$db = Zend_Db_Table::getDefaultAdapter();

		$form = new forms_WeaningCage ( );
		$form->getElement('assigned_id')
		    ->setAttrib('disabled', '+disabled+')
		    ->addDecorator('Lock')
    		    ->getDecorator('HtmlTag')
    		    ->setOption('style', 'display:inline;');
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
					$formValues = $form->getValues();

					// create master cage
					$cages = new Cages();
					$cageRows = $cages->find( $id );

					if ($cageRows->count () == 0) {
						mdb_Messages::add ( 'there is no such cage', 'error' );
						$this->_redirect ( '/weaning-cage' );
						return;
					}
					$cageRow = $cageRows->current ();

					$weaningCages = new WeaningCages( );
					$weaningCageRows = $weaningCages->find( $id );
					if ($weaningCageRows->count () == 0) {
						mdb_Messages::add ( 'there is no such cage', 'error' );
						$this->_redirect ( '/weaning-cage' );
						return;
					}
					$weaningCageRow = $weaningCageRows->current ();

					// if this row has been modified since user retreived the data, reject the save
					// should really reload current data or ask if save anyway or what?
					if ( $formValues['lastmodified'] != max($weaningCageRow->lastmodified, $cageRow->lastmodified) ) {
						mdb_Messages::add ( 'This record was modified since you retreived original data. Current data retreived now.' );
					} else {
					    if ($formValues['assigned_id']) {
    						$cageRow->assigned_id = $formValues['assigned_id'];
    					}
						$cageRow->protocol_id = $formValues['protocol_id'];
						// not necessary when saving existing record
						// $cageRow->cagetype = Cages::WEANING;
						$cageRow->user_id = $this->_user_id;
						try {
							$cageRow->save ();
						    mdb_Messages::add ( 'weaning cage '.$cageRow->assigned_id.' saved' );
						} catch (Exception $e) {
							mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
							mdb_Log::Write('unable to save: '.$e->__toString());
						}
					}
					// redirect anyway: Post/Redirect/Get pattern
					$this->_redirect ( '/weaning-cage/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify weaning cages', 'error' );
				$this->_redirect ( '/weaning-cage/view/id/' . $id );
			}
			// implicit return
		}

		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$assigned_id = $this->_request->getParam ( 'cage', '' );
		if ($id > 0 || $assigned_id != '') {
			if ($id) {
				$where = 'id = '.$db->quote($id);
			} else {
				$where = 'assigned_id = ' . $db->quote($assigned_id) . ' and cagetype = ' . $db->quote(Cages::WEANING);
			}
			$cages = new Cages ( );
			$cageRow = $cages->fetchRow($where);
			// does this cage exist?
			if ($cageRow == null) {
				mdb_Messages::add ( 'there is no such cage', 'error' );
				$this->_redirect ( '/weaning-cage' );
				return;
			}
			if ($id == 0) {
				$id = $cageRow->id;
			}
			$this->view->cage_id = $id;

			$weaningCages = new WeaningCages ( );
			$weaningCageRow = $weaningCages->fetchRow('id ='.$id);
			// does this cage exist?
			if ($weaningCageRow == null) {
				mdb_Messages::add ( 'there is no such cage', 'error' );
				$this->_redirect ( '/weaning-cage' );
				return;
			}

			$this->view->title = 'Weaning Cage ' . $this->view->escape($cageRow->assigned_id);

			if ($this->_request->isPost ()) {
				$form->populate ( $formData );
			} else {
				$form->populate ( $cageRow->toArray () );
				$form->populate ( $weaningCageRow->toArray () );
				$form->getElement('lastmodified')->setValue( max($cageRow->lastmodified, $weaningCageRow->lastmodified) );
			}

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
			$this->view->deleteURL = $this->view->url ( array ('controller' => 'weaning-cage', 'action' => 'delete', 'id' => $id ), null, true );

			$this->view->litter_id = $weaningCageRow->litter_id;
			if ($this->view->litter_id) {
				$this->view->litter_name = $db->fetchOne ( "select assigned_id from litters where id = " . $weaningCageRow->litter_id );
			}

			// list mice in this cage
			$mice = $db->select()
			->from('mice',
				array('id', 'assigned_id', 'sex', 'is_alive', 'status' ))
			->where('mice.cage_id = '.$id)
			->order(array('is_alive desc', 'sex'));

			$this->view->mice = $mice->query()->fetchAll();

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
				->where('from_cage_id = '.$db->quote($id).' or to_cage_id = '.$db->quote($id))
				->order('transfers.id');;

			$this->view->transfers = $xfers_select->query()->fetchAll();

		} else {
			// no cage id specified in URL
			$this->_redirect ( '/weaning-cage' );
			return;
		}
	}

	public function searchAction() {

		$this->view->title = "Search Weaning Cages";

		$query = mdb_Globals::stripslashes ( $this->_request->getParam ( 'q' ) );

		// if there is a breeding cage with this assigned_id, just to straight there
		if ($query != '') {
			$db = Zend_Db_Table::getDefaultAdapter();
			$single_result = $db->fetchOne('select id from cages where cagetype = \'weaning\' and assigned_id = '.$db->quote($query));
			if ($single_result) {
				$this->_redirect ( '/weaning-cage/view/id/' . $single_result );
			}

			$pageNumber = $this->_request->getParam ( 'page', 1 );

			$this->view->query = $query;

			if ( substr($query, 0, 1) == '!' ) {
				$query = substr($query, 1);
				$select = Zend_Db_Table::getDefaultAdapter ()->select ()
				->from('weaning_cages',
					array('id', 'sex', 'litter_id' ))
				->joinInner('cages',
					'weaning_cages.id = cages.id',
					array('assigned_id', 'greatest(cages.lastmodified, weaning_cages.lastmodified) as lastmodified') )
				->joinLeft('litters',
					'litters.id = weaning_cages.litter_id',
					array('assigned_id as litter_assigned_id', 'weaned_on') )
				->where ( $query )
				->order ( 'assigned_id' );
			} else {
				$select = Zend_Db_Table::getDefaultAdapter ()->select ()
				->from('weaning_cages',
					array('id', 'sex', 'litter_id' ))
				->joinInner('cages',
					'weaning_cages.id = cages.id',
					array('assigned_id', 'greatest(cages.lastmodified, weaning_cages.lastmodified) as lastmodified') )
				->joinLeft('litters',
					'litters.id = weaning_cages.litter_id',
					array('assigned_id as litter_assigned_id', 'weaned_on') )
				->where ( 'MATCH (cages.assigned_id) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) or exists (select * from comments where ref_table = '.$db->quote(Comments::WEANING_CAGE).' and ref_item_id = weaning_cages.id and MATCH (comment) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) )' )
				->order ( 'assigned_id' );
			}

			try {
				$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_DbSelect ( $select ) );
				$paginator->setItemCountPerPage ( 1000 );
				$paginator->setCurrentPageNumber ( $pageNumber );
				$this->view->paginator = $paginator;
			} catch (Zend_Db_Statement_Exception $e) {
				$this->view->search_error = $e->getMessage();
				mdb_Log::Write('unable to search: '.$e->__toString());
			}
		}
	}
}