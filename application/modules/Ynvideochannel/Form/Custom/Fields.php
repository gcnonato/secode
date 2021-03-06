<?php

class Ynvideochannel_Form_Custom_Fields extends Fields_Form_Standard
{
    protected $_fieldType = 'ynvideochannel_video';
    public $_error = array();
    protected $_name = 'fields';
    protected $_elementsBelongTo = 'fields';

    public function init()
    {
        if (!$this->_item) {
            $video = new Ynvideochannel_Model_Video(array());
            $this->setItem($video);
        }

        parent::init();
        $this->removeElement('submit');
    }

    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }
        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this
                ->addDecorator('FormElements');
        }
    }
}