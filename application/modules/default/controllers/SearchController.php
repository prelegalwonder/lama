<?php
require_once 'MouseController.php';
require_once 'StrainController.php';
require_once 'LitterController.php';
require_once 'BreedingCageController.php';
require_once 'HoldingCageController.php';
require_once 'WeaningCageController.php';
require_once 'TransferController.php';

class SearchController extends mdb_Controller {

    const ACL_RESOURCE = 'default_search';
    const DELETE_OTHER = 'delete_other';

	protected $_json = array('listgo');

	public function indexAction() {
		$this->view->title = 'Search';

		$acl = mdb_Acl::getInstance();
		$this->view->canViewStrains = $acl->isAllowed( $this->_role_id, StrainController::ACL_RESOURCE, 'view' );
		$this->view->canViewMice = $acl->isAllowed( $this->_role_id, MouseController::ACL_RESOURCE, 'view' );
		$this->view->canViewLitters = $acl->isAllowed( $this->_role_id, LitterController::ACL_RESOURCE, 'view' );
		$this->view->canViewBreedingCages = $acl->isAllowed( $this->_role_id, BreedingCageController::ACL_RESOURCE, 'view' );
		$this->view->canViewHoldingCages = $acl->isAllowed( $this->_role_id, HoldingCageController::ACL_RESOURCE, 'view' );
		$this->view->canViewWeaningCages = $acl->isAllowed( $this->_role_id, WeaningCageController::ACL_RESOURCE, 'view' );
		$this->view->canViewTransfers = $acl->isAllowed( $this->_role_id, TransferController::ACL_RESOURCE, 'view' );

		$searches = new Searches();

		$select = Zend_Db_Table::getDefaultAdapter()->select()
			->from('searches',
				array('id', 'subject', 'title', 'public', 'lastmodified', 'user_id' ))
			->joinLeft('users',
				'searches.user_id = users.id',
				array('username'))
			->order(array('type', 'title', 'username'));

		$user_id = mdb_Globals::stripslashes($this->_request->getParam('user_id', $this->_user_id));
		if ($user_id == 'all') {
			$user_id = null;
		}
		$this->view->user_id = $user_id;

		$acl = mdb_Acl::getInstance();
		$aclResource = $this->getRequest()->getModuleName() . '_' . $this->getRequest()->getControllerName ();
		$this->view->canView = $acl->isAllowed( $this->_role_id, $aclResource, 'view' );

		if (null === $user_id) {
			$select->where( 'public or user_id = '.$searches->getAdapter()->quote($this->_user_id) );
		} elseif ($user_id == $this->_user_id) {
		    $select->where( 'user_id = '.$searches->getAdapter()->quote($this->_user_id) );
		} elseif ($this->_role_id == mdb_Acl::ROLE_ADMIN) {
		    $select->where( 'user_id = '.$searches->getAdapter()->quote($user_id) );
		} else {
		    $select->where( 'public and user_id = '.$searches->getAdapter()->quote($user_id) );
		}
		$this->view->searches = Zend_Db_Table::getDefaultAdapter()->fetchAll($select->assemble());
	}

