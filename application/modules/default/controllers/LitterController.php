<?php
require_once 'SearchController.php';

class LitterController extends mdb_Controller {

    const ACL_RESOURCE = 'default_litter';

	protected $_table = 'litters';
	protected $_json = array('list', 'suggest');
	protected $_suggestFields = array('generation');

    public function indexAction() {
		$this->view->title = "Litters";

		$acl = mdb_Acl::getInstance ();
		$this->view->canSearch = $acl->isAllowed ( $this->_role_id, SearchController::ACL_RESOURCE, 'view' );

		$select = Zend_Db_Table::getDefaultAdapter()->select()
			->from('litters',
				array('id', 'assigned_id', 'born_on', 'weaned_on', 'alive_pups', 'lastmodified', 'strain_id' ))
			->joinLeft('strains',
				'litters.strain_id = strains.id',
				array('strain_name'))
			->order('litters.lastmodified desc')
			->limit(20);

		$this->view->recently_modified_litters = $select->query()->fetchAll();
	}

	public function deleteAction() {

		$this->view->title = "Delete Litter";

		$litters = new Litters ( );

		$id = ( int ) $this->_request->getParam( 'id' );
		$rows = $litters->find ( $id );
		if ($rows->count()) {
			$row = $rows->current ();
		} else {
			mdb_Messages::add ( 'there is no such litter', 'error' );
			$this->_redirect ( '/litter' );
		}

		try {
			$row->delete ();
			mdb_Messages::add ( 'litter deleted' );
		} catch (Exception $e) {
			mdb_Messages::add ( $e->getMessage(), 'error' );
			mdb_Log::Write('unable to delete: '.$e->__toString());
			$this->_redirect ( '/litter/view/id/' . $id );
			return;
		}
		$this->_redirect ( '/litter' );
	}

	public function listAction() {
	    $this->listItems();
	}

	public function newAction() {
		$this->view->title = "New Litter";

		$form = new forms_Litter ( );
		$form->removeElement('weaned_on');
		$form->removeElement('delete');

		$this->view->form = $form;

		$formData = mdb_Globals::stripslashes($this->_request->getParams());

		if ($this->_request->isPost ()) {
			if ($form->isValid ( $formData )) {
				$mice = new Litters ( );
				$row = $mice->createRow ();
				foreach ( $form->getValues() as $key => $value ) {
					if ( $row->__isset($key) && ! in_array($key, array('id', 'user_id', 'lastmodified')) ) {
						if (in_array($key, array('born_on', 'weaned_on', 'mother2_id', 'mother3_id', 'total_pups', 'alive_pups', 'strain_id', 'protocol_id')) and $value == '') {
							$value = null;
						}
						$row->__set($key, $value);
					}
				}
				// automatically "wean" litters with no living pups
				if ($row->alive_pups == 0 && ! is_null($row->alive_pups)) {
					$row->weaned_on = date('Y-m-d');
					$row->weaned_female_count = 0;
					$row->weaned_male_count = 0;
					$row->holding_female_count = 0;
					$row->holding_male_count = 0;
					$row->sacrificed_female_count = 0;
					$row->sacrificed_male_count = 0;
					$row->sacrificed_nosex_count = 0;
					$row->not_viable = false;
					mdb_Messages::add ( 'litter automatically weaned with zero pups alive', 'notice' );
				}
				$row->user_id = $this->_user_id;
				$row->save ();

				mdb_Messages::add ( 'created litter "' . $form->getValue ( 'assigned_id' ) . '"' );

				// $this->_redirect ( '/litter/view/id/' . $row->id );
				$this->_redirect ( '/breeding-cage/view/id/'.$row->breeding_cage_id );
				return;
			} else {
				$form->populate ( $formData );
			}
		}

		// not saving yet

		// we must have a valid breeding cage to create a litter
		$cage_id = $this->_request->getParam ( 'cage', 0 );

		$db = Zend_Db_Table::getDefaultAdapter ();

		$cage_details = $db->fetchRow('select assigned_id, active, protocol_id, breeding_type, mating_type, assigned_stud_id from cages inner join breeding_cages on cages.id = breeding_cages.id where cages.id = '.$cage_id);

		if (! $cage_details ) {
			mdb_Messages::add ( 'requested cage does not exist' );
			$this->_redirect ( '/breeding-cage' );
			return;
		}

		if (! $cage_details['assigned_stud_id'] ) {
			mdb_Messages::add ( 'there is no stud assigned to this cage' );
			$this->_redirect ( '/breeding-cage/view/id/'.$cage_id );
			return;
		}

		// do not require studs to be alive - they may have sired this litter before dying
		/*
		$father = $db->fetchRow('select * from mice where is_alive and id = ' . $cage_details['assigned_stud_id']);
		if (! $father ) {
			mdb_Messages::add ( 'there is no living stud assigned to this cage' );
			$this->_redirect ( '/breeding-cage/view/id/'.$cage_id );
			return;
		}
		*/

		$mothers = $db->fetchAll('select * from mice where sex = "F" and is_alive and cage_id = ' . $cage_id);
		if ( count($mothers) == 0 ) {
			mdb_Messages::add ( 'there are no living female breeders assigned to this cage' );
			$this->_redirect ( '/breeding-cage/view/id/'.$cage_id );
			return;
		}

		$form->breeding_cage_id->setValue($cage_id);

		$form->strain_id->setValue(0);

		$next_sequence = 1;
		// parse out breeding cage "number" - ie, in 5746(4), we want 5746.
		$matches = array();
		if ( eregi('^[0-9A-Za-z]+', $cage_details['assigned_id'], $matches) ) {
			$cage_root_number = $matches[0];

			// now find all litters with thar root
			$litter_list = $db->fetchCol('select assigned_id from litters where assigned_id regexp ' . $db->quote($cage_root_number .'[(-]'));
			// this would give us array like 5746(1)-1 5746(3)-2 etc.
			// we are only interested in last digits after -.
			foreach ($litter_list as $used_litter_id) {
				$matches = array();
				if (eregi('[0-9]*$', $used_litter_id, $matches)) {
					if (intval($matches[0]) >= $next_sequence) {
						$next_sequence = intval($matches[0]) + 1;
					}
				}
			}
		}

		$form->assigned_id->setValue($cage_details['assigned_id'].'-'.$next_sequence);

		$form->born_on->setValue(date('Y-m-d'));
		$form->father_id->setValue($cage_details['assigned_stud_id']);
		$parentIds = array();
		if ($cage_details['assigned_stud_id']) {
			$parentIds[] = $cage_details['assigned_stud_id'];
		}
		$mother_count = 0;
		foreach ($mothers as $mother) {
			$mother_count++;
			$parentIds[] = $mother['id'];
			switch ($mother_count) {
				case 1:
					$form->mother_id->setValue($mother['id']);
					break;
				case 2:
					$form->mother2_id->setValue($mother['id']);
					break;
				case 3:
					$form->mother3_id->setValue($mother['id']);
					break;
				default:
					throw new Exception('Too many mothers in this litter');
			}
		}

		// list litter parents
		if (count($parentIds)) {
			$parents = $db->select()
			->from('mice',
				array('id', 'assigned_id', 'sex', 'is_alive', 'strain_id', 'genotype', 'born_on', 'generation', 'datediff(curdate(), mice.born_on) as born_days' ))
			->joinLeft('strains',
				'mice.strain_id = strains.id',
				array('strain_name', 'promoter'))
			->where('mice.id in ('.implode(',', $parentIds).')')
			->order(array('sex', 'mice.id'));

			$this->view->parents = $parents->query()->fetchAll();

			// hack: look for first parent with strain starting with mems, and if there is one, set that as default strain

			// if all parents have same strain, use that for litter strain
			$common_strain = $this->view->parents[0]['strain_id'];
			foreach ($this->view->parents as $parent) {
				if ($parent['strain_id'] != $common_strain) {
					$common_strain = null;
				}
				if ( stripos($parent['strain_name'], 'mems') === 0 ) {
					$form->strain_id->setValue($parent['strain_id']);
				}
			}
			if ( $common_strain ) {
				$form->strain_id->setValue($common_strain);
			}

		} else {
			$this->view->parents = array();
		}
	}

