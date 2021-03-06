<?php
class Yncontest_Widget_MyFollowContestsController extends Engine_Content_Widget_Abstract {
	
	public function indexAction() {
		$viewer = Engine_Api::_() -> user() -> getViewer();		
		if($viewer->getIdentity() == 0)
		{
			$this->setNoRender(true);
			return;
		}
  		
		$request = Zend_Controller_Front::getInstance() -> getRequest();
		
		$items_per_page = (int)$this->_getParam('number',16);
		$this->view->height = (int)$this -> _getParam('height',200);
		$this->view->width = (int)$this -> _getParam('width',200);
		
		$this -> view -> items_per_page = $items_per_page;
		$params['page'] = $request -> getParam('page');
		$this -> view -> user_id = $user_id = $viewer -> getIdentity();
		$params['user_id'] = $user_id;
		
		
		$this -> view -> paginator = $paginator = Yncontest_Api_FollowContests::getInstance() -> getFollowContestsPaginators($params);
		
		if(!$paginator->count()) {			
			$this->view->flag = true;
		}
		$paginator -> setItemCountPerPage($items_per_page);
		$this->view->className = "layout_contest_my_follow_contests";
	}

}