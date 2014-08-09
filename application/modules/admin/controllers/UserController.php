<?php

class Admin_UserController extends mdb_Controller {

	public function indexAction() {
        $this->view->title = 'Users';
        $db = Zend_Db_Table::getDefaultAdapter();
        $this->view->resolveIP = ( bool ) $this->_request->getParam( 'resolve_ip', false );
        $this->view->users = $db->fetchAll('select users.id, username, active, password is not null as has_password, last_seen, last_ip, role_name from users left join roles on users.role_id = roles.id order by last_seen desc, (active and has_password) desc, username');
    }

    public function deleteAction() {

		$this->view->title = "Delete User";

		$db = Zend_Db_Table::getDefaultAdapter();
		$id = ( int ) $this->_request->getParam( 'id' );

		if (! $id) {
		    mdb_Messages::add ( 'user not specified', 'error' );
		    $this->_redirect ( '/admin/user' );
		}
		if ($id == $this->_user_id) {
		    mdb_Messages::add ( 'You cannot delete yourself', 'error' );
		    $this->_redirect ( '/admin/user' );
		}

		$model = new Users();
		$rows = $model->find( $id );
		if (! $rows->count()) {
			mdb_Messages::add ( 'there is no such user', 'error' );
		    $this->_redirect ( '/admin/user' );
		}

		$delete_policy = $this->_request->getPost('delete_policy', Users::COMMENT_RESTRICT);
		try {
			// $row->delete ();
			$model->delete('id = '.$db->quote($id), $delete_policy);
			mdb_Messages::add ( 'user deleted' );
		} catch (Zend_Db_Exception $e) {
			mdb_Messages::add ( $e->getMessage(), 'error' );
			$this->_redirect ( '/admin/user/view/id/' . $id );
			return;
		}
		$this->_redirect ( '/admin/user' );
	}

	public function newAction() {

		$this->view->title = "New User";

		$form = new forms_User();
		$form->removeElement('delete');
		$this->view->form = $form;

		$formData = mdb_Globals::stripslashes ( $this->_request->getParams () );
		if ($this->_request->isPost ()) {
			if ($form->isValid ( $formData )) {
				$users = new Users ( );
				$row = $users->createRow ();
				foreach ( $form->getValues () as $key => $value ) {
					if ($row->__isset ( $key ) && ! in_array ( $key, array ('id' ) )) {
						$row->__set ( $key, $value );
					}
				}
				try {
					$row->save ();
					mdb_Messages::add ( 'added user "' . $row->username . '"' );
				} catch ( Exception $e ) {
					mdb_Messages::add ( 'unable to save: ' . $e->getMessage (), 'error' );
				}

				$this->_redirect ( '/admin/user/view/id/' . $row->id );
				return;
			} else {
				$form->populate ( $formData );
			}
		} else {
			$form->active->setValue(true);
			$form->role_id->setValue(2);
		}

	}

	public function viewAction() {
		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canSave = $acl->isAllowed ( $this->_role_id, $aclResource, 'save' );
		$this->view->canDelete = $acl->isAllowed ( $this->_role_id, $aclResource, 'delete' );

		$db = Zend_Db_Table::getDefaultAdapter();

		$form = new forms_User ( );
		$form->getElement('username')
		    ->setAttrib('disabled', '+disabled+')
		    ->addDecorator('Lock')
    		    ->getDecorator('HtmlTag')
    		    ->setOption('style', 'display:inline;');
    	if (! $this->view->canDelete) {
    			$form->removeElement('delete');
    	}

		$formData = mdb_Globals::stripslashes ( $this->_request->getParams () );

		if ($this->_request->isPost ()) {
			if ($this->view->canSave) {
				if (! array_key_exists('username', $formData)) {
				     $form->removeElement('username');
				} else {
			        $form->getElement( 'username' )->getValidator( 'UniqueValue' )->id = $formData ['id'];
				}
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$users = new Users ( );
					$rows = $users->find ( $id );
					// does this user exist?
					if ($rows->count () == 0) {
						mdb_Messages::add ( 'there is no such user', 'error' );
						$this->_redirect ( '/admin/user' );
						return;
					}
					$row = $rows->current ();
					$ignore_fields = array ('id', 'password');
					if ($this->_user_id == $form->getValue('id')) {
						// cannot change your own role
						array_push($ignore_fields, 'role_id', 'active');
					}
					foreach ( $form->getValues () as $key => $value ) {
						if ($row->__isset ( $key ) && ! in_array ( $key, $ignore_fields )) {
							if (in_array ( $key, array ('email' ) ) && $value == '') {
								$value = null;
							}
							$row->__set ( $key, $value );
						}
					}
					if ($form->getValue('password')) {
						$row->password = md5($form->getValue('password'));
					}
					try {
						$row->save ();
						mdb_Messages::add ( 'saved user "' . $row->username . '"' );
					} catch ( Exception $e ) {
						mdb_Messages::add ( 'unable to save: ' . $e->getMessage (), 'error' );
					}

					// redirect anyway: Post/Redirect/Get pattern
					$this->_redirect ( '/admin/user/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify users', 'error' );
			}
		}

		// we are here because user wants to view user, or maybe after save, failed or not
		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$username = $this->_request->getParam ( 'username', '' );
		if ($id > 0 || $username != '') {
			$users = new Users ( );
			if ($id) {
				$where = 'id = '.$db->quote($id);
			} else {
				$where = 'username = ' . $db->quote($username);
			}
			$row = $users->fetchRow ( $where );
			// does this user exist?
			if (is_null ( $row )) {
				mdb_Messages::add ( 'there is no such user', 'error' );
				$this->_redirect ( '/admin/user' );
				return;
			}
			if ($id == 0) {
				$id = $row->id;
			}
			if ($this->_user_id == $id) {
				$form->role_id->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
				$form->active->setAttrib('readonly', true)->setAttrib('style', 'color:gray;');
				$this->view->canDelete = false;
			}
			$this->view->title = 'User ' . $row->username;
			if ($this->_request->isPost ()) {
				$form->populate ( $formData );
			} else {
				$form->populate ( $row->toArray () );
				$form->password->setValue('');
			}
			$this->view->deleteURL = $this->view->url ( array ('module' => 'admin', 'controller' => 'user', 'action' => 'delete', 'id' => $id ), null, true );

			// $this->render ();
			// $this->renderTags(Tags::STRAIN, $id, true);
		} else {
			// no user id specified in URL
			$this->_redirect ( '/admin/user' );
			return;
		}
	}

}