<?php

class Ynmobile_Plugin_Core
{
	function onMessagesMessageCreateAfter($event)
	{
		//Do nothing
	}

    /**
     * @update 4.11
     * Allows admin configure notification type push to device " plugins > YouNet Mobile > Manage Notification"
     *
     *
     * @param $event
     *
     * @throws Zend_Exception
     */
	function onActivityNotificationCreateAfter($event)
	{
		$payload =  $event->getPayload();
		if($payload instanceof Activity_Model_Notification)
		{
			$payloadId = $payload->getIdentity();
			$userId = $payload->user_id;
			
			$content = strip_tags($payload->getContent());
			
			$notificationApi = Engine_Api::_()->getApi('notification','ynmobile');

			$notifications = $notificationApi->update(array("iUserId" => $userId));

            /**
             * skip if does not support by notification type
             */
            if(!$notificationApi->enablePushNotificationType($payload->getType())){
                return ;
            }
			
			if ($payload->type == "friend_request")
			{
				$content = ($content) ? $content : Zend_Registry::get('Zend_Translate') -> _("You have a new friend request.");
				$params = array(
						'ios' => array(
								'aps' => array(
									'alert' => $content, 
									'badge' => $notifications['iNumberOfFriendRequest']
								),
								'iId' => $payloadId,
								'sType' => 'friend_request'
						),
						'android' => array(
								'message' => $content,
								'iId' => $payloadId,
								'sType' => 'friend_request'
						)
				);
			}
			
			else if ($payload->type == "message_new") 
			{
				$content = ($content) ? $content : Zend_Registry::get('Zend_Translate') -> _("You have a new message.");
				$params = array(
						'ios' => array(
								'aps' => array(
									'alert' => $content, 
									'badge' => $notifications['iNumberOfMessage']
								),
								'iId' => $payloadId,
								'sType' => 'mail'
						),
						'android' => array(
								'message' => $content,
								'iId' => $payloadId,
								'sType' => 'mail'
						)
				);
			}
			
			else {
				$content = ($content) ? $content : Zend_Registry::get('Zend_Translate') -> _("You have a new notification.");
				$params = array(
						'ios' => array(
								'aps' => array(
									'alert' => $content, 
									'badge' => $notifications['iNumberNotification'] 
								),
								'iId' => $payloadId,
								'sType' => 'notification'
						),
						'android' => array(
								'message' => $content,
								'iId' => $payloadId,
								'sType' => 'notification'
						)
				);
				
			}

			Engine_Api::_()->getApi('PushNotification','Ynmobile')->send($params, $userId);
		}
		
	}

	function onActivityRequestCreateAfter($event)
	{
		//Do nothing
	}
}