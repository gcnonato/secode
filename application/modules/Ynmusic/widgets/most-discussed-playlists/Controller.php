<?php
class Ynmusic_Widget_MostDiscussedPlaylistsController extends Engine_Content_Widget_Abstract {
	public function indexAction() {
        $params['browse_by'] = "most_discussed";
		// Get paginator
		$this -> view -> paginator = $paginator = Engine_Api::_() -> getItemTable('ynmusic_playlist') -> getPaginator($params);
		$this->getElement()->removeDecorator('Title');
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$page = $request -> getParam('page', 1);
		//$this -> _getParam('itemCountPerPage', 8)
		// Set item count per page and current page number
		$paginator -> setItemCountPerPage($this -> _getParam('itemCountPerPage', 8));
		$paginator -> setCurrentPageNumber($page);
        
	}
}
?>