	public function viewAction() {
		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canSave = $acl->isAllowed ( $this->_role_id, $aclResource, 'save' );
		$this->view->canDelete = $acl->isAllowed ( $this->_role_id, $aclResource, 'delete' );
		$this->view->canEditParents = $acl->isAllowed ( $this->_role_id, $aclResource, 'editparents' );

		$db = Zend_Db_Table::getDefaultAdapter();

		$form = new forms_Litter ( );
		$form->getElement('assigned_id')
		    ->setAttrib('disabled', '+disabled+')
		    ->addDecorator('Lock')
    		    ->getDecorator('HtmlTag')
    		    ->setOption('style', 'display:inline;');
		$form->removeElement('total_pups');
		$form->removeElement('alive_pups');
    	if (! $this->view->canDelete) {
    			$form->removeElement('delete');
    	}

		if ($this->_request->isPost ()) {
			if ($this->view->canSave) {
				$formData = $this->_request->getPost ();
				if (! array_key_exists('assigned_id', $formData)) {
				     $form->removeElement('assigned_id');
				} else {
    				$form->getElement('assigned_id')->getValidator('UniqueValue')->id = $formData['id'];
				}
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$litters = new Litters ( );
					$rows = $litters->find ( $id );
					// does this litter exist?
					if ($rows->count () == 0) {
						mdb_Messages::add ( 'there is no such litter', 'error' );
						$this->_redirect ( '/litter' );
						return;
					}
					$row = $rows->current ();
					// are we going to need to update mice?
					$updateMice = ($form->born_on->getValue() != $row->born_on or $form->weaned_on->getValue() != $row->weaned_on or $form->strain_id->getValue() != $row->strain_id or $form->generation->getValue() != $row->generation);
					$protocolChanged = ($form->protocol_id->getValue() != $row->protocol_id);
					foreach ( $form->getValues() as $key => $value ) {
						if ( $row->__isset($key) && ! in_array($key, array('id', 'user_id', 'lastmodified')) ) {
							if (in_array($key, array('born_on', 'weaned_on', 'mother2_id', 'mother3_id')) && $value == '') {
								$value = null;
							}
							$row->__set($key, $value);
						}
					}
					$row->user_id = $this->_user_id;
					try {
						$row->save ();
						mdb_Messages::add ( 'saved litter "' . $row->assigned_id . '"' );
						if ($protocolChanged && $row->weaned_on) {
							mdb_Messages::add ( 'if needed, also update protocol for each mouse in this litter' );
						}
						if ($updateMice) {
							$db->update('mice', array('born_on' => $row->born_on, 'weaned_on' => $row->weaned_on, 'strain_id' => $row->strain_id, 'generation' => $row->generation, 'user_id' => $row->user_id), 'litter_id = '.$db->quote($id));
						}
					} catch (Exception $e) {
						mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
						mdb_Log::Write('unable to save: '.$e->__toString());
					}

					// redirect anyway: Post/Redirect/Get pattern
					$this->_redirect ( '/litter/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify litters', 'error' );
				$this->_redirect ( '/litter/view/id/' . $id );
			}
		}

		if (! $this->view->canSave) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$assigned_id = $this->_request->getParam ( 'litter', '' );
		if ($id > 0 || $assigned_id != '') {
			$litters = new Litters ( );
			if ($id) {
				$where = 'id ='.$id;
			} else {
				$where = 'assigned_id = ' . $db->quote($assigned_id);
			}
			$row = $litters->fetchRow($where);
			// does this litter exist?
			if (is_null($row)) {
				mdb_Messages::add ( 'there is no such litter', 'error' );
				$this->_redirect ( '/litter' );
				return;
			}
			$this->view->title = 'Litter ' . $row->assigned_id;

			if ($id == 0) {
				$id = $row->id;
			}
			if (! $row->weaned_on) {
				$form->removeElement('weaned_on');
			}
			$this->view->id = $id;
			if ($this->_request->isPost ()) {
				$form->populate ( $formData );
			} else {
				$form->populate ( $row->toArray () );
			}
			$lastmodifiedby = $form->getElement('user_id')->getValue();
			if (! $lastmodifiedby) {
				$this->view->lastmodifiedby = "nobody in particular";
			} elseif ($lastmodifiedby == $this->_user_id) {
				$this->view->lastmodifiedby = 'you';
			} else {
				$this->view->lastmodifiedby = $db->fetchOne ( "SELECT username from users where id = " . $lastmodifiedby );
			}
			$lastmodified = $form->getElement('lastmodified')->getValue();
			if ($lastmodified == 0) {
				$this->view->lastmodified = "sometime ago";
			} else {
				$this->view->lastmodified = $lastmodified;
			}
			$this->view->deleteURL = $this->view->url ( array ('controller' => 'litter', 'action' => 'delete', 'id' => $id ), null, true );

			// list weaning details
			$this->view->litter = $row;

			// 3 week and 6 week dates
			if ($row->born_on) {
				$this->view->weeksinfo = 'This litter is 3 weeks old on '.mdb_Globals::formatDateTime(strtotime($row->born_on) + 3 * 7 * 24 * 60 * 60).', 6 weeks old on '.mdb_Globals::formatDateTime(strtotime($row->born_on) + 6 * 7 * 24 * 60 * 60);
			}

			// get mice - should be done as a view?
			$mice_select = $db->select()
				->from('mice',
					array('id', 'sex', 'is_alive', 'status', 'genotype', 'assigned_id', 'cage_id', 'lastmodified' ))
				->joinLeft('strains',
					'mice.strain_id = strains.id',
					array('strain_name'))
				->joinLeft('cages',
					'mice.cage_id = cages.id',
					array('assigned_id as cage_assigned_id', 'cagetype'))
				->where('litter_id = ' . $id)
				->order(array('sex', 'mice.id'));

			$this->view->mice = $mice_select->query()->fetchAll();

			$this->view->breeding_cage_id = $row->breeding_cage_id;
			if ($this->view->breeding_cage_id) {
				$this->view->breeding_cage_name = $db->fetchOne ( "select assigned_id from cages where id = " . $row->breeding_cage_id );
			}

			// list litter parents
			$parentIds = array();
			if ($row->father_id) {
				$parentIds[] = $row->father_id;
			}
			if ($row->mother_id) {
				$parentIds[] = $row->mother_id;
			}
			if ($row->mother2_id) {
				$parentIds[] = $row->mother2_id;
			}
			if ($row->mother3_id) {
				$parentIds[] = $row->mother3_id;
			}
			if (count($parentIds)) {
				$parents = $db->select()
				->from('mice',
					array('id', 'assigned_id', 'sex', 'is_alive', 'strain_id', 'genotype', 'born_on', 'generation', 'datediff(curdate(), mice.born_on) as born_days' ))
				->joinLeft('strains',
					'mice.strain_id = strains.id',
					array('strain_name', 'promoter'))
				->where('mice.id in ('.implode(',', $parentIds).')')
				->order(array('sex', 'mice.id'));

				$this->view->parents = $parents->query()->fetchAll();
			} else {
				$this->view->parents = array();
			}

			// get related litters
			// all litters should have father or breeding cage, but just in case...
			$where_snippets = array();
			if ($row->father_id) {
				$where_snippets[] = 'father_id = '.$row->father_id;
			}
			if ($row->breeding_cage_id) {
				$where_snippets[] = 'breeding_cage_id = '.$row->breeding_cage_id;
			}
			if (count($where_snippets)) {
				$where = implode(' or ', $where_snippets);
			} else {
				$where = 'false';
			}
			$related_litters = $db->select()
				->from('litters',
					array('id', 'assigned_id' ))
				->where($where)
				->order(array('born_on', 'assigned_id'));

			$this->view->related_litters = $related_litters->query()->fetchAll();
			// we don't actually want to list anything for 1 match, which will be this litter
			if (count($this->view->related_litters) == 1) {
				$this->view->related_litters = array();
			}
		} else {
			// no litter id specified in URL
			$this->_redirect ( '/litter' );
			return;
		}
	}

