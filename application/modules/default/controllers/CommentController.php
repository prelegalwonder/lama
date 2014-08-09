<?php

class CommentController extends mdb_Controller {

    const ACL_RESOURCE = 'default_comment';

	protected $_ajax = array('view', 'new');

	public function indexAction() {
		// TODO: there is no centralized comment viewing facility?
	}

	public function viewAction() {
		$acl = mdb_Acl::getInstance ();
		$this->view->canEditComment = $acl->isAllowed ( $this->_role_id, self::ACL_RESOURCE, 'edit' );
		$this->view->canDeleteComment = $this->_role_id == mdb_Acl::ROLE_ADMIN || $acl->isAllowed ( $this->_role_id, self::ACL_RESOURCE, 'delete' );
		$this->view->role_id = $this->_role_id;

		$ref_table = $this->_request->getParam ( 'table' );
		$ref_item_id = $this->_request->getParam ( 'item' );

		if (! is_null($ref_table) && ! is_null($ref_item_id)) {
            $select = Zend_Db_Table::getDefaultAdapter()->select()
                ->from('comments')
                ->joinLeft('users', 'users.id = comments.user_id', 'username')
                ->where('ref_table = ?', $ref_table)
                ->where('ref_item_id = ?', $ref_item_id)
                ->order('modified_on desc');

            $this->view->comments = $select->query()->fetchAll();
		}
		$this->view->userid = $this->_user_id;
		$this->view->editCommentURL = $this->view->url ( array ('action' => 'edit', 'controller' => 'comment' ), null, true );
		$this->view->deleteCommentURL = $this->view->url ( array ('action' => 'delete', 'controller' => 'comment' ), null, true );
		if ($acl->isAllowed ( $this->_role_id, self::ACL_RESOURCE, 'new' )) {
			$this->render ();
			$this->_forward ( 'new' );
		}
	}

	public function editAction() {
		$this->view->title = "Edit Comment";

		$form = new forms_Comment ( );
		$this->view->form = $form;

		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		if ($this->_request->isPost ()) {
			// $formData = $this->_request->getPost ();
			if ($form->isValid ( $formData )) {
				$comments = new Comments ( );
				$id = ( int ) $form->getValue ( 'id' );
				$row = $comments->fetchRow ( 'id=' . $id );
				$row->comment = $form->getValue ( 'comment' );
				if ($this->_user_id == $row->user_id) {
					try {
						$row->save ();
						mdb_Messages::add ( 'comment saved' );
					} catch (Exception $e) {
						mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
						mdb_Log::Write('unable to save: '.$e->__toString());
					}
				} else {
					mdb_Messages::add ( 'You can only modify your own comments', 'error' );
				}

				$this->redirectTable($row->ref_table, $row->ref_item_id);
				return;
			} else {
				$form->populate ( $formData );
			}
		} else {
			$id = ( int ) $this->_request->getParam ( 'id', 0 );
			$this->view->deleteURL = $this->view->url ( array ('action' => 'delete', 'id' => $id ) );
			$comments = new Comments ( );
			$comment = $comments->fetchRow ( 'id=' . $id );
			if ($comment == null) {
				mdb_Messages::add ( 'there is no such comment', 'error' );
				$this->_redirect ( '/' );
				return;
			}
			$form->populate ( $comment->toArray () );

			if ($comment->modified_on == 0) {
				$this->view->modified_on = "sometime ago";
			} else {
				$this->view->modified_on = $comment->modified_on;
			}

		//			$form->setAction ( $this->view->url ( array ('controller' => 'comments', 'action' => 'edit' ), null, true ) );
		}
	}

	public function newAction() {
		$form = new forms_Comment ( );
		$form->submitComment->setLabel ( 'Add Comment' );
		$form->comment->setLabel ( 'Add Comment' );
		$this->view->form = $form;

		$ref_table = $this->_request->getParam ( 'table' );
		$ref_item_id = ( int ) $this->_request->getParam ( 'item' );

		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		// we use "newcomment" submit button id in the form. Unless controller is 'comments',
		// this action may have been invoked as result of failed validation in another form.
		if ($this->_request->isPost () && $this->_request->getParam('controller') == 'comment') {
			if ($form->isValid ( $formData ) && in_array($ref_table, array(Comments::STRAIN, Comments::MOUSE, Comments::LITTER, Comments::BREEDING_CAGE, Comments::HOLDING_CAGE, Comments::WEANING_CAGE, Comments::TRANSFER, Comments::PROTOCOL, Comments::SEARCH))) {
				$comments = new Comments ( );
				$row = $comments->createRow ();
				$row->user_id = $this->_user_id;
				$row->ref_table = $ref_table;
				$row->ref_item_id = $ref_item_id;
				$row->comment = $form->getValue ( 'comment' );
				$row->save ();

				mdb_Messages::add ( 'comment saved' );

				$this->redirectTable($row->ref_table, $row->ref_item_id);
			} else {
				$form->populate ( $formData );
			}
		} else {
			$form->setAction ( $this->view->url ( array ('controller' => 'comment', 'action' => 'new', 'table' => $ref_table, 'item' => $ref_item_id ), null, true ) );
		}
	}

	public function deleteAction() {
		$id = ( int ) $this->_request->getParam ( 'id' );
		if ($id) {
			$comments = new Comments ( );
			try {
				$comments->delete ( 'id=' . $id );
			} catch (Exception $e) {
				header('HTTP/1.1 500 '.$e->getMessage());
				die();
			}
		} else {
			header('HTTP/1.1 500 No comment specificed');
			die();
		}
	}

	private function redirectTable( $ref_table, $ref_item_id) {

		switch ($ref_table) {
			case Comments::STRAIN :
				$this->_redirect ( '/strain/view/id/' . $ref_item_id );
				break;
			case Comments::MOUSE :
				$this->_redirect ( '/mouse/view/id/' . $ref_item_id );
				break;
			case Comments::LITTER :
				$this->_redirect ( '/litter/view/id/' . $ref_item_id );
				break;
			case Comments::BREEDING_CAGE :
				$this->_redirect ( '/breeding-cage/view/id/' . $ref_item_id );
				break;
			case Comments::HOLDING_CAGE :
				$this->_redirect ( '/holding-cage/view/id/' . $ref_item_id );
				break;
			case Comments::WEANING_CAGE :
				$this->_redirect ( '/weaning-cage/view/id/' . $ref_item_id );
				break;
			case Comments::TRANSFER :
				$this->_redirect ( '/transfer/view/id/' . $ref_item_id );
				break;
			case Comments::PROTOCOL :
				$this->_redirect ( '/protocol/view/id/' . $ref_item_id );
				break;
			case Comments::SEARCH :
				$this->_redirect ( '/search/view/id/' . $ref_item_id );
				break;
			default:
				// this shouldn't happen
				$this->_redirect ( '/' );
		}
	}

}
