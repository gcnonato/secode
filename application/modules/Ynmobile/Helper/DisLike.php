<?php

class Ynmobile_Helper_Dislike extends Ynmobile_Helper_Base{
    
    function field_id(){
        $this->data['iDislikeId']  = $this->entry->getIdentity();
    }
    
    function field_listing(){
        $this->field_id();
        $this->field_type();
        $this->field_user();
    }
    
    function field_detail(){
        $this->field_listing();
    }
}