	public function editparentsAction() {

		$acl = mdb_Acl::getInstance ();
		$aclResource = $this->getRequest ()->getModuleName () . '_' . $this->getRequest ()->getControllerName ();
		$this->view->canEditParents = $acl->isAllowed ( $this->_role_id, $aclResource, 'editparents' );

		$db = Zend_Db_Table::getDefaultAdapter();

		$form = new forms_LitterParents ( );

		if ($this->_request->isPost ()) {
			if ($this->view->canEditParents) {
				$formData = $this->_request->getPost ();
				// $form->getElement('assigned_id')->getValidator('UniqueValue')->id = $formData['id'];
				if ($form->isValid ( $formData )) {
					$id = ( int ) $form->getValue ( 'id' );
					$litters = new Litters ( );
					$rows = $litters->find ( $id );
					// does this litter exist?
					if ($rows->count () == 0) {
						mdb_Messages::add ( 'there is no such litter', 'error' );
						$this->_redirect ( '/litter' );
						return;
					}
					$row = $rows->current ();
					foreach ( $form->getValues() as $key => $value ) {
						if ( $row->__isset($key) && ! in_array($key, array('id', 'user_id', 'lastmodified')) ) {
							if (in_array($key, array('father', 'mother_id', 'mother2_id', 'mother3_id')) && $value == '') {
								$value = null;
							}
							$row->__set($key, $value);
						}
					}
					$row->user_id = $this->_user_id;
					try {
						$row->save ();
						mdb_Messages::add ( 'saved parents for litter "' . $row->assigned_id . '"' );
					} catch (Exception $e) {
						mdb_Messages::add ( 'unable to save: ' . $e->getMessage(), 'error' );
						mdb_Log::Write('unable to save: '.$e->__toString());
					}

					// redirect to regular view
					$this->_redirect ( '/litter/view/id/' . $id );
					return;
				}
			} else {
				mdb_Messages::add ( 'You are not allowed to modify litter parents', 'error' );
				$this->_redirect ( '/litter/view/id/' . $id );
			}
		}

		if (! $this->view->canEditParents) {
			$form->removeElement ( 'submit' );
		}
		$this->view->form = $form;

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$assigned_id = $this->_request->getParam ( 'litter', '' );
		if ($id > 0 || $assigned_id != '') {
			$litters = new Litters ( );
			if ($id) {
				$where = 'id ='.$id;
			} else {
				$where = 'assigned_id = \'' . $assigned_id . '\'';
			}
			$row = $litters->fetchRow($where);
			// does this litter exist?
			if (is_null($row)) {
				mdb_Messages::add ( 'there is no such litter', 'error' );
				$this->_redirect ( '/litter' );
				return;
			}
			$this->view->title = 'Litter ' . $row->assigned_id . ' Parents';

			if ($id == 0) {
				$id = $row->id;
			}
			$this->view->id = $id;
			if ($this->_request->isPost ()) {
				$form->populate ( $formData );
			} else {
				$form->populate ( $row->toArray () );
			}
			$lastmodifiedby = $form->getElement('user_id')->getValue();
			if (! $lastmodifiedby) {
				$this->view->lastmodifiedby = "nobody in particular";
			} elseif ($lastmodifiedby == $this->_user_id) {
				$this->view->lastmodifiedby = 'you';
			} else {
				$this->view->lastmodifiedby = $db->fetchOne ( "SELECT username from users where id = " . $lastmodifiedby );
			}
			$lastmodified = $form->getElement('lastmodified')->getValue();
			if ($lastmodified == 0) {
				$this->view->lastmodified = "sometime ago";
			} else {
				$this->view->lastmodified = $lastmodified;
			}

		} else {
			// no litter id specified in URL
			$this->_redirect ( '/litter' );
			return;
		}
	}

