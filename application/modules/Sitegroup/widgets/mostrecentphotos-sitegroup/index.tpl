<?php
/**
 * SocialEngine
 *
 * @category   Application_Extensions
 * @package    Sitegroup
 * @copyright  Copyright 2010-2011 BigStep Technologies Pvt. Ltd.
 * @license    http://www.socialengineaddons.com/license/
 * @version    $Id: manage.tpl 2011-05-05 9:40:21Z SocialEngineAddOns $
 * @author     SocialEngineAddOns
 */
?>
<?php 
include_once APPLICATION_PATH . '/application/modules/Sitegroup/views/scripts/common_style_css.tpl';
?>
<?php 
echo $this->partial('application/modules/Sitegroup/views/scripts/partialPhotoWidget.tpl', array('paginator' => $this->paginator, 'showLightBox' => $this->showLightBox, 'show_detail' =>  0, 'includeCss' => 1, 'displayGroupName' => $this->displayGroupName, 'displayUserName' => $this->displayUserName, 'showFullPhoto' => $this->showFullPhoto, 'type' => 'creation_date', 'count' => $this->count, 'urlaction' => 'recent'));
?>

