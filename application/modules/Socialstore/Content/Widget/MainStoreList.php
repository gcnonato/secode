<?php
class Socialstore_Content_Widget_MainStoreList extends Engine_Content_Widget_Abstract {

	/**
	 * @var int [0,20]
	 */
	protected $_limit = 5;

	public function init() {
		
		// set script path for all item
		$this -> setScriptPath('application/modules/Socialstore/views/scripts/widgets/main-store-list');

		$limit = (int)$this -> _getParam('max');
		$limit = $limit < 1 ? 5 : $limit;
		$limit = Engine_Api::_()->getApi('settings', 'core')->getSetting('store.store.page', 10);
		// check some thing else
		$this -> _limit = $limit > 10 ? 5 : $limit;
		$this->view->show_creation =  $this->_getParam('show_creation',1);
	
	
		$this->view->show_options = array(
			'creation'=>$this->_getParam('show_creation',0),
			'author'=>$this->_getParam('show_author',1),
			'indexing'=>'creation',
		);

	}

}