	public function viewAction() {

		$this->view->title = 'Search';

		$acl = mdb_Acl::getInstance();
		$canViewStrains = $acl->isAllowed( $this->_role_id, StrainController::ACL_RESOURCE, 'view' );
		$canViewMice = $acl->isAllowed( $this->_role_id, MouseController::ACL_RESOURCE, 'view' );
		$canViewLitters = $acl->isAllowed( $this->_role_id, LitterController::ACL_RESOURCE, 'view' );
		$canViewBreedingCages = $acl->isAllowed( $this->_role_id, BreedingCageController::ACL_RESOURCE, 'view' );
		$canViewHoldingCages = $acl->isAllowed( $this->_role_id, HoldingCageController::ACL_RESOURCE, 'view' );
		$canViewWeaningCages = $acl->isAllowed( $this->_role_id, WeaningCageController::ACL_RESOURCE, 'view' );
		$canViewTransfers = $acl->isAllowed( $this->_role_id, TransferController::ACL_RESOURCE, 'view' );
		$canDeleteOtherSearches = $acl->isAllowed( $this->_role_id, self::ACL_RESOURCE, self::DELETE_OTHER );

		$params = mdb_Globals::stripslashes($this->_request->getParams());

		$show_sql = (mdb_Globals::stripslashes($this->_request->getParam('show_sql', 'no')) == 'yes');

        $searches = new Searches();
		if (array_key_exists('id', $params)) {
		    $id = $params['id'];
            $rows = $searches->find($id);
            if ($rows->count() == 0) {
    	        mdb_Messages::add('there is no such search', 'error');
    	        $this->_redirect('/search');
    	        return;
            }
            $row = $rows->current();
            if (! $row->public && $row->user_id != $this->_user_id && $this->_role_id != mdb_Acl::ROLE_ADMIN) {
    	        mdb_Messages::add('this search is not public', 'error');
    	        $this->_redirect('/search');
    	        return;
            }
            $subject = $row->subject;
            $this->view->title = ucwords($subject).' Search '.$row->title;
		} else {
            $subject = $params['subject'];
            $this->view->title = 'New '.ucwords($subject).' Search';
            $row = $searches->createRow();
		}

	   	switch ($subject) {
	        case Searches::SUBJECT_STRAIN:
	            if (! $canViewStrains) {
        	        mdb_Messages::add('Your user permissions do not allow you to view strains', 'error');
        	        $this->_redirect('/search');
	            }
	            $search = new mdb_Search_Strain('strain_search_form');
	            break;
	        case Searches::SUBJECT_LITTER:
	            if (! $canViewLitters) {
        	        mdb_Messages::add('Your user permissions do not allow you to view litters', 'error');
        	        $this->_redirect('/search');
	            }
	            $search = new mdb_Search_Litter('litter_search_form');
	            break;
	        case Searches::SUBJECT_MOUSE:
	            if (! $canViewMice) {
        	        mdb_Messages::add('Your user permissions do not allow you to view mice', 'error');
        	        $this->_redirect('/search');
	            }
	            $search = new mdb_Search_Mouse('mouse_search_form');
	            break;
	        case Searches::SUBJECT_BREEDING_CAGE:
	            if (! $canViewBreedingCages) {
        	        mdb_Messages::add('Your user permissions do not allow you to view breeding cages', 'error');
        	        $this->_redirect('/search');
	            }
	            $search = new mdb_Search_BreedingCage('breeding_search_form');
	            break;
	        case Searches::SUBJECT_HOLDING_CAGE:
	            if (! $canViewHoldingCages) {
        	        mdb_Messages::add('Your user permissions do not allow you to view holding cages', 'error');
        	        $this->_redirect('/search');
	            }
	            $search = new mdb_Search_HoldingCage('holding_search_form');
	            break;
	        case Searches::SUBJECT_WEANING_CAGE:
	            if (! $canViewWeaningCages) {
        	        mdb_Messages::add('Your user permissions do not allow you to view weaning cages', 'error');
        	        $this->_redirect('/search');
	            }
	            $search = new mdb_Search_WeaningCage('weaning_search_form');
	            break;
	        case Searches::SUBJECT_TRANSFER:
	            if (! $canViewTransfers) {
        	        mdb_Messages::add('Your user permissions do not allow you to view transfers', 'error');
        	        $this->_redirect('/search');
	            }
	            $search = new mdb_Search_Transfer('transfer_search_form');
	            break;
	        default:
    	        mdb_Messages::add('I do not know how to search for '.$subject, 'error');
    	        $this->_redirect('/search');
    	        return;
	    }

	    $form = $search->getForm();
	    if (! isset($id)) {
	        $form->removeElement('delete');
	    } else {
	    	if ($row->user_id == $this->_user_id || $canDeleteOtherSearches) {
	    		$this->view->search_id = $id;
	        } else {
	            $form->removeElement('delete');
	        }
	    }
	    if ($this->_request->IsPost()) {
	        if ($params['search_submit_reason'] == 'save' or $params['search_submit_reason'] == '') {
	            $form->getElement('search_title')->setRequired(true);
	    	    if (isset($id)) {
	    	        $form->getElement('search_title')->getValidator('UniqueValue')->id = $id;
	    	        $form->getElement('search_title')->getValidator('UniqueValue')->where = 'user_id = '.$this->_user_id;
	    	    }
	        } else {
	        	$form->getElement('search_title')->removeValidator('UniqueValue');
	        }
	        if ($form->isValid($params)) {
	        	$search->setLimit($params['search_result_limit']);
                if (count($search->getOutputFields()) == 0) {
                    $search->resetOutputFieldsToDefault();
                }
	            if ($params['search_submit_reason'] == 'search') { // form was submitted for searching
					ini_set('memory_limit', '64M');
	            	$this->view->fetch = true;
	                $this->view->select = $search->getSelect();
					$this->view->fields = $search->getOutputFields();
	            } elseif ($params['search_submit_reason'] == 'export') { // form was submitted for exporting to csv
	            	ini_set('memory_limit', '64M');
	            	$this->view->makeHyperlink = ($params['export_add_hyperlink'] != 'false');
	            	$this->view->select = $search->getSelect();
	                $this->view->fields = $search->getOutputFields();
	                $title = $form->getElement('search_title')->getValue();
	                if (! $title) {
	                    $title = 'search';
	                }
	                $this->view->title = $title;
	                $this->_helper->layout->disableLayout();
	                $this->render('export');
	                return;
	            } else { // form must be submitted for saving
	                // it user is trying to save another user's form, save it as new form belonging to this user
	                if (isset($id) && $row->user_id != $this->_user_id) {
	                    $row = $searches->createRow();
	                }
	                $row->user_id = $this->_user_id;
	                $row->title = $params['search_title'];
	                $row->public = $params['search_public'];
	                $row->limit = $params['search_result_limit'];
	                $row->subject = $subject;
	                $row->params = serialize($params['search_details']);
	                $row->output_fields = serialize($params['search_output_fields']);
	                try {
						$row->save ();
						mdb_Messages::add( 'search saved' );
					} catch (Zend_Db_Exception $e) {
						mdb_Messages::add( 'unable to save: ' . $e->getMessage(), 'error' );
						mdb_Log::Write('unable to save: '.$e->__toString());
					}
	                $this->_redirect('search/view/id/'.$row->id);
	            }
	        }
	    } else {
	        if (isset($id)) {
                $form->getElement('search_title')->setValue($row->title);
                $form->getElement('search_public')->setValue($row->public);
                if ($row->params) {
                    $form->getSubForm('search_details')->populate(unserialize($row->params));
                }
                if ($row->output_fields) {
                    $form->getSubForm('search_output_fields')->populate(unserialize($row->output_fields));
                }
                $search->setLimit($row->limit);
	        } else {
	            $form->getElement('search_public')->checked = true;
                $search->resetOutputFieldsToDefault();
	        }
            if (count($search->getOutputFields()) == 0) {
                mdb_Messages::add ( 'output columns reset to default', 'notice' );
            }
	    }

	    // if limit is not set, default to N
	    if (! $search->getLimit()) {
	        $search->setLimit(1000);
	    }
	    $this->view->search = $search;

	    if ($show_sql) {
	    	$this->view->sql = $search->assemble();
	    }
	}

