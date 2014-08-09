<?php

require_once 'MouseController.php';
require_once 'StrainController.php';
require_once 'LitterController.php';
require_once 'BreedingCageController.php';
require_once 'HoldingCageController.php';
require_once 'WeaningCageController.php';
require_once 'TransferController.php';
require_once 'ProtocolController.php';
require_once 'SearchController.php';

class TagController extends mdb_Controller {

    const ACL_RESOURCE = 'default_tag';

	protected $_json = array('list');
	protected $_ajax = array('index', 'view');

	public function indexAction() {

		$this->view->title = "Tags";

		$db = Zend_Db_Table::getDefaultAdapter();

		$select = $db->select()
            ->from('tags',
                array('tag', 'count(*) as count'))
            ->group('tag');

        $user_id = $this->_request->getParam ('user_id', null);

        if ($user_id) {
            if ($user_id == $this->_user_id) {
                $username = 'you';
            } else {
                $username = $db->fetchOne('select username from users where id = '.$db->quote($user_id));
            }
            if ($username) {
                $select->where('user_id = '.$db->quote($this->_request->getParam ('user_id')));
                $this->view->title = 'Tags created by '.$username;
            }
        }

        if ($this->_request->getParam ('format') == 'html') {
            $select->limit(100);
        }

		$this->view->tags = $select->query()->fetchAll();

		$this->view->log_base = 10;
	}

	public function viewAction() {

		$acl = mdb_Acl::getInstance ();

		$this->view->canAddTag = $acl->isAllowed ( $this->_role_id, $this->getRequest ()->getModuleName () . '_tag', 'add' );
		$this->view->canRemoveTag = $acl->isAllowed ( $this->_role_id, $this->getRequest ()->getModuleName () . '_tag', 'remove' );
		$this->view->canDeleteTag = $acl->isAllowed ( $this->_role_id, $this->getRequest ()->getModuleName () . '_tag', 'delete' );

		$db = Zend_Db_Table::getDefaultAdapter();

		$ref_table = mdb_Globals::stripslashes($this->_request->getParam ( 'table' , null));
		$ref_item_id = mdb_Globals::stripslashes($this->_request->getParam ( 'item' , null));
		$rendercomments = (bool) mdb_Globals::stripslashes($this->_request->getParam ( 'comments' , false));

		$this->view->ref_table = $ref_table;
		$this->view->ref_item_id = $ref_item_id;
		$this->view->tags = $db->fetchAll ( 'select user_id, username, ref_item_id, ref_table, tag from tags t left join users u on t.user_id = u.id where ref_table = '.$db->quote($ref_table).' and ref_item_id = '.$db->quote($ref_item_id).' order by tag' );

		$this->view->userid = $this->_user_id;
		$this->view->removeTagURL = $this->view->url ( array ('action' => 'remove', 'controller' => 'tag' ), null, true );
		$this->view->deleteTagURL = $this->view->url ( array ('action' => 'delete', 'controller' => 'tag' ), null, true );

		if ($rendercomments) {
		    $this->view->add_hr = '<hr />';
    		$this->render();
    		$this->renderComments($ref_table, $ref_item_id);
		}
	}

	public function addAction() {

	    $db = Zend_Db_Table::getDefaultAdapter();

		$ref_table = mdb_Globals::stripslashes($this->_request->getParam ( 'table' , null));
		$ref_item_id = mdb_Globals::stripslashes($this->_request->getParam ( 'item' , null));
		$tag = trim(stripslashes($this->_request->getParam ( 'tag' )));

		if (! in_array($ref_table, array(Tags::STRAIN, Tags::MOUSE, Tags::LITTER, Tags::BREEDING_CAGE, Tags::HOLDING_CAGE, Tags::WEANING_CAGE, Tags::TRANSFER, Tags::PROTOCOL, Tags::SEARCH))) {
			mdb_Log::Write( 'I do not know how to tag table '.$ref_table);
		} elseif (! $db->fetchOne('select count(*) from '.$ref_table.' where id = '.$db->quote($ref_item_id))) {
			mdb_Log::Write( 'unable to add tag - requested item does not exist');
		} elseif (! $tag) {
			mdb_Log::Write( 'You must enter text for the tag');
		} else {
		    try {
    			$tags = new Tags ( );
    			$row = $tags->createRow ();
    			$row->user_id = $this->_user_id;
    			$row->ref_table = $ref_table;
    			$row->ref_item_id = $ref_item_id;
    			$row->tag = $tag;
    			$row->save ();
		    } catch (Exception $e) {
				mdb_Log::Write('unable to tag: '.$e->__toString());
		    }
		}
	}

