<?php

class Socialstore_AdminCategoryController extends Core_Controller_Action_Admin {

	protected $_paginate_params = array();

	public function init() {
		parent::init();
		Zend_Registry::set('admin_active_menu', 'socialstore_admin_main_categories');		

	}

	/**
	 *
	 *
	 * @return Socialstore_Model_DbTable_Categories
	 */
	public function getDbTable() {
		return Engine_Api::_() -> getDbTable('categories', 'Socialstore');
	}

	public function indexAction() {
		$table = $this -> getDbTable();

		$pid = $this -> _getParam('pid', 0);
		$this->view->pid  = $pid = (int)$pid;

		$select = $table -> select() -> where('pid=?', $pid);

		$node = $table -> find($pid) -> current();
		$request = Zend_Controller_Front::getInstance() -> getRequest();
		$page = $request -> getParam('page', 1);
		$this -> view -> categories = $paginator = Zend_Paginator::factory($select);
		$paginator->setItemCountPerPage(10);
		$paginator->setCurrentPageNumber($page);
		$this -> view -> category = $node;
	}

	public function createAction() {
		// In smoothbox
		$this -> _helper -> layout -> setLayout('admin-simple');

		// Generate and assign form
		$form = $this -> view -> form = new Socialstore_Form_Admin_Category_Create();
		$pid= $this -> _getParam('pid', 0);
		

		// Check post
		$req = $this -> getRequest();
		
		
		if($req-> isPost() && $form -> isValid($req-> getPost())) {
			// we will add the category
			$data = $form -> getValues();
			$table = $this -> getDbTable();			
			$node = $table -> addNode($data, $pid);
			
			if(is_object($node)){
				$this -> _forward('success', 'utility', 'core', array('smoothboxClose' => 10, 'parentRefresh' => 10, 'messages' => array('')));
			}else{
				$form->addError('An error occurs');
			}
				
		}
		// Output
		$this -> renderScript('admin-category/form.tpl');
	}
	
	public function editAction() {
		// In smoothbox
		$this -> _helper -> layout -> setLayout('admin-simple');
		$form = $this -> view -> form = new Socialstore_Form_Admin_Category_Edit();

		$req =  $this->getRequest();
		
		$id =  $this->_getParam('id',0);
		
		$table = $this->getDbTable();
		
		$item = $table->find($id)->current();
		
		if(!is_object($item)){
			$form->setError("Category not found.");
			return ;
		}
		
		if($req->isGet()){
			$form->populate($item->toArray());
		}				
		
		
		// Check post
		if($req-> isPost() && $form -> isValid($req-> getPost())) {
			// Ok, we're good to add field
			$item->name =  $form->getValue('name');
			$item->save();
			$this -> _forward('success', 'utility', 'core', array('smoothboxClose' => 10, 'parentRefresh' => 10, 'messages' => array('')));
		}

		// Output
		$this -> renderScript('admin-category/form.tpl');
	}
	public function deleteAction() {

		// In smoothbox
		$this -> _helper -> layout -> setLayout('admin-simple');
		$id = $this -> _getParam('id');
		$this -> view -> category_id = $id;
		$table = $this -> getDbTable();
		$node = $table -> find($id) -> current();
		
		if(!is_object($node)){
			return ;
		}
		$row = $node->getUsedCount();
		$this->view->usedCount = $total = count($row);
		if ($total > 0) {
			$this->view->form = $form = new Socialstore_Form_Admin_Category_Change();
		}
		$req  = $this->getRequest();
		
		
		$post = $this -> getRequest() -> getPost();

		
		// Check post
		if($req-> isPost()) {
			if ($total > 0) {
				if(!$form -> isValid($post)) {
					return;
				}
				$new_category = $form->getValue('category_id');
				$ids = $node->getDescendantIds();
				if (in_array($new_category, $ids)) {
					return $form->addError('You cannot select deleted category or sub-categories of deleted Category!');
				}
				if (!empty($row)) {
					foreach ($row as $product) {
						$product->category_id = $new_category;
						$product->save();
					}
				}
			}
			$table -> deleteNode($node, 0);
			$this -> _forward('success', 'utility', 'core', array('smoothboxClose' => 10, 'parentRefresh' => 10, 'messages' => array('')));
		}

		// Output
		$this -> renderScript('admin-category/delete.tpl');
	}

	

}
