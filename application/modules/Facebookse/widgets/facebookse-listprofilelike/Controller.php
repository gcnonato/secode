<?php
 /**
 * SocialEngine
 *
 * @category   Application_Extensions
 * @package    Facebookse
 * @copyright  Copyright 2010-2011 BigStep Technologies Pvt. Ltd.
 * @license    http://www.socialengineaddons.com/license/
 * @version    $Id: Controller.php 6590 2011-01-06 9:40:21Z SocialEngineAddOns $
 * @author     SocialEngineAddOns
 */

class Facebookse_Widget_FacebookseListprofilelikeController extends Engine_Content_Widget_Abstract
{
  public function indexAction()
  {
    //CHECK IF Facebookse MODULE IS ENABLE OR NOT.WE ARE DOING THIS FOR INTEGRATING FACEBOOK LIKE TO SITE LIKE.
		$enable_facebooksemodule = Engine_Api::_()->getDbtable('modules', 'core')->isModuleEnabled('facebookse');

    $enable_fboldversion = Engine_Api::_()->getDbtable('modules', 'core')->isModuleEnabled('socialdna');
		if (!empty($enable_fboldversion)) {
			$socialdnamodule = Engine_Api::_()->getDbtable('modules', 'core')->getModule('socialdna');
			$socialdnaversion = $socialdnamodule->version;
			if ($socialdnaversion >= '4.1.1') {
				$enable_fboldversion = 0;
			}
		}


    if (empty($enable_facebooksemodule)) {
			return $this->setNoRender();
    }
    $front = Zend_Controller_Front::getInstance();
		$group_like = Zend_Registry::get('facebookse_grouplike');
    $module = $front->getRequest()->getModuleName();
		$action = $front->getRequest()->getActionName();
    if (!Engine_Api::_()->getDbtable('mixsettings', 'facebookse')->checkLikeButton($module, ''))
         return $this->setNoRender();
		$curr_url = (_ENGINE_SSL ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $front->getRequest()->getRequestUri();
		$fblike_moduletype_id = $front->getRequest()->getparam('listing_id');
    $this->view->fblike_moduletype_id = $fblike_moduletype_id;
		$this->view->group_type = Zend_Registry::get('facebookse_grouptype');
    if (($module == 'list' && $action == 'view')) {
			$this->view->LikeSettings = $LikeSetting = Engine_Api::_()->facebookse()->getLikeSetting($module, '' , $curr_url);
      $button = Engine_Api::_()->facebookse()->getFBLikeCode();
      $this->view->like_button  = $button;
		}
  }
}
?>
