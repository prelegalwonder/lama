<?php

class Admin_ErrorLogController extends mdb_Controller {

	public function indexAction() {

		$this->view->title = "Error Log";
		// $this->_helper->layout->setLayout('some other layout');

		// where is error log?
		if (Zend_Registry::isRegistered('system.logfile')) {
			$logfile = Zend_Registry::get('system.logfile');
			$this->view->logfile = $logfile;
			if (file_exists($logfile)) {
				$this->view->error_log = file_get_contents($logfile);
			}
		}
	}

	public function clearAction() {
		if (Zend_Registry::isRegistered('system.logfile')) {
			$logfile = Zend_Registry::get('system.logfile');
			unlink($logfile);
			$this->_redirect ( '/admin/error-log' );
		}
	}
}