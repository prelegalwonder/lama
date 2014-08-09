<?php

class TransferController extends mdb_Controller {

    const ACL_RESOURCE = 'default_transfer';

	public function newAction() {
		$this->view->title = "New Mouse Transfer Record";

		$form = new forms_Transfer ( );
		$form->removeElement('delete');
		$this->view->form = $form;
		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		if ($this->_request->isPost ()) {
			// $formData = $this->_request->getPost ();
			if ($form->isValid ( $formData )) {
				$transfers = new Transfers ( );
				$row = $transfers->createRow ();
				foreach ( $form->getValues() as $key => $value ) {
					if ( $row->__isset($key) && ! in_array($key, array('id', 'user_id', 'lastmodified')) ) {
						$row->__set($key, $value);
					}
				}
				if (is_null($row->from_cage_id) && is_null($row->to_cage_id)) {
					mdb_Messages::add ( 'unable to save: you must select from or to cage', 'error' );
					$this->_redirect('/transfer/new');
					return;
				}
				if (is_null($row->mouse_id)) {
					mdb_Messages::add ( 'unable to save: you must select a mouse', 'error' );
					$this->_redirect('/transfer/new');
					return;
				}
				if ($row->transferred_on == 0) {
					$row->transferred_on = null;
				}
				if (is_null($row->transferred_on)) {
					mdb_Messages::add ( 'unable to save: you must select transfer date', 'error' );
					$this->_redirect('/transfer/new');
					return;
				}
				$row->user_id = $this->_user_id;
				try {
					$row->save ();
					mdb_Messages::add ( 'added mouse transfer record' );
				} catch (Exception $e) {
					mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
					mdb_Log::Write('unable to save: '.$e->__toString());
				}

				if (is_null($row->mouse_id)) {
					$redirect = '/transfer/view/id/' . $row->mouse_id;
				} else {
					$redirect = '/mouse/view/id/' . $row->mouse_id;
				}
				$this->_redirect ( $redirect );
				return;
			} else {
				$form->populate ( $formData );
			}
		} else {
			$form->getElement('transferred_on')->setValue(date('Y-m-d'));
		}
	}

	public function viewAction() {
		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canSave = $acl->isAllowed ( $this->_role_id, $aclResource, 'save' );
		$this->view->canDelete = $acl->isAllowed ( $this->_role_id, $aclResource, 'delete' );

		$form = new forms_Transfer( );
    	if (! $this->view->canDelete) {
    			$form->removeElement('delete');
    	}

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		if ($this->_request->isPost () && ! $this->_request->getParam('edit') == 'true') {
			if ($this->view->canSave) {
				// $formData = $this->_request->getPost ();
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$transfers = new Transfers ( );
					$rows = $transfers->find ( $id );
					// does this transfer exist?
					if ($rows->count () == 0) {
						mdb_Messages::add ( 'there is no such transfer record', 'error' );
						$this->_redirect ( '/mouse' );
						return;
					}
					$row = $rows->current ();
					// if this row has been modified since user retreived the data, reject the save
					// should really reload current data or ask if save anyway or what?
					if ( $form->getValue('lastmodified') != $row->lastmodified) {
						mdb_Messages::add ( 'This record was modified since you retreived original data. Current data retreived now.' );
					} else {
						foreach ( $form->getValues() as $key => $value ) {
							if ( $row->__isset($key) && ! in_array($key, array('id', 'user_id', 'lastmodified')) ) {
								$row->__set($key, $value);
							}
						}

						$notes_subform = $form->getValue('notes_subform');
						foreach ( $notes_subform as $key => $value ) {
							if ( $row->__isset($key) ) {
								$row->__set($key, $value);
							}
						}
						if (is_null($row->from_cage_id) && is_null($row->to_cage_id)) {
							mdb_Messages::add ( 'unable to save: you must select from or to cage', 'error' );
							$this->_redirect ( '/mouse/view/id/' . $row->mouse_id );
							return;
						}

						$row->user_id = $this->_user_id;
						try {
							$row->save ();
							mdb_Messages::add ( 'saved transfer record');
						} catch (Exception $e) {
							mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
							mdb_Log::Write('unable to save: '.$e->__toString());
						}
					}

					if (is_null($row->mouse_id)) {
						$redirect = '/transfer/view/id/' . $row->mouse_id;
					} else {
						$redirect = '/mouse/view/id/' . $row->mouse_id;
					}
					$this->_redirect ( $redirect );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify transfer records', 'error' );
			}
		}

		// we are here because user wants to view strain, or maybe after save, failed or not
		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		if (! $id) {
			// no transfer id specified in URL
			$this->_redirect ( '/mouse' );
			return;
		}

		if ($this->_request->isPost () && ! $this->_request->getParam('edit') == 'true') {
			$form->populate ( $formData );
		} else {
			$transfers = new Transfers ( );
			$rows = $transfers->find ( $id );
			// does this transfer exist?
			if ($rows->count () == 0) {
				mdb_Messages::add ( 'there is no such transfer record', 'error' );
				$this->_redirect ( '/mouse' );
				return;
			}
			$row = $rows->current ();
			$this->view->title = 'Mouse Transfer Record';

			$form->populate ( $row->toArray () );
		}
		$lastmodifiedby = $form->getValue('user_id');
		if ( ! $lastmodifiedby ) {
			$this->view->lastmodifiedby = "nobody in particular";
		} elseif ($lastmodifiedby == $this->_user_id) {
			$this->view->lastmodifiedby = 'you';
		} else {
			$this->view->lastmodifiedby = Zend_Db_Table::getDefaultAdapter ()->fetchOne ( "SELECT username from users where id = " . $lastmodifiedby );
		}
		$lastmodified = $form->getValue('lastmodified');
		if ($lastmodified == 0) {
			$this->view->lastmodified = "sometime ago";
		} else {
			$this->view->lastmodified = $lastmodified;
		}
		$this->view->deleteURL = $this->view->url ( array ('controller' => 'transfer', 'action' => 'delete', 'id' => $id ), null, true );
		$this->view->transfer_id = $id;

	}

	public function deleteAction() {
		$this->view->title = "Delete Mouse Transfer Record";

		$id = ( int ) $this->_request->getParam( 'id' );
		if ($id) {
			$transfers = new Transfers ( );

			$rows = $transfers->find ( $id );
			if ($rows->count ()) {
				$row = $rows->current ();
				try {
					$mouse_id = $row->mouse_id;
					$row->delete ();
					mdb_Messages::add ( 'mouse transfer record deleted' );
					$this->_redirect ( '/mouse/view/id/' . $mouse_id);
					return;
				} catch (Exception $e) {
					mdb_Messages::add ( $e->getMessage(), 'error' );
					mdb_Log::Write('unable to delete: '.$e->__toString());
					$this->_redirect ( '/transfer/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'there is no such mouse transfer record', 'error' );
				$this->_redirect ( '/mouse' );
			}
		}
		$this->_redirect ( '/transfer/view/id/' . $id );
	}
}