<?php

class Admin_InfoController extends mdb_Controller
{
    public function phpAction ()
    {
        $this->_helper->layout->disableLayout();
    }

        public function backupAction ()
    {
        $this->_helper->layout->disableLayout();

        $this->view->db_config = Zend_Db_Table::getDefaultAdapter()->getConfig();
    }

    public function systemAction ()
    {
        $this->view->title = "System Information";

		$db = Zend_Db_Table::getDefaultAdapter();

		$this->view->tables = $db->fetchAll('show table status');
		$this->view->mysql_version = $db->fetchOne('select version()');
		$this->view->mysql_database = $db->fetchOne('select database()');
		$this->view->mysql_uptime = $db->fetchRow('SHOW STATUS LIKE \'Uptime\'');
		$this->view->bzr_binary = Zend_Registry::get('system.versions.bzr.binary');
    }
}