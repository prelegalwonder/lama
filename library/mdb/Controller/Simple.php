<?php

class mdb_Controller_Simple extends mdb_Controller {
	protected $_table;
	protected $_modelClass;
	protected $_formClass;
	protected $_item;
	protected $_controller_path;
	protected $_ignoreFormFields = array ('id', 'user_id', 'lastmodified' );
	protected $_empty2nullFormFields = array ();
	protected $_assigned_id_col = 'assigned_id';
	protected $_json = array ('list' );

	public function init() {
		$contextSwitch = $this->_helper->getHelper ( 'contextSwitch' );
		$contextSwitch->addActionContext ( 'list', 'json' )->initContext ();

		parent::init ();
	}

	public function indexAction() {
		$this->view->title = $this->_modelClass;
		$model = new $this->_modelClass ( );

		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canNew = $acl->isAllowed ( $this->_role_id, $aclResource, 'new' );

		$this->view->view_controller = $this->_controller_path;
		$this->view->items = $model->fetchAll ();
	}

	public function deleteAction() {
		$this->view->title = 'Delete ' . $this->_item;
		$id = ( int ) $this->_request->getParam ( 'id' );
		if (! $id) {
			mdb_Messages::add ( $this->_item . 'not specified', 'error' );
			$this->_redirect ( '/' . $this->_controller_path );
		}
		$model = new $this->_modelClass ( );
		$rows = $model->find ( $id );
		if (! $rows->count ()) {
			mdb_Messages::add ( 'there is no such ' . $this->_item, 'error' );
			$this->_redirect ( '/' . $this->_controller_path );
		}
		$row = $rows->current ();
		try {
			$row->delete ();
			mdb_Messages::add ( $this->_item . ' deleted' );
		} catch ( Zend_Db_Exception $e ) {
			mdb_Messages::add ( $e->getMessage (), 'error' );
			mdb_Log::Write('unable to delete: '.$e->__toString());
			$this->_redirect ( '/' . $this->_controller_path . '/view/id/' . $id );
			return;
		}
		$this->_redirect ( '/' . $this->_controller_path );
	}

	public function newAction() {
		$this->view->title = 'New ' . $this->_item;
		$form = new $this->_formClass ( );
		$form->removeElement('delete');
		$this->view->form = $form;
		$formData = mdb_Globals::stripslashes ( $this->_request->getParams () );
		if ($this->_request->isPost ()) {
			if ($form->isValid ( $formData )) {
				$model = new $this->_modelClass ( );
				$row = $model->createRow ();
				foreach ( $form->getValues () as $key => $value ) {
					if ($row->__isset ( $key ) && ! in_array ( $key, $this->_ignoreFormFields )) {
						$row->__set ( $key, $value );
					}
				}
				$row->user_id = $this->_user_id;
				try {
					$row->save ();
					mdb_Messages::add ( 'saved new ' . $this->_item );
				} catch ( Exception $e ) {
					mdb_Messages::add ( 'unable to save: ' . $e->getMessage (), 'error' );
					mdb_Log::Write('unable to save: '.$e->__toString());
				}
				$this->_redirect ( '/' . $this->_controller_path . '/view/id/' . $row->id );
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
		$form = new $this->_formClass ( );
		$form->getElement ( $this->_assigned_id_col )->setAttrib ( 'disabled', '+disabled+' )->addDecorator ( 'Lock' )->getDecorator ( 'HtmlTag' )->setOption ( 'style', 'display:inline;' );
    	if (! $this->view->canDelete) {
    			$form->removeElement('delete');
    	}

		$formData = mdb_Globals::stripslashes ( $this->_request->getParams () );
		if ($this->_request->isPost ()) {
			if ($this->view->canSave) {
				if (! array_key_exists ( $this->_assigned_id_col, $formData )) {
					$form->removeElement ( $this->_assigned_id_col );
				} else {
					$form->getElement ( $this->_assigned_id_col )->getValidator ( 'UniqueValue' )->id = $formData ['id'];
				}
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$model = new $this->_modelClass ( );
					$rows = $model->find ( $id );
					if ($rows->count () == 0) {
						mdb_Messages::add ( 'there is no such ' . $this->_item, 'error' );
						$this->_redirect ( '/' . $this->_controller_path );
						return;
					}
					$row = $rows->current ();
					// if this row has been modified since user retreived the data, reject the save
					// should really reload current data or ask if save anyway or what?
					if ($form->getValue ( 'lastmodified' ) != $row->lastmodified) {
						mdb_Messages::add ( 'This record was modified since you retreived original data. Current data retreived now.' );
					} else {
						foreach ( $form->getValues () as $key => $value ) {
							if ($row->__isset ( $key ) && ! in_array ( $key, $this->_ignoreFormFields )) {
								if (in_array ( $key, $this->_empty2nullFormFields ) && $value == '') {
									$value = null;
								}
								$row->__set ( $key, $value );
							}
						}
						$row->user_id = $this->_user_id;
						try {
							$row->save ();
							mdb_Messages::add ( 'saved ' . $this->_item );
						} catch ( Exception $e ) {
							mdb_Messages::add ( 'unable to save: ' . $e->getMessage (), 'error' );
							mdb_Log::Write('unable to save: '.$e->__toString());
						}
					}
					$this->_redirect ( '/' . $this->_controller_path . '/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify ' . $this->_item, 'error' );
			}
		}
		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;
		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$item_name = $this->_request->getParam ( strtolower ( $this->_item ), '' );
		if ($id == 0 and $item_name == '') {
			// no item id specified in URL
			$this->_redirect ( '/' . $this->_controller_path );
			return;
		}
		$model = new $this->_modelClass ( );
		if ($id) {
			$where = 'id =' . $id;
		} else {
			$where = $this->_assigned_id_col . ' = \'' . $item_name . '\'';
		}
		$row = $model->fetchRow ( $where );
		// does this strain exist?
		if (is_null ( $row )) {
			mdb_Messages::add ( 'there is no such ' . $this->_item, 'error' );
			$this->_redirect ( '/' . $this->_controller_path );
			return;
		}
		if ($id == 0) {
			$id = $row->id;
		}
		$rowArray = $row->toArray ();
		$this->view->title = $this->_item . ' ' . $rowArray [$this->_assigned_id_col];
		if ($this->_request->isPost ()) {
			$form->populate ( $formData );
		} else {
			$form->populate ( $rowArray );
		}
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
		$this->view->deleteURL = $this->view->url ( array ('controller' => $this->_controller_path, 'action' => 'delete', 'id' => $id ), null, true );
		$this->view->item_id = $id;
	}

	public function listAction() {
		$this->listItems ( $this->_table, 'id', $this->_assigned_id_col );
	}
}