	public function weanAction() {

		// this action displays wean options page or does actual weaning, when submitted by post

		// wean options page - display how many alive mice, males, females. ask how many to sac right away and how many to
		// put into a new weaning cage, etc

		if (! $this->_request->isPost ()) {
			// we shouldn't be here unless this is a result of a posted form.
			$this->_redirect ( '/litter' );
		}

		$formData = $this->_request->getPost ();
		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		// $id = ( int ) $formData['id'];

		// do weaning

		$db = Zend_Db_Table::getDefaultAdapter ();

		// get litter info
		$litterRow = $db->fetchRow('select * from litters where id = ' . $id);
		// is there such a litter?
		if (! $litterRow) {
			mdb_Messages::add ( 'there is no such litter', 'error' );
			$this->_redirect ( '/litter' );
			return;
		}
		if ( $litterRow['weaned_on'] ) {
			mdb_Messages::add ( 'this litter was already weaned' );
			$this->_redirect ( '/litter/view/id/' . $id );
			return;
		}

		$assigned_id = $litterRow['assigned_id'];
		$protocol_id = $litterRow['protocol_id'];
		$born_on = $litterRow['born_on'];
		$generation = $litterRow['generation'];
		$strain_id = $litterRow['strain_id'];

		$weaned_on = $formData['new_weaned_on'];
		if ($weaned_on == '') {
			$weaned_on = date('Y-m-d', strtotime($litterRow['born_on']) + 3 * 7 * 24 * 60 * 60);
		}

		$not_viable = $formData['not_viable'];

		if ($not_viable) {
			$db->update('litters', array(
					'weaned_on' => $weaned_on,
					'total_pups' => null,
					'alive_pups' => null,
					'weaned_female_count' => null,
					'weaned_male_count' => null,
					'holding_female_count' => null,
					'holding_male_count' => null,
					'sacrificed_female_count' => null,
					'sacrificed_male_count' => null,
					'sacrificed_nosex_count' => null,
					'not_viable' => true,
					'user_id' => $this->_user_id,
			), 'id = '.$db->quote($id));
			mdb_Messages::add ( 'litter flagged as not counted' );

		} elseif ($formData['wean_submit'] == 'Save Born & Alive') {

			$total_pups = intval($formData['total_pups']);
			if ($total_pups <= 0) {
				$total_pups = null;
			}

			$alive_pups = intval($formData['alive_pups']);
			if ($alive_pups <= 0) {
				$alive_pups = null;
			}

			$db->update('litters', array(
					'weaned_on' => null,
					'total_pups' => $total_pups,
					'alive_pups' => $alive_pups,
					'weaned_female_count' => null,
					'weaned_male_count' => null,
					'holding_female_count' => null,
					'holding_male_count' => null,
					'sacrificed_female_count' => null,
					'sacrificed_male_count' => null,
					'sacrificed_nosex_count' => null,
					'not_viable' => false,
					'user_id' => $this->_user_id,
			), 'id = '.$db->quote($id));
			mdb_Messages::add ( 'saved born & alive count' );

		} elseif ($formData['wean_submit'] == 'Wean') {

			$total_female_count = intval($formData['total_female_count']);

			$total_pups = intval($formData['total_pups']);
			$alive_pups = intval($formData['alive_pups']);

			$total_female_count = intval($formData['total_female_count']);
			$total_male_count = intval($formData['total_male_count']);

			$sacrificed_female_count = intval($formData['sacrificed_female_count']);
			$sacrificed_male_count = intval($formData['sacrificed_male_count']);
			$sacrificed_nosex_count = intval($formData['sacrificed_nosex_count']);

			$weaned_female_count = intval($formData['weaned_female_count']);
			$weaned_male_count = intval($formData['weaned_male_count']);

			$holding_female_count = intval($formData['holding_female_count']);
			$holding_male_count = intval($formData['holding_male_count']);

			// check if data adds up
			if ($total_pups == 0) {
				mdb_Messages::add ( 'there must be at least 1 pup born in this litter', 'error' );
				$this->_redirect ( '/litter/view/id/' . $id );
				return;
			}

			if ( $alive_pups > $total_pups ) {
				mdb_Messages::add ( 'number of pups alive at weaning time cannot be greater then number of pups born', 'error' );
				$this->_redirect ( '/litter/view/id/' . $id );
				return;
			}

			if ( $alive_pups != ($total_female_count+$total_male_count+$sacrificed_nosex_count) ) {
				mdb_Messages::add ( 'it just doesn\'t add up', 'error' );
				$this->_redirect ( '/litter/view/id/' . $id );
				return;
			}

			if ( $total_female_count != ($sacrificed_female_count + $weaned_female_count + $holding_female_count) ) {
				mdb_Messages::add ( 'females don\'t add up', 'error' );
				$this->_redirect ( '/litter/view/id/' . $id );
				return;
			}

			if ( $total_male_count != ($sacrificed_male_count + $weaned_male_count + $holding_male_count) ) {
				mdb_Messages::add ( 'males don\'t add up', 'error' );
				$this->_redirect ( '/litter/view/id/' . $id );
				return;
			}

			// now we can actually wean

			// create needed weaning cages
			$cages = new Cages();
			$weaningCages = new WeaningCages( );

			$MAX_PER_CAGE = 5;
			if ($weaned_female_count > 0) {
				// how many weaning cages do we need? there is max 5 (usually) mice per cage.
				$female_wean_cages_needed = ceil($weaned_female_count / $MAX_PER_CAGE);
				for ($i = 1; $i <= $female_wean_cages_needed; $i++) {
					// create master cage
					$cageRow = $cages->createRow();
					$cageRow->assigned_id = $assigned_id.':F:'.$i.'/'.$female_wean_cages_needed;
//					$cageRow->set_up_on = date('Y-m-d');
					$cageRow->protocol_id = $protocol_id;
					$cageRow->cagetype = Cages::WEANING;
					$cageRow->user_id = $this->_user_id;
					$cageRow->save();

					// create weaning cage
					$weaningCageRow = $weaningCages->createRow ();
					$weaningCageRow->id = $cageRow->id;
					$weaningCageRow->litter_id = $id;
					$weaningCageRow->sex = 'F';
					$weaningCageRow->save ();

					// create mice that belong to this cage
					// we are working on ($i-1)*$MAX_PER_CAGE + 1 to $i*$MAX_PER_CAGE mouse.
					for ($j = ($i-1) * $MAX_PER_CAGE + 1; $j <= min($i*$MAX_PER_CAGE, $weaned_female_count); $j++) {
						$db->insert('mice', array(
							'assigned_id' => $assigned_id.'-'.$j,
							'sex' => 'F',
							'is_alive' => true,
							'cage_id' => $cageRow->id,
							'user_id' => $this->_user_id,
							'litter_id' => $id,
							'protocol_id' => $protocol_id,
							'strain_id' => $strain_id,
							'born_on' => $born_on,
							'weaned_on' => $weaned_on,
							'generation' => $generation
							));
					}
					$db->query('insert into transfers(mouse_id, user_id, transferred_on, notes, from_cage_id, to_cage_id) select id, '.$db->quote($this->_user_id).', '.$db->quote($weaned_on).', \'to wean cage\', '.$db->quote($litterRow['breeding_cage_id']).', '.$db->quote($cageRow->id).' from mice where cage_id = '.$db->quote($cageRow->id));
				}

			}

			// create holding females
			for ($i = 1; $i <= $holding_female_count; $i++) {
				$db->insert('mice', array(
					'assigned_id' => $assigned_id.'-'.($weaned_female_count + $i),
					'sex' => 'F',
					'is_alive' => false,
					'status' => 'holding',
					'user_id' => $this->_user_id,
					'litter_id' => $id,
					'protocol_id' => $protocol_id,
					'strain_id' => $strain_id,
					'born_on' => $born_on,
					'weaned_on' => $weaned_on,
					'generation' => $generation
					));
			}

			// sacrificed females
			for ($i = 1; $i <= $sacrificed_female_count; $i++) {
				$db->insert('mice', array(
					'assigned_id' => $assigned_id.'-'.($weaned_female_count + $holding_female_count + $i),
					'sex' => 'F',
					'is_alive' => false,
					'status' => 'sacrificed at wean',
					'user_id' => $this->_user_id,
					'litter_id' => $id,
					'strain_id' => $strain_id,
					'protocol_id' => $protocol_id,
					'born_on' => $born_on,
					'terminated_on' => $weaned_on,
					'generation' => $generation
					));
			}

			if ($weaned_male_count > 0) {
				// how many weaning cages do we need? there is max $MAX_PER_CAGE mice per cage.
				$male_wean_cages_needed = ceil($weaned_male_count / $MAX_PER_CAGE);
				for ($i = 1; $i <= $male_wean_cages_needed; $i++) {
					// create master cage
					$cageRow = $cages->createRow();
					$cageRow->assigned_id = $assigned_id.':M:'.$i.'/'.$male_wean_cages_needed;
//					$cageRow->set_up_on = date('Y-m-d');
					$cageRow->protocol_id = $protocol_id;
					$cageRow->cagetype = Cages::WEANING;
					$cageRow->user_id = $this->_user_id;
					$cageRow->save();

					// create weaning cage
					$weaningCageRow = $weaningCages->createRow ();
					$weaningCageRow->id = $cageRow->id;
					$weaningCageRow->litter_id = $id;
					$weaningCageRow->sex = 'M';
					$weaningCageRow->save ();

					// create mice that belong to this cage
					// we are working on ($i-1)*$MAX_PER_CAGE + 1 to $i*$MAX_PER_CAGE mouse.
					for ($j = ($i-1) * $MAX_PER_CAGE + 1; $j <= min($i*$MAX_PER_CAGE, $weaned_male_count); $j++) {
						$db->insert('mice', array(
							'assigned_id' => $assigned_id.'-'.($total_female_count + $j),
							'sex' => 'M',
							'is_alive' => true,
							'cage_id' => $cageRow->id,
							'user_id' => $this->_user_id,
							'litter_id' => $id,
							'protocol_id' => $protocol_id,
							'strain_id' => $strain_id,
							'born_on' => $born_on,
							'weaned_on' => $weaned_on,
							'generation' => $generation
							));
					}
					$db->query('insert into transfers(mouse_id, user_id, transferred_on, notes, from_cage_id, to_cage_id) select id, '.$db->quote($this->_user_id).', '.$db->quote($weaned_on).', \'to wean cage\', '.$db->quote($litterRow['breeding_cage_id']).', '.$db->quote($cageRow->id).' from mice where cage_id = '.$db->quote($cageRow->id));
				}
			}

			// holding males
			for ($i = 1; $i <= $holding_male_count; $i++) {
				$db->insert('mice', array(
					'assigned_id' => $assigned_id.'-'.($total_female_count + $weaned_male_count + $i),
					'sex' => 'M',
					'is_alive' => false,
					'status' => 'holding',
					'user_id' => $this->_user_id,
					'litter_id' => $id,
					'protocol_id' => $protocol_id,
					'strain_id' => $strain_id,
					'born_on' => $born_on,
					'weaned_on' => $weaned_on,
					'generation' => $generation
					));
			}

			// sacrificed males
			for ($i = 1; $i <= $sacrificed_male_count; $i++) {
				$db->insert('mice', array(
					'assigned_id' => $assigned_id.'-'.($total_female_count + $weaned_male_count + $holding_male_count + $i),
					'sex' => 'M',
					'is_alive' => false,
					'status' => 'sacrificed at wean',
					'user_id' => $this->_user_id,
					'litter_id' => $id,
					'strain_id' => $strain_id,
					'protocol_id' => $protocol_id,
					'born_on' => $born_on,
					'terminated_on' => $weaned_on,
					'generation' => $generation
					));
			}

			// update weaned on date in litter
			$db->update('litters', array(
					'weaned_on' => $weaned_on,
					'total_pups' => $total_pups,
					'alive_pups' => $alive_pups,
					'weaned_female_count' => $weaned_female_count,
					'weaned_male_count' => $weaned_male_count,
					'holding_female_count' => $holding_female_count,
					'holding_male_count' => $holding_male_count,
					'sacrificed_female_count' => $sacrificed_female_count,
					'sacrificed_male_count' => $sacrificed_male_count,
					'sacrificed_nosex_count' => $sacrificed_nosex_count,
					'not_viable' => $not_viable,
					'user_id' => $this->_user_id,
			), 'id = '.$id);

			mdb_Messages::add ( 'litter weaned' );
		} else {
			throw new Exception('/litter/wean called with unrecognised opption '.$formData['wean_submit']);
		}
		$this->_redirect ( '/litter/view/id/' . $id );

	}