	public function addselectedAction() {

	    $selectedItems = array();

		$ref_table = mdb_Globals::stripslashes($this->_request->getParam ( 'multi_table' , null));
    	$tag = trim(stripslashes($this->_request->getParam ( 'multi_tag' )));
		$redirect = trim(stripslashes($this->_request->getParam ( 'redirect' )));

		if (! in_array($ref_table, array(Tags::STRAIN, Tags::MOUSE, Tags::LITTER, Tags::BREEDING_CAGE, Tags::HOLDING_CAGE, Tags::WEANING_CAGE, Tags::TRANSFER, Tags::SEARCH))) {
            mdb_Messages::add ( 'I do not know how to tag table '.$ref_table, 'error');
		} elseif (! $tag) {
			mdb_Messages::add ( 'You must enter text for the tag', 'error');
		} else {
    		foreach ($this->_request->getParams() as $param => $value ) {
    			if ( substr($param,0,9) == 'selected-' && $value > 0 ) {
    				$selectedItems[] = $value;
    			}
    		}
			if (count($selectedItems)) {

        	    $db = Zend_Db_Table::getDefaultAdapter();

        		$already_tagged = false;
        	    foreach ($selectedItems as $ref_item_id) {

        		    try {
                		$db->insert('tags', array('ref_table' => $ref_table, 'ref_item_id' => $ref_item_id, 'tag' => $tag, 'user_id' => $this->_user_id));
        		    } catch (Zend_Db_Statement_Exception $e) {
        		        if ( strpos($e->getMessage(), 'SQLSTATE[23000]') === 0 ) {
        		            $already_tagged = true;
        		        } else {
							mdb_Log::Write('unable to tag selected: '.$e->__toString());
        		        	throw $e;
        		        }
        		    }
        		}
			    mdb_Messages::add ( 'items tagged');
                if ($already_tagged) {
    			    mdb_Messages::add ( 'some items were already tagged with '.$tag);
                }
			} else {
			    mdb_Messages::add ( 'no items selected', 'error' );
			}
		}

		$this->_redirect($redirect);
	}

	public function removeAction() {

		$db = Zend_Db_Table::getDefaultAdapter();

		$params = mdb_Globals::stripslashes($this->_request->getParams());

		$tags = new Tags ( );
		$tags->delete ( 'ref_table = '.$db->quote($params['table']).' and ref_item_id = '.$db->quote($params['item']).' and tag = '.$db->quote($params['tag']) );
	}

	public function listAction() {

		$db = Zend_Db_Table::getDefaultAdapter();

		$ref_table = mdb_Globals::stripslashes($this->_request->getParam('table', null));
	    $ref_item_id = mdb_Globals::stripslashes($this->_request->getParam('ref_id', null));

	    if ($ref_table && $ref_item_id) {
	        $where = 'not exists (select * from tags t2 where ref_table = '.$db->quote($ref_table).' and ref_item_id = '.$db->quote($ref_item_id).' and t2.tag = tags.tag)';
	    } else {
	        $where = null;
	    }

		$this->listItems('tags', 'tag', 'tag', $where);
	}

