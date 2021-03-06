<?php

class Ynmobile_Helper_Blog extends Ynmobile_Helper_Base{


    function getYnmobileApi(){
        return Engine_Api::_()->getApi('blog','ynmobile');
    }

    function field_id(){
        $this->data['iEntryId'] =  $this->entry->getIdentity();
    }

    function field_listing(){
        $this->field_id();
        $this->field_type();
        $this->field_stats();
        $this->field_title();
        $this->field_desc();
        $this->field_user();
        $this->field_category();
        $this->field_canEdit();
        $this->field_canDelete();
        $this->field_totalView();
        $this->field_info();
        $this->field_tags();

        if(!$this->data['bIsApproved']){
            $this->data['bCanShare'] = 0;
        }
    }

    function field_info(){

        $subscriptionTable = $this->getWorkingTable('subscriptions', 'blog');
        if($this-> getWorkingModule() == 'ynblog')
        {
            $this->data['bIsApproved'] = isset($this->entry->is_approved)? ($this->entry->is_approved ? 1:0):1;
            $this->data['bIsFeatured'] = $this->entry->is_featured ? 1:0;
        }
        else
        {
            $this->data['bIsApproved'] = 1;
            $this->data['bIsFeatured'] = 0;
        }
        $this->data['bIsDraft'] = $this->entry->draft ? 1:0;
        $this->data['bIsPublished'] = $this->entry->draft ? 0:1;
        $this->data['bIsSubscribed'] = ( $subscriptionTable->checkSubscription($this->entry->getOwner(), $this->getViewer()) ) ? 1 : 0;
    }

    function field_body(){
        $this->data['sBody'] =  $this->entry->body;
    }

    function field_detail(){
        $this->field_listing();
        $this->field_likes();
        $this->field_body();
    }

    function field_canView(){
        $blog = $this->entry;
        $viewer = Engine_Api::_()->user()->getViewer();
        $canView = $blog -> authorization() -> isAllowed($this->getViewer(), 'view');

        if (Engine_Api::_() -> hasModuleBootstrap("ynblog")) {
            $ynblogCanNotView = ($blog->draft && ! $blog->isOwner ( $viewer )) || (! $blog->is_approved && ! $blog->isOwner ( $viewer ) && ! $viewer->isAdmin ());
            $this->data['bCanView'] = $canView && !$ynblogCanNotView;
        } else {
            $this->data['bCanView'] = $canView;
        }
    }
}