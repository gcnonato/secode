<?php
class Mp3music_Widget_ProfileMusicController extends Engine_Content_Widget_Abstract
{
  protected $_childCount;
  
  public function indexAction()
  {
    // Don't render this if not authorized
    $viewer = Engine_Api::_()->user()->getViewer();
    if( !Engine_Api::_()->core()->hasSubject() ) {
      return $this->setNoRender();
    }

    // Get subject and check auth
    $subject = Engine_Api::_()->core()->getSubject();
    if( !$subject->authorization()->isAllowed($viewer, 'view') ) {
      return $this->setNoRender();
    }

    // Get paginator
    $obj = new  Mp3music_Api_Core(array());
    $this->view->paginator = $paginator = $obj->getAlbumPaginator(array(
      'user'  => Engine_Api::_()->core()->getSubject('user')->getIdentity(),
      'sort'  => 'recent',
      //'limit' => 10, // items per page
    ));
    $paginator->setCurrentPageNumber($this->_getParam('page'));
    $this->view->short_player = true;

    // Do not render if nothing to show
    if( $paginator->getTotalItemCount() <= 0 ) {
      return $this->setNoRender();
    }

    // Add count to title if configured
    if( $this->_getParam('titleCount', false) && $paginator->getTotalItemCount() > 0 ) {
      $this->_childCount = $paginator->getTotalItemCount();
    }
  }

  public function getChildCount()
  {
    return $this->_childCount;
  }
}