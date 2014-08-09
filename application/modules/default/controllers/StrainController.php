<?php
require_once 'SearchController.php';

class StrainController extends mdb_Controller {

	const ACL_RESOURCE = 'default_strain';

	protected $_suggestFields = array ('esc_line', 'grant', 'location', 'pems', 'bems', 'reporter', 'promoter', 'backbone_pems', 'jax_store_number', 'jax_strain_name', 'jax_generation', 'jax_genotype', 'jax_url' );
	protected $_table = 'strains';
	protected $_json = array ('list', 'suggest' );

	public function indexAction() {
		$this->view->title = "Strains";
		$strains = new Strains ( );

		$acl = mdb_Acl::getInstance ();
		$this->view->canNew = $acl->isAllowed ( $this->_role_id, $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName (), 'new' );
		$this->view->canSearch = $acl->isAllowed ( $this->_role_id, SearchController::ACL_RESOURCE, 'view' );

		$this->view->recently_modified_strains = $strains->fetchAll ( null, 'lastmodified desc', 20 );
	}

	public function newAction() {
		$this->view->title = "New Strain";

		$form = new forms_Strain ( );
		$form->removeElement('delete');
		$this->view->form = $form;

		$formData = mdb_Globals::stripslashes ( $this->_request->getParams () );
		if ($this->_request->isPost ()) {
			//$formData = $this->_request->getPost ();
			if ($form->isValid ( $formData )) {
				$strains = new Strains ( );
				$row = $strains->createRow ();
				foreach ( $form->getValues () as $key => $value ) {
					if ($row->__isset ( $key ) && ! in_array ( $key, array ('id', 'user_id', 'lastmodified' ) )) {
						if (in_array ( $key, array ('assigned_user_id' ) ) && $value == '') {
							$value = null;
						}
						$row->__set ( $key, $value );
					}
				}
				$row->user_id = $this->_user_id;
				try {
					$row->save ();
					mdb_Messages::add ( 'added strain "' . $form->getValue ( 'strain_name' ) . '"' );
				} catch ( Exception $e ) {
					mdb_Messages::add ( 'unable to save: ' . $e->getMessage (), 'error' );
					mdb_Log::Write('unable to save: '.$e->__toString());
				}

				$this->_redirect ( '/strain/view/id/' . $row->id );
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

		$form = new forms_Strain ( );
		$form->getElement ( 'strain_name' )->setAttrib ( 'disabled', '+disabled+' )->addDecorator ( 'Lock' )->getDecorator ( 'HtmlTag' )->setOption ( 'style', 'display:inline;' );
    	if (! $this->view->canDelete) {
    			$form->removeElement('delete');
    	}

		$formData = mdb_Globals::stripslashes ( $this->_request->getParams () );

		$db = Zend_Db_Table::getDefaultAdapter ();

		if ($this->_request->isPost ()) {
			if ($this->view->canSave) {
				// $formData = $this->_request->getPost ();
				if (! array_key_exists ( 'strain_name', $formData )) {
					$form->removeElement ( 'strain_name' );
				} else {
					$form->getElement ( 'strain_name' )->getValidator ( 'UniqueValue' )->id = $formData ['id'];
				}
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$strains = new Strains ( );
					$rows = $strains->find ( $id );
					// does this strain exist?
					if ($rows->count () == 0) {
						mdb_Messages::add ( 'there is no such strain', 'error' );
						$this->_redirect ( '/strain' );
						return;
					}
					$row = $rows->current ();
					// if this row has been modified since user retreived the data, reject the save
					// should really reload current data or ask if save anyway or what?
					if ($form->getValue ( 'lastmodified' ) != $row->lastmodified) {
						mdb_Messages::add ( 'This record was modified since you retreived original data. Current data retreived now.' );
					} else {
						foreach ( $form->getValues () as $key => $value ) {
							if ($row->__isset ( $key ) && ! in_array ( $key, array ('id', 'user_id', 'lastmodified' ) )) {
								if (in_array ( $key, array ('assigned_user_id' ) ) && $value == '') {
									$value = null;
								}
								$row->__set ( $key, $value );
							}
						}

						$row->user_id = $this->_user_id;
						try {
							$row->save ();
							mdb_Messages::add ( 'saved strain "' . $row->strain_name . '"' );
						} catch ( Exception $e ) {
							mdb_Messages::add ( 'unable to save: ' . $e->getMessage (), 'error' );
							mdb_Log::Write('unable to save: '.$e->__toString());
						}
					}

					// redirect anyway: Post/Redirect/Get pattern
					$this->_redirect ( '/strain/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify strains', 'error' );
			}
		}

		// we are here because user wants to view strain, or maybe after save, failed or not
		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$strain_name = $this->_request->getParam ( 'strain', '' );
		if ($id == 0 && $strain_name == '') {
			// no strain id specified in URL
			$this->_redirect ( '/strain' );
			return;
		}

		$strains = new Strains ( );
		if ($id) {
			$where = 'id = ' . $db->quote ( $id );
		} else {
			$where = 'strain_name = ' . $db->quote ( $strain_name );
		}
		$row = $strains->fetchRow ( $where );
		// does this strain exist?
		if (is_null ( $row )) {
			mdb_Messages::add ( 'there is no such strain', 'error' );
			$this->_redirect ( '/strain' );
			return;
		}
		if ($id == 0) {
			$id = $row->id;
		}
		$this->view->title = 'Strain ' . $row->strain_name;
		if ($this->_request->isPost ()) {
			$form->populate ( $formData );
		} else {
			$form->populate ( $row->toArray () );
		}
		// $form->setAction ( $this->view->url ( array ('controller' => 'strain', 'action' => 'save' ), null, true ) );
		$lastmodifiedby = $form->getValue ( 'user_id' );
		if (! $lastmodifiedby) {
			$this->view->lastmodifiedby = "nobody in particular";
		} elseif ($lastmodifiedby == $this->_user_id) {
			$this->view->lastmodifiedby = 'you';
		} else {
			$this->view->lastmodifiedby = Zend_Db_Table::getDefaultAdapter ()->fetchOne ( "SELECT username from users where id = " . $lastmodifiedby );
		}
		$lastmodified = $form->getValue ( 'lastmodified' );
		if ($lastmodified == 0) {
			$this->view->lastmodified = "sometime ago";
		} else {
			$this->view->lastmodified = $lastmodified;
		}
		$this->view->deleteURL = $this->view->url ( array ('controller' => 'strain', 'action' => 'delete', 'id' => $id ), null, true );
		$this->view->strain_id = $id;

	}

	public function deleteAction() {
		$this->view->title = "Delete Strain";

		$id = ( int ) $this->_request->getParam ( 'id' );
		if ($id) {
			$strains = new Strains ( );

			$rows = $strains->find ( $id );
			if ($rows->count ()) {
				$row = $rows->current ();
				try {
					$row->delete ();
					mdb_Messages::add ( 'strain deleted' );
				} catch ( Exception $e ) {
					mdb_Messages::add ( $e->getMessage (), 'error' );
					mdb_Log::Write('unable to delete: '.$e->__toString());
					$this->_redirect ( '/strain/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'there is no such strain', 'error' );
			}
		}
		$this->_redirect ( '/strain' );
	}

	public function listAction() {

		$this->listItems ( $this->_table, 'id', 'strain_name' );

	}

	public function searchAction() {

		$this->view->title = "Search Strains";

		$query = mdb_Globals::stripslashes ( $this->_request->getParam ( 'q' ) );

		// if there is a breeding cage with this assigned_id, just to straight there
		if ($query != '') {
			$db = Zend_Db_Table::getDefaultAdapter ();
			$single_result = $db->fetchOne ( 'select id from strains where strain_name = ' . $db->quote ( $query ) );
			if ($single_result) {
				$this->_redirect ( '/strain/view/id/' . $single_result );
			}

			$pageNumber = $this->_request->getParam ( 'page', 1 );

			$this->view->query = $query;

			if (substr ( $query, 0, 1 ) == '!') {
				$query = substr ( $query, 1 );
				$select = $db->select ()->from ( 'strains' )->joinLeft ( 'users as assigned_user', 'assigned_user.id = strains.assigned_user_id', 'assigned_user.username as assigned' )->where ( $query )->order ( 'strain_name' );
			} else {
				$select = $db->select ()->from ( 'strains' )->joinLeft ( 'users as assigned_user', 'assigned_user.id = strains.assigned_user_id', 'assigned_user.username as assigned' )->where ( 'MATCH (strain_name,pems,bems,promoter,esc_line,backbone_pems,reporter,jax_strain_name,jax_store_number,jax_generation,jax_genotype,jax_url) AGAINST (' . $db->quote ( $query ) . ' IN BOOLEAN MODE) or MATCH (assigned_user.username) AGAINST (' . $db->quote ( $query ) . ' IN BOOLEAN MODE) or exists (select * from comments where ref_table = ' . $db->quote ( Comments::STRAIN ) . ' and ref_item_id = strains.id and MATCH (comment) AGAINST (' . $db->quote ( $query ) . ' IN BOOLEAN MODE) )' )->order ( 'strain_name' );
			}
			try {
				$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_DbSelect ( $select ) );
				$paginator->setItemCountPerPage ( 1000 );
				$paginator->setCurrentPageNumber ( $pageNumber );
				$this->view->paginator = $paginator;
			} catch ( Zend_Db_Statement_Exception $e ) {
				$this->view->search_error = $e->getMessage ();
				mdb_Log::Write('unable to search: '.$e->__toString());
			}
		}
	}
}