	public function unweanAction() {

		$litters = new Litters();

		$id = ( int ) $this->_request->getParam ( 'id', 0 );
		$assigned_id = $this->_request->getParam ( 'litter', '' );
		if ($id > 0 || $assigned_id != '') {
			if ($id) {
				$where = 'id ='.$id;
			} else {
				$where = 'assigned_id = \'' . $assigned_id . '\'';
			}
			$row = $litters->fetchRow($where);
			// does this litter exist?
			if ($row == null) {
				mdb_Messages::add ( 'there is no such litter', 'error' );
				$this->_redirect ( '/litter' );
				return;
			}
		} else {
			// no litter id specified in URL
			$this->_redirect ( '/litter' );
			return;
		}

		try {
			// delete all mice
			$mice = new Mice();
			$mice->delete('litter_id = '.$id, true);
		} catch (Zend_Db_Table_Exception $e) {
			mdb_Messages::add ( $e->getMessage(), 'error' );
			mdb_Log::Write('unable to delete: '.$e->__toString());
			$this->_redirect ( '/litter/view/id/' . $id );
			return;
		}
		// delete weaning cages
		$weaningCages = new WeaningCages();
		$weaningCages->delete('litter_id = '.$id);

		// update litter record
		$litters->update(array(
				'weaned_on' => null,
				'weaned_female_count' => null,
				'weaned_male_count' => null,
				'holding_female_count' => null,
				'holding_male_count' => null,
				'sacrificed_female_count' => null,
				'sacrificed_male_count' => null,
				'sacrificed_nosex_count' => null,
				'not_viable' => null),
			'id = '.$id);
		$this->_redirect ( '/litter/view/id/' . $id );
	}