	public function listgoAction() {

		$id = $this->_request->getParam('id');
		$item = $this->_request->getParam('item');

		$this->view->identifier = 'item';
		$this->view->label = 'item';
		if (! $id && (! $item || $item == '*')) {
			$this->view->items = array();
			return;
		}

		$db = Zend_Db_Table::getDefaultAdapter();

		$start = $this->_request->getParam('start', null);
		$count = $this->_request->getParam('count', 'Infinity');
		if ($count == 'Infinity') {
			$count = null;
		}

		if (is_null($id)) {
			$item = $this->_request->getParam('item', '%');
			if (substr($item, -1) == '*') {
				$item[strlen($item)-1]  = '%';
			}
			$where_snip = ' like '.$db->quote($item);
		} else {
			$item = null;
			$where_snip = ' = '.$db->quote($id);
		}

		$selectStrains = $db->select()
			->from('strains',
				array('strain_name as item' ))
			->where('strain_name '.$where_snip);

		$selectBreedingCages = $db->select()
			->from('cages',
				array('assigned_id as item' ))
			->where('cagetype = '.$db->quote(Cages::BREEDING).' and assigned_id '.$where_snip);

		$selectHoldingCages = $db->select()
			->from('cages',
				array('assigned_id as item' ))
			->where('cagetype = '.$db->quote(Cages::HOLDING).' and assigned_id '.$where_snip);

		$selectLitters = $db->select()
			->from('litters',
				array('assigned_id as item' ))
			->where('assigned_id '.$where_snip);

		$selectMice = $db->select()
			->from('mice',
				array('assigned_id as item' ))
			->where('assigned_id '.$where_snip);

		$selectWeaningCages = $db->select()
			->from('cages',
				array('assigned_id as item' ))
			->where('cagetype = '.$db->quote(Cages::WEANING).' and assigned_id '.$where_snip);

		$selectTags = $db->select()
			->from('tags',
				array('tag as item' ))
			->where('tag '.$where_snip)
			->distinct(true);

		$select = $db->select()->union(array($selectStrains, $selectBreedingCages, $selectHoldingCages, $selectLitters, $selectMice, $selectWeaningCages, $selectTags));

		$select->order('item')
			->limit($count, $start);

		$this->view->items = $select->query()->fetchAll();

	}

