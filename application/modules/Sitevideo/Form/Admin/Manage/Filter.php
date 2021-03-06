<?php

/**
 * SocialEngine
 *
 * @category   Application_Extensions
 * @package    Sitevideo
 * @copyright  Copyright 2015-2016 BigStep Technologies Pvt. Ltd.
 * @license    http://www.socialengineaddons.com/license/
 * @version    $Id: Filter.php 6590 2016-3-3 00:00:00Z SocialEngineAddOns $
 * @author     SocialEngineAddOns
 */
class Sitevideo_Form_Admin_Manage_Filter extends Engine_Form {

    public function init() {

        $this
                ->setAttribs(array(
                    'id' => 'filter_form',
                    'class' => 'global_form_box',
        ));

        $this->addElement('Hidden', 'order', array(
            'order' => 10001,
        ));

        $this->addElement('Hidden', 'order_direction', array(
            'order' => 10002,
        ));

        $this->setAction(Zend_Controller_Front::getInstance()->getRouter()->assemble(array()));
    }

}