	public function weanlistAction() {

		$this->view->title = "Wean List";

		$fromDays = (int) $this->_request->getParam ( 'from', 18 );
		$toDays = (int) $this->_request->getParam ( 'to', 21 );
		$weaned = $this->_request->getParam ( 'weaned' ) == '1';
		$search = $this->_request->getParam ( 'search' );

		$this->view->fromDays = $fromDays;
		$this->view->toDays = $toDays;
		$this->view->weaned = $weaned;
		$this->view->search = $search;

		$db = Zend_Db_Table::getDefaultAdapter();

		// get search results
		$where = 'datediff(curdate(), born_on) between '.min($fromDays, $toDays).' and '.max($fromDays, $toDays);
		if (! $weaned) {
			$where .= ' and weaned_on is null';
		}
		if ($search) {
			$where .= ' and (MATCH (assigned_id) AGAINST ('.$db->quote($search).' IN BOOLEAN MODE) or MATCH (strain_name) AGAINST ('.$db->quote($search).' IN BOOLEAN MODE) or MATCH (mating_type) AGAINST ('.$db->quote($search).' IN BOOLEAN MODE))';
		}
		$select = $db->select()
			->from('litters',
				array('id', 'assigned_id', 'born_on', 'weaned_on', 'alive_pups', 'lastmodified', 'strain_id', 'breeding_cage_id', 'generation' ))
			->joinLeft('strains',
				'litters.strain_id = strains.id',
				array('strain_name'))
			->joinLeft('users',
				'strains.assigned_user_id = users.id',
				array('username as assigned_user'))
			->joinLeft('breeding_cages',
				'litters.breeding_cage_id = breeding_cages.id',
				array('mating_type'))
				->where($where)
			->order(array('born_on', 'litters.assigned_id'));

			$this->view->weanlist = $select->query()->fetchAll();
	}

