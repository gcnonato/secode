<?php

/**
 * SocialEngine
 *
 * @category   Application_Extensions
 * @package    Siteevent
 * @copyright  Copyright 2013-2014 BigStep Technologies Pvt. Ltd.
 * @license    http://www.socialengineaddons.com/license/
 * @version    $Id: Remove.php 6590 2014-01-02 00:00:00Z SocialEngineAddOns $
 * @author     SocialEngineAddOns
 */
class Siteevent_Form_Member_Remove extends Engine_Form {

    public function init() {
        $this
                ->setTitle('Remove Guest')
                ->setDescription('Are you sure you want to remove this guest from the event?')
                ->setAction($_SERVER['REQUEST_URI'])
        ;

        $this->addElement('Button', 'submit', array(
            'type' => 'submit',
            'ignore' => true,
            'decorators' => array('ViewHelper'),
            'label' => 'Remove Guest',
        ));

        $this->addElement('Cancel', 'cancel', array(
            'prependText' => ' or ',
            'label' => 'cancel',
            'link' => true,
            'href' => '',
            'onclick' => 'parent.Smoothbox.close();',
            'decorators' => array(
                'ViewHelper'
            ),
        ));

        $this->addDisplayGroup(array(
            'submit',
            'cancel'
                ), 'buttons');
    }

}