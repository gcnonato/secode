<?php

/**
 * SocialEngine
 *
 * @category   Application_Extensions
 * @package    Sitemobile
 * @copyright  Copyright 2012-2013 BigStep Technologies Pvt. Ltd.
 * @license    http://www.socialengineaddons.com/license/
 * @version    $Id: notfound.tpl 6590 2013-06-03 00:00:00Z SocialEngineAddOns $
 * @author     SocialEngineAddOns
 */
?>
<div  class="ui-collapsible ui-collapsible-inset ui-corner-all ui-collapsible-themed-content">
  <h3 class="ui-collapsible-heading">
    <a href="#" class="ui-collapsible-heading-toggle ui-btn ui-fullsize ui-btn-icon-left ui-btn-up-e">
      <span class="ui-btn-inner">
        <span class="ui-btn-text">
          <?php echo $this->translate('Page Not Found') ?>
        </span>
        <span class="ui-icon ui-icon-shadow ui-icon-alert">&nbsp;</span>
      </span></a></h3>
  <div class="ui-collapsible-content ui-body-e" aria-hidden="false">
    <p>
      <?php echo $this->translate('The page you have attempted to access could not be found.') ?>
    </p>
    <a  <?php echo $this->dataHtmlAttribs("go_back_button", array('data-role' => "button", 'data-rel' => "back", 'data-corners' => "true", 'data-shadow' => "true", 'data-iconshadow' => "true",'data-theme'=>"b","data-icon"=>"chevron-left")); ?>  > <?php echo $this->translate('Go Back') ?></a>
  </div>
</div>