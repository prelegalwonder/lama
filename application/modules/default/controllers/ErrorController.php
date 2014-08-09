<?php

class ErrorController extends mdb_Controller {

    const ACL_RESOURCE = 'default_error';

	public function errorAction() {
		$errors = $this->_getParam ( 'error_handler' );
		if (! $errors instanceof ArrayObject) {
			// we shouldn't be here.
			throw new Zend_Exception ( 'Error page called without error message' );
		}
		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER :
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION :
				// 404 error -- controller or action not found
				$this->getResponse ()->setRawHeader ( 'HTTP/1.1 404 Not Found' );
				$this->view->title = 'HTTP/1.1 404 Not Found';
				break;
			default :
				// application error; display error page, but don't change
				// status code
				$this->getResponse ()->clearBody ();
				$this->view->title = 'Application Error';
				break;
		}

		$this->view->message = $errors->exception->getMessage();

		// (try to) log error message
		if (! mdb_Log::Write($errors->exception->__toString()) ) {
			// if message cannot be logged, display stack trace on error page.
			$this->view->stacktrace = $errors->exception->getTraceAsString();
		}
//		try {
//			$logger = new mdb_Log();
//			$logger->log('(user id: '.$this->_user_id.') '.$errors->exception->__toString(), Zend_Log::ERR);
//			$logger = null;
//		} catch (Exception $e) {
//			mdb_Messages::add('Unable to log this error: '.$e->getMessage(), 'error');
//			$this->view->stacktrace = $errors->exception->getTraceAsString();
//		}
	}

	public function permissionsAction() {
		// if not logged in yet, redirect to login page
		if (Zend_Auth::getInstance()->hasIdentity()) {
			$this->view->title = 'Insufficient Permissions';
			$this->view->errorMessage = $this->getRequest()->getParam( 'error' );
		} else {
			$this->_forward( 'login', 'user', 'default' );
		}
	}

	public function dojoAction() {
		// dojo is not loaded, system will not work properly
		// explain the situation
		$this->view->title = 'Dojo Toolkit is not available';
		$this->view->isdojoerror = true;
	}
}