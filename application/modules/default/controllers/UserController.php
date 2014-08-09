<?php

class UserController extends mdb_Controller {

	const ACL_RESOURCE = 'default_user';

	protected $_json = array('list', 'listsearch');

	public function indexAction() {

	}

	public function loginAction() {

		$form = new forms_Login();

		$form->getElement('redir_uri')->setValue( $this->getRequest()->getParam( 'redir_uri' ) );
		// if redir_uri is login form, redir to /

		$formData = mdb_Globals::stripslashes( $this->_request->getPost() );
		if (! $this->getRequest()->isPost() || ! $form->isValid( $formData )) {
			$this->view->title = 'Log In';
			$this->view->forms_Login = $form;
			return;
		}

		$values = $form->getValues();

		$db = Zend_Db_Table::getDefaultAdapter();
		$authenticated = false;
		if ($db) {
			$authAdapter = new Zend_Auth_Adapter_DbTable( Zend_Db_Table::getDefaultAdapter(), 'users', 'username', 'password', 'MD5(?) AND active = TRUE' );
			$authAdapter->setIdentity( $values ['username'] )->setCredential( $values ['password'] );
			// Perform the authentication query, saving the result
			try {
				$result = $authAdapter->authenticate();
				$authenticated = $result->isValid();
			} catch (Exception $e) {
				mdb_Messages::add($e->getMessage(), 'error');
				mdb_Log::Write('unable to login: '.$e->__toString());
			}
		}

		if ($authenticated) {
			Zend_Auth::getInstance()->getStorage()->write( $authAdapter->getResultRowObject( array('id', 'username', 'role_id' ), null ) );
			if ($values ['redir_uri'] ) {
				$this->_helper->redirector->gotoUrl($values['redir_uri'], array('prependBase' => false) );
			} else {
				$this->_helper->redirector( 'index', '' );
			}
		} else {
			Zend_Auth::getInstance()->clearIdentity();
			$this->view->failedAuthentication = true;
			$form->getElement('username')->setValue('');
			$form->getElement('password')->setValue('');
			$this->view->forms_Login = $form;
			return;
		}

	}

	public function logoutAction() {
		Zend_Auth::getInstance()->clearIdentity();
		$this->_helper->redirector( 'index', '' );
	}

	public function listAction() {

		$this->listItems('users', 'id', 'username');

	}

	public function listsearchAction() {

		if ($this->_role_id == mdb_Acl::ROLE_ADMIN) {
			$this->listItems('users', 'id', 'username', 'exists (select * from searches where searches.user_id = users.id)');
		} else {
			$this->listItems('users', 'id', 'username', 'exists (select * from searches where public and searches.user_id = users.id)');
		}

	}

	private function saveSetting($setting, $value) {

		$model = new UserPrefs();

		// if setting = global default, don't save it
		if ($value == mdb_Globals::getDefaultUserPref($setting)) {
			$model->delete('user_id = '.$model->getAdapter()->quote($this->_user_id).' and preference = '.$model->getAdapter()->quote($setting));
			return;
		}
		$row = $model->find($this->_user_id, $setting)->current();
		if (! $row) {
			$row = $model->createRow();
			$row->user_id = $this->_user_id;
			$row->preference = $setting;
		}
		$row->value = $value;
		$row->save();
	}

	public function settingsAction() {

		$form = new forms_UserSettings();

		$formData = mdb_Globals::stripslashes($this->_request->getParams());
		if ($this->_request->isPost()) {
			if ($form->isValid($formData)) {
				try {
					$this->saveSetting('search.go.input.suggest', $form->search_go_input_suggest->getValue());
					$this->saveSetting('interface.table.expand', $form->interface_table_expand->getValue());
					mdb_Messages::add('settings saved');
				} catch (Exception $e) {
					mdb_Messages::add('unable to save some settings: ' . $e->getMessage(), 'error');
					mdb_Log::Write('unable to save: '.$e->__toString());
				}
				$this->_redirect('/user/settings/');
				return;
			}
		} else {
			$form->search_go_input_suggest->setValue(mdb_Globals::getUserPref('search.go.input.suggest'));
			$form->interface_table_expand->setValue(mdb_Globals::getUserPref('interface.table.expand'));
		}
		$this->view->title = 'Your Settings';
		$this->view->form = $form;
	}
}