	public function printweancardsAction() {

		$params = $this->_request->getParams();
		$prints = array();

		foreach ($params as $key => $value) {
			if ( substr($key, 0, 6) == 'print-' and $value > 0) {
				$prints[substr($key,6)] = $value;
			}
		}

		if (count($prints) == 0) {
			// nothing selected to print
			mdb_Messages::add('nothing selected to print', 'error');
			$this->_redirect('/litter/weanlist');
			return;
		}

		$username = Zend_Db_table::getDefaultAdapter()->fetchOne ( "SELECT username from users where id = " . $this->_user_id );
		$pdf = new Zend_Pdf();
		$pdf->properties['Title'] = 'Wean Cards';
		$pdf->properties['Author'] = $username;
		$pdf->properties['Creator'] = Zend_Registry::get('system.title');

		$template = $this->WeanCardTemplate();

		foreach ($prints as $cage_id => $qty) {
			for ($i = 1; $i <= $qty; $i++) {
				try {
					$pdf->pages[] = $this->WeanCageCard($template, $cage_id, $username);
				} catch (Zend_Exception $e) {
					mdb_Messages::add ( $e->getMessage() );
					mdb_Log::Write('unable to print wean cards: '.$e->__toString());
					$this->_redirect ( '/litter/weanlist' );
					return;
				}
			}
		}

		if (! count($pdf->pages)) {
			// nothing to print
			mdb_Messages::add ( $e->getMessage() );
			$this->_redirect ( '/litter/weanlist' );
			return;
		}

		// ready to display PDF file

		$this->_helper->layout->disableLayout();

		$this->view->pdf = $pdf;
	}

	private function WeanCardTemplate() {

		$hairline_width = 0.25;
		$boldline_width = 0.5;
		$topgrid_top = 310;
		$topgrid_lines = 6;
		$topgrid_line_height = 14;
		$topgrid_text_left = 15;
		// $bottomgrid_top = 230;
		$bottomgrid_top = $topgrid_top - $topgrid_lines * $topgrid_line_height;
		$bottomgrid_lines = 11;
		$bottomgrid_line_height = 17;
		$bottomgrid_header_height = 10;
		$far_left = 0;
		$far_right = 216;
		$far_bottom = 0;
		$far_top = 360;

		$card = new Zend_Pdf_Page((3*72).':'.(5*72).':');

		// WEAN title
		$card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 13);
		$card->drawText('WEAN', 88, $far_top - 25);

		$card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 5);
		$card->drawText('Printed on:', $topgrid_text_left, $far_top - 18);
		$card->drawText('Printed by:', $topgrid_text_left, $far_top - 25);

		// top grid

		// bold fist line
		$card->setLineWidth($boldline_width);
		$card->drawLine($far_left, $topgrid_top, $far_right, $topgrid_top);

		// top grid gridlines
		$card->setLineWidth($hairline_width);
		for ($i = 1; $i < $topgrid_lines; $i++) {
			$card->drawLine($far_left, $topgrid_top - $i*$topgrid_line_height, $far_right, $topgrid_top - $i*$topgrid_line_height);
		}

		// top grid text
		$card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 9);

		$topgrid_titles = array('Cage:', 'Strain', 'Mother(s)', 'Father', 'DOB', 'Gen.', 'Protocol');

		for ($i = 0; $i < count($topgrid_titles); $i++) {
			$card->drawText($topgrid_titles[$i], $topgrid_text_left, $topgrid_top + 3 - $i * $topgrid_line_height);
		}
		$card->drawText('Sex:', $far_right - 60, $topgrid_top + 3);

		// bottom grid

		// bold fist line
		$card->setLineWidth($boldline_width);
		$card->drawLine($far_left, $bottomgrid_top, $far_right, $bottomgrid_top);

		// bottom grid gridlines
		$card->setLineWidth($hairline_width);
		for ($i = 0; $i <= $bottomgrid_lines; $i++) {
			$card->drawLine(
				$far_left,
				$bottomgrid_top - $bottomgrid_header_height - $i*$bottomgrid_line_height,
				$far_right,
				$bottomgrid_top - $bottomgrid_header_height - $i*$bottomgrid_line_height);
		}

		$card->setLineWidth($boldline_width);
		$card->drawLine(
			$far_left,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height,
			$far_right,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height);

		// add vertical gridlines
		$card->setLineWidth($hairline_width);
		$card->drawLine(
			$topgrid_text_left + 20,
			$bottomgrid_top,
			$topgrid_text_left + 20,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height);
		$card->drawLine(
			$topgrid_text_left + 50,
			$bottomgrid_top,
			$topgrid_text_left + 50,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height);
		$card->drawLine(
			$topgrid_text_left + 105,
			$bottomgrid_top,
			$topgrid_text_left + 105,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height);

		$card->drawLine(
			108,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height,
			108,
			$far_bottom);

		// bottom grid header text
		$card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
		$card->drawText('Indiv.', $topgrid_text_left, $bottomgrid_top - 8);
		$card->drawText('EM', $topgrid_text_left + 30, $bottomgrid_top - 8);
		$card->drawText('Com.', $topgrid_text_left + 140, $bottomgrid_top - 8);

		// footer text
		$card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
		$card->drawText('DOW', $topgrid_text_left,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height - 8);
		$card->drawText('Wean is 6 weeks', 112,
			$bottomgrid_top - $bottomgrid_header_height - $bottomgrid_lines*$bottomgrid_line_height - 8);

		$card->flush();

		return $card;
	}

	private function WeanCageCard(Zend_Pdf_Page $template, $id, $username) {

		// does this cage exist?
		$select = Zend_Db_Table::getDefaultAdapter ()->select ()
		->from ( 'litters',
			array('assigned_id as litter', 'born_on', 'weaned_on', 'alive_pups', 'generation', 'date_add(litters.born_on, interval 6 week) as six_weeks'))
		->joinLeft('cages',
			'litters.breeding_cage_id = cages.id',
			array( 'cages.assigned_id as breeding_cage'))
		->joinLeft('strains',
			'litters.strain_id = strains.id',
			array( 'strain_name'))
		->joinLeft('protocols',
			'litters.protocol_id = protocols.id',
			array( 'protocol_name'))
		->joinLeft('mice as m_father',
			'litters.father_id = m_father.id',
			array( 'm_father.assigned_id as father'))
		->joinLeft('mice as m_mother',
			'litters.mother_id = m_mother.id',
			array( 'm_mother.assigned_id as mother'))
		->joinLeft('mice as m_mother2',
			'litters.mother2_id = m_mother2.id',
			array( 'm_mother2.assigned_id as mother2'))
		->joinLeft('mice as m_mother3',
			'litters.mother3_id = m_mother3.id',
			array( 'm_mother3.assigned_id as mother3'))
		->where ( 'litters.id = '.$id );

		$wean_query = $select->query()->fetchAll();

		if ( ! count($wean_query) ) {
			throw new Zend_Exception('Cannon retreive information for litter id '.$id);
		}

		$wean_info = $wean_query[0];

		$wean_card = new Zend_Pdf_Page($template);

		$text_left = 15;

		$wean_card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 5);
		$wean_card->drawText(date('ymd h:i a'), $text_left + 25, 342);
		$wean_card->drawText($username, $text_left + 25, 335);

		$wean_card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD), 9);

		$topgrid_values = array(
			$wean_info['litter'],
			$wean_info['strain_name'],
			implode(' ', array($wean_info['mother'], $wean_info['mother2'], $wean_info['mother3'])),
			$wean_info['father'],
			mdb_Globals::formatDateTime($wean_info['born_on']),
			$wean_info['generation'],
			$wean_info['protocol_name']);

		for ($i = 0; $i < count($topgrid_values); $i++) {
		 	$wean_card->drawText($topgrid_values[$i], $text_left + 45, 313 - $i * 14);
		}

		// footer text
		$wean_card->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 7);
		if ($wean_info['weaned_on']) {
			$dow = mdb_Globals::formatDateTime($wean_info['weaned_on']);
		} else {
			$dow = date('ymd');
		}
		$wean_card->drawText($dow, $text_left, 12);
		$wean_card->drawText(mdb_Globals::formatDateTime($wean_info['six_weeks']), 112, 12);

		$wean_card->flush();

		return $wean_card;

	}

	public function searchAction() {

		$this->view->title = "Search Litters";

		$query = mdb_Globals::stripslashes ( $this->_request->getParam ( 'q' ) );

		// if there is a litter with this assigned_id, just to straight there
		if ($query != '') {
			$db = Zend_Db_Table::getDefaultAdapter();
			$single_result = $db->fetchOne('select id from litters where assigned_id = '.$db->quote($query));
			if ($single_result) {
				$this->_redirect ( '/litter/view/id/' . $single_result );
			}

			$pageNumber = $this->_request->getParam ( 'page', 1 );

			$this->view->query = $query;

			if ( substr($query, 0, 1) == '!' ) {
				$query = substr($query, 1);
				$select = $db->select ()
				->from ( 'litters',
					array('id', 'assigned_id', 'born_on', 'weaned_on', 'alive_pups', 'lastmodified'))
				->joinLeft('strains',
					'litters.strain_id = strains.id',
					array( 'strain_name'))
				->where ( $query )
				->order ( 'assigned_id' );
			} else {
				$select = $db->select ()
				->from ( 'litters',
					array('id', 'assigned_id', 'born_on', 'weaned_on', 'alive_pups', 'lastmodified'))
				->joinLeft('strains',
					'litters.strain_id = strains.id',
					array( 'strain_name'))
				->where ( 'MATCH (litters.assigned_id) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) or MATCH (strain_name) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) or exists (select * from comments where ref_table = '.$db->quote(Comments::LITTER).' and ref_item_id = litters.id and MATCH (comment) AGAINST ('.$db->quote($query).' IN BOOLEAN MODE) )' )
				->order ( 'litters.assigned_id' );
			}

			try {
				$paginator = new Zend_Paginator ( new Zend_Paginator_Adapter_DbSelect ( $select ) );
				$paginator->setItemCountPerPage ( 1000 );
				$paginator->setCurrentPageNumber ( $pageNumber );
				$this->view->paginator = $paginator;
			} catch (Zend_Db_Statement_Exception $e) {
				mdb_Log::Write('unable to search: '.$e->__toString());
				$this->view->search_error = $e->getMessage();
			}
		}
	}

}
