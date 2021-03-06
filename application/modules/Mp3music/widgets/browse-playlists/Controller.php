<?php
class Mp3music_Widget_BrowsePlaylistsController extends Engine_Content_Widget_Abstract
{
  public function indexAction()
  {   
      if( !Zend_Controller_Action_HelperBroker::getStaticHelper('requireAuth')->setAuthParams('mp3music_playlist', null, 'view')->isValid())
      return;
      $request = Zend_Controller_Front::getInstance()->getRequest();
	  $params = $request->getParams (); 
      $obj = new Mp3music_Api_Core(array());
	  $limit = Engine_Api::_()->getApi('settings', 'core')->getSetting('mp3music.songsPerPage', 10);
	  $params['limit'] = $limit;
      $this->view->playlistPaginator = $obj->getTopPlaylistPaginator($params);
      $this->view->search = $params['search'];  
      $this->view->title = $params['title'];
      $this->view->params    = $params;
  }
}