    public function taggedAction() {

		$db = Zend_Db_Table::getDefaultAdapter();

		$tag = mdb_Globals::stripslashes($this->_request->getParam ( 'tag' ));

		if (! $tag) {
		    $this->_redirect ( '/tag');
		}

		$this->view->title = 'Items tagged with '.$this->view->escape($tag);

		$acl = mdb_Acl::getInstance ();

		$canViewStrains = $acl->isAllowed( $this->_role_id, StrainController::ACL_RESOURCE, 'view' );
		$canViewMice = $acl->isAllowed( $this->_role_id, MouseController::ACL_RESOURCE, 'view' );
		$canViewLitters = $acl->isAllowed( $this->_role_id, LitterController::ACL_RESOURCE, 'view' );
		$canViewBreedingCages = $acl->isAllowed( $this->_role_id, BreedingCageController::ACL_RESOURCE, 'view' );
		$canViewHoldingCages = $acl->isAllowed( $this->_role_id, HoldingCageController::ACL_RESOURCE, 'view' );
		$canViewWeaningCages = $acl->isAllowed( $this->_role_id, WeaningCageController::ACL_RESOURCE, 'view' );
		$canViewTransfers = $acl->isAllowed( $this->_role_id, TransferController::ACL_RESOURCE, 'view' );
		$canViewSearches = $acl->isAllowed( $this->_role_id, SearchController::ACL_RESOURCE, 'view' );
		$canViewProtocols = $acl->isAllowed( $this->_role_id, ProtocolController::ACL_RESOURCE, 'view' );

		if ($canViewStrains) {
		    $selectStrains = $db->select ()
    		->from ( 'strains',
    			array('id', 'strain_name', 'pems', 'bems', 'promoter', 'esc_line', 'backbone_pems', 'reporter', 'lastmodified'))
            ->joinInner('tags',
                'tags.ref_item_id = strains.id and tags.ref_table = '.$db->quote(Tags::STRAIN), null)
            ->where('tags.tag = '.$db->quote($tag))
    		->order ( 'strain_name' );

    		$this->view->strains = $selectStrains->query()->fetchAll();
		}

		if ($canViewBreedingCages) {
		$selectBreeding = $db->select ()
    		->from ( 'cages',
    			array('id', 'assigned_id', 'protocol_id'))
    		->joinInner('breeding_cages',
    			'cages.id = breeding_cages.id',
    			array( 'breeding_type', 'mating_type', 'active', 'set_up_on', 'greatest(cages.lastmodified, breeding_cages.lastmodified) as lastmodified'))
            ->joinInner('tags',
                'tags.ref_item_id = cages.id and tags.ref_table = '.$db->quote(Tags::BREEDING_CAGE), null)
            ->where('tags.tag = '.$db->quote($tag))
    		->order ( 'assigned_id' );

    		$this->view->breeding_cages = $selectBreeding->query()->fetchAll();
		}

		if ($canViewHoldingCages) {
		$selectHolding = $db->select ()
    		->from ( 'cages',
    			array('id', 'assigned_id', 'protocol_id'))
    		->joinInner('holding_cages',
    			'cages.id = holding_cages.id',
    			array( 'active', 'set_up_on', 'greatest(cages.lastmodified, holding_cages.lastmodified) as lastmodified'))
            ->joinInner('tags',
                'tags.ref_item_id = cages.id and tags.ref_table = '.$db->quote(Tags::HOLDING_CAGE), null)
            ->where('tags.tag = '.$db->quote($tag))
    		->order ( 'assigned_id' );

    		$this->view->holding_cages = $selectHolding->query()->fetchAll();
		}

		if ($canViewLitters) {
    		$selectLitters = $db->select ()
    			->from ( 'litters',
    				array('id', 'assigned_id', 'born_on', 'weaned_on', 'alive_pups', 'strain_id', 'lastmodified'))
                ->joinInner('tags',
                    'tags.ref_item_id = litters.id and tags.ref_table = '.$db->quote(Tags::LITTER), null)
    			->joinLeft('strains',
    				'litters.strain_id = strains.id',
    				array( 'strain_name'))
                ->where('tags.tag = '.$db->quote($tag))
    			->order ( 'assigned_id' );

    		$this->view->litters = $selectLitters->query()->fetchAll();
		}

		if ($canViewMice) {
    		$selectMice = $db->select ()
        		->from ( 'mice')
                ->joinInner('tags',
                    'tags.ref_item_id = mice.id and tags.ref_table = '.$db->quote(Tags::MOUSE), null)
        		->joinLeft('strains',
        			'mice.strain_id = strains.id',
        			'strain_name')
                ->where('tags.tag = '.$db->quote($tag))
        		->order ( 'mice.assigned_id' );

    		$this->view->mice = $selectMice->query()->fetchAll();
		}

		if ($canViewWeaningCages) {
    		$selectWeaning = Zend_Db_Table::getDefaultAdapter ()->select ()
    			->from('weaning_cages',
    				array('id', 'sex', 'litter_id' ))
    			->joinInner('cages',
    				'weaning_cages.id = cages.id',
    				array('assigned_id', 'greatest(cages.lastmodified, weaning_cages.lastmodified) as lastmodified') )
                ->joinInner('tags',
                    'tags.ref_item_id = cages.id and tags.ref_table = '.$db->quote(Tags::WEANING_CAGE), null)
        		->joinLeft('litters',
    				'litters.id = weaning_cages.litter_id',
    				array('assigned_id as litter_assigned_id', 'weaned_on') )
                ->where('tags.tag = '.$db->quote($tag))
    			->order ( 'assigned_id' );

    		$this->view->weaning_cages = $selectWeaning->query()->fetchAll();
		}

		if ($canViewTransfers) {
    		$selectTransfers = $db->select()
    			->from('transfers',
    				array('id', 'mouse_id', 'transferred_on', 'user_id', 'from_cage_id', 'to_cage_id', 'notes' ))
               ->joinInner('tags',
                    'tags.ref_item_id = transfers.id and tags.ref_table = '.$db->quote(Tags::TRANSFER), null)
     			->joinLeft('mice',
    				'mice.id = transfers.mouse_id',
    				array('mice.assigned_id as mouse_assigned_id'))
    			->joinLeft('cages as cages_from',
    				'transfers.from_cage_id = cages_from.id',
    				array('cages_from.assigned_id as from_cage_assigned_id', 'cages_from.cagetype as from_cagetype'))
    			->joinLeft('cages as cages_to',
    				'transfers.to_cage_id = cages_to.id',
    				array('cages_to.assigned_id as to_cage_assigned_id', 'cages_to.cagetype as to_cagetype'))
    			->joinLeft('users',
    				'transfers.user_id = users.id',
    				array('username'))
                ->where('tags.tag = '.$db->quote($tag))
    			->order('transfers.id');;

    			$this->view->transfers = $selectTransfers->query()->fetchAll();
		}

		if ($canViewSearches) {
    		$selectSearches = $db->select()
    			->from('searches',
    				array('id', 'title', 'public', 'user_id', 'subject', 'type' ))
               ->joinInner('tags',
                    'tags.ref_item_id = searches.id and tags.ref_table = '.$db->quote(Tags::SEARCH), null)
    			->joinLeft('users',
    				'searches.user_id = users.id',
    				array('username'))
                ->where('(users.id = '.$db->quote($this->_user_id).' or searches.public) and tags.tag = '.$db->quote($tag))
    			->order('searches.id');;

    		$this->view->searches = $selectSearches->query()->fetchAll();
		}

		if ($canViewProtocols) {
    		$selectProtocols = $db->select ()
    		->from ( 'protocols',
    			array('id', 'protocol_name', 'lastmodified'))
            ->joinInner('tags',
                'tags.ref_item_id = protocols.id and tags.ref_table = '.$db->quote(Tags::PROTOCOL), null)
            ->where('tags.tag = '.$db->quote($tag))
    		->order ( 'protocol_name' );

            $this->view->protocols = $selectProtocols->query()->fetchAll();
		}
    }

