<?php

require_once 'Zend/Controller/Plugin/Abstract.php';

class mdb_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract {

    static $user_active = false;

	public function preDispatch(Zend_Controller_Request_Abstract $request) {

		// bypass ACL checking for error pages
		if ($request->getControllerName() == 'error') {
			return;
		}

		// anyone can try to log in at any time
		if ($request->getControllerName() == 'user' && $request->getActionName() == 'login') {
			return;
		}

		switch ($request->getActionName()) {
			case 'list':
			case 'suggest':
			case 'listgo':
				return;
		}
		if (Zend_Auth::getInstance ()->hasIdentity ()) {
			$user_id = Zend_Auth::getInstance ()->getIdentity ()->id;
			$role_id = Zend_Auth::getInstance ()->getIdentity ()->role_id;
		} else {
			$user_id = null;
			$role_id = null;
		}
		$acl = mdb_Acl::getInstance ();

		if ($user_id && ! self::$user_active) {
			// is this user allowed to log in?
			try {
				$db = Zend_Db_Table::getDefaultAdapter ();
				if ( is_null($db) || ! $db->fetchOne('select count(*) from users where active and id = '.$db->quote($user_id))) {
					$error = 'You are not an active user. Please contact system administrator.';
					Zend_Auth::getInstance ()->clearIdentity ();
				} else {
				    self::$user_active = true;
				}
				if (! $request->getParam('format')) { // parameter "format" is present in queries that return json or ajax data
			        $db->update('users', array('last_seen' => date('Y-m-d H:i:s', time()), 'last_ip' => mdb_Globals::getClientIP()), 'id = '.$db->quote($user_id));
				}
			} catch (Exception $e) {
				$error = 'Unable to determine permissions: '.$e->getMessage();
			}
		}
		if (! isset($error)) {
			if (! ($role_id == null || $acl->hasRole ( $role_id ))) {
				$error = "Unable to determine permissions: requested user role '" . $role_id . "' does not exist.";
			} elseif (! $acl->isAllowed ( $role_id, $request->getModuleName () . '_' . $request->getControllerName (), $request->getActionName () )) {
				$error = "You are not allowed to perform this action. Contact the system administrator if you believe you should have access to this feature.";
			}
		}
		if (isset( $error )) {
			$request->setParam('error', $error)
				->setParam('redir_uri',$request->getRequestUri())
				->setModuleName('default')
				->setControllerName('error')
				->setActionName('permissions');
		}
	}
}