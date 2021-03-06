<?php
/**
 * YouNet Company
 *
 * @category   	Application_Extensions
 * @package    	Adv.Event
 * @company     YouNet Company
 * @author		LuanND
 */

class Ynevent_Widget_EventsTagsController extends Engine_Content_Widget_Abstract
{
  public function indexAction()
  {
    $tag_table = Engine_Api::_()->getDbtable('tags', 'core');
    $tag_map_table = Engine_Api::_()->getDbtable('tagMaps', 'core');
    $event_table = Engine_Api::_()->getItemTable('event');
    $tag_name = $tag_table->info('name');
    $tag_map_name = $tag_map_table->info('name');
    $event_name = $event_table->info('name');
    
    $filter_select = $tag_map_table->select()->distinct()->from($tag_map_name, array("$event_name.repeat_group", "$tag_map_name.tag_id", "$tag_map_name.resource_type"))
                     ->setIntegrityCheck(false)
                     ->joinLeft($event_name,"$event_name.event_id = $tag_map_name.resource_id",'')
                     ->where("$event_name.search = ?","1");

    $select = $tag_table->select()->from($tag_name,array("$tag_name.*","Count($tag_name.tag_id) as count"));
    $select  ->joinLeft($filter_select, "t.tag_id = $tag_name.tag_id",'');
    $select  ->order("count DESC");
    $select  ->group("$tag_name.text");
    $select  ->where("t.resource_type = ?","event");

    if(Engine_Api::_()->core()->hasSubject('user')){
      $user = Engine_Api::_()->core()->getSubject('user');
      $select -> where("t.tagger_id = ?", $user->getIdentity());
    }
    else if( Engine_Api::_()->core()->hasSubject('event') ) {
      $event = Engine_Api::_()->core()->getSubject('event');
      $user = $event->getOwner();
      $select -> where("t.tagger_id = ?", $user->getIdentity());
    }
	
    $this->view->tags = $tag_table->fetchAll($select);
  }
}
?>