	private function redirectTable( $ref_table, $ref_item_id) {

		switch ($ref_table) {
			case Tags::STRAIN :
				$this->_redirect ( '/strain/view/id/' . $ref_item_id );
				break;
			case Tags::MOUSE :
				$this->_redirect ( '/mouse/view/id/' . $ref_item_id );
				break;
			case Tags::LITTER :
				$this->_redirect ( '/litter/view/id/' . $ref_item_id );
				break;
			case Tags::BREEDING_CAGE :
				$this->_redirect ( '/breeding-cage/view/id/' . $ref_item_id );
				break;
			case Tags::HOLDING_CAGE :
				$this->_redirect ( '/holding-cage/view/id/' . $ref_item_id );
				break;
			case Tags::WEANING_CAGE :
				$this->_redirect ( '/weaning-cage/view/id/' . $ref_item_id );
				break;
			case Tags::TRANSFER :
				$this->_redirect ( '/transfer/view/id/' . $ref_item_id );
				break;
			case Tags::PROTOCOL :
				$this->_redirect ( '/protocol/view/id/' . $ref_item_id );
				break;
			case Tags::SEARCH :
				$this->_redirect ( '/search/view/id/' . $ref_item_id );
				break;
			default:
				// this shouldn't happen
				$this->_redirect ( '/' );
		}
	}

}