	public function goAction() {
		$query = mdb_Globals::stripslashes ( $this->_request->getParam ( 'goItem' ) );
		if ($query == Searches::GO_PLACEHOLDER) {
		    $query = '';
		}

		// see if given code matches any unique IDs in all tables, and go there directly.
		if ($query == '') {
			// nothing found
			mdb_Messages::add ( 'Nothing found.' );
			$this->_redirect ( '/' );
			return;
		}

		$db = Zend_Db_Table::getDefaultAdapter();

		// mouse
		$single_result = $db->fetchOne('select id from mice where assigned_id = '.$db->quote($query));
		if ($single_result) {
			$this->_redirect ( '/mouse/view/id/' . $single_result );
			return;
		}

		// litter
		$single_result = $db->fetchOne('select id from litters where assigned_id = '.$db->quote($query));
		if ($single_result) {
			$this->_redirect ( '/litter/view/id/' . $single_result );
			return;
		}

		// strain
		$single_result = $db->fetchOne('select id from strains where strain_name = '.$db->quote($query));
		if ($single_result) {
			$this->_redirect ( '/strain/view/id/' . $single_result );
			return;
		}

		// breeding
		$single_result = $db->fetchOne('select id from cages where cagetype = '.$db->quote(Cages::BREEDING).' and assigned_id = '.$db->quote($query));
		if ($single_result) {
			$this->_redirect ( '/breeding-cage/view/id/' . $single_result );
			return;
		}

		// holding
		$single_result = $db->fetchOne('select id from cages where cagetype = '.$db->quote(Cages::HOLDING).' and assigned_id = '.$db->quote($query));
		if ($single_result) {
			$this->_redirect ( '/holding-cage/view/id/' . $single_result );
			return;
		}

		// weaning
		$single_result = $db->fetchOne('select id from cages where cagetype = '.$db->quote(Cages::WEANING).' and assigned_id = '.$db->quote($query));
		if ($single_result) {
			$this->_redirect ( '/weaning-cage/view/id/' . $single_result );
			return;
		}

		// tags
		$single_result = $db->fetchOne('select count(*) from tags where tag = '.$db->quote($query));
		if ($single_result) {
			$this->_redirect ( '/tag/tagged/tag/' . urldecode($query) );
			return;
		}

		mdb_Messages::add ( 'Nothing found matching "'.$query.'"' );
		$this->_redirect ( '/' );
	}

	public function deleteAction() {

		$id = ( int ) $this->_request->getParam ( 'id' );

		if ($id) {
			$model = new Searches ( );

			$rows = $model->find ( $id );
			if ($rows->count ()) {
				$row = $rows->current ();
				try {
					$row->delete ();
					mdb_Messages::add ( 'search deleted' );
				} catch (Exception $e) {
					mdb_Messages::add ( $e->getMessage(), 'error' );
					mdb_Log::Write('unable to delete: '.$e->__toString());
					$this->_redirect ( '/search/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'there is no such search', 'error' );
			}
				$this->_redirect ( '/search' );
		} else {
			mdb_Messages::add ( 'no search selected for deletion', 'error' );
			$this->_redirect ( '/search' );
		}
	}

}
