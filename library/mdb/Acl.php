<?php

class mdb_Acl extends Zend_Acl {

	const ROLE_ADMIN = 1;

	protected static $_instance = null;

	protected function _initialize() {

		$roles = array();
		$resources = array();
		$permissions = array();

		$db = Zend_Db_Table::getDefaultAdapter();
		if ($db) {
			try {
				$roles = $db->fetchAll ( "SELECT id, parent_role_id FROM roles" );
				$resources = $db->fetchAll ( "SELECT module, controller FROM acl_resources" );
				$permissions = $db->fetchAll ( "SELECT role_id, module, controller, action, is_allowed FROM permissions" );
			} catch (Zend_Db_Exception $e) {
			    if ( ! strpos($e->getMessage(), 'SQLSTATE[HY000]') === 0 ) {
			        throw $e;
			    }
			}
		}

		foreach ( $roles as $role ) {
			$this->addRole ( new Zend_Acl_Role ( $role ['id'] ), $role ['parent_role_id'] );
		}

		$this->add(new Zend_Acl_Resource ('default_error'));

		foreach ( $resources as $resource ) {
			$this->add ( new Zend_Acl_Resource ( $resource ['module'] . '_' . $resource ['controller'] ) );
		}

		$this->deny ();

		foreach ( $permissions as $permit ) {
			$resource = $permit ['module'] . '_' . $permit ['controller'];
			if ($resource == '_') $resource = null;
			if (! $this->has($resource)) {
				$this->add(new Zend_Acl_Resource ($resource));
			}
			if ($permit ['is_allowed']) {
				$this->allow ( $permit ['role_id'], $resource, $permit ['action'] );
			} else {
				$this->deny ( $permit ['role_id'], $resource, $permit ['action'] );
			}
		}

	}

	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self ( );
			self::$_instance->_initialize ();
		}

		return self::$_instance;
	}

    public function isAllowed($role = null, $resource = null, $privilege = null) {

    	if (! $this->hasRole($role)) {
    		$role = null;
    	}
    	if (! $this->has($resource)) {
    		$resource = null;
    	}

    	return parent::isAllowed($role, $resource, $privilege);
	}

}