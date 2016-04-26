<?php

class Socialstore_Widget_MostCommentedStoresController extends Socialstore_Content_Widget_StoreList {
	public function indexAction() {
		
		// initialzie
		parent::init();

		$Model = new Socialstore_Model_DbTable_SocialStores;

		$select = $Model -> select();
		$select -> where('deleted=?', 0) -> where('approve_status=?', 'approved') -> where('view_status=?', 'show')-> order('comment_count desc')->limit($this->_limit);

		$this -> view -> items = $items = $Model -> fetchAll($select);
		$this -> view -> totalItems = $totalItems = count($items);

		if(!$totalItems) {
			$this -> setNoRender();
		}

		//$this -> view -> title = $this->_getParam('title','Most Commented Stores');
		$this -> view -> viewer_id = Engine_Api::_() -> user() -> getViewer() -> getIdentity();		
	}
}