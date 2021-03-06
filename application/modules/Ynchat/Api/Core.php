<?php
define('ONLINE_TIMEOUT', 30);   
define('MAX_SIZE_OF_USER_IMAGE', '_50_square');
define('CHECK_ALIVE_CONNECTION_DB_INTERVAL', 300);

class Ynchat_Api_Core extends Core_Api_Abstract {
    
    private $_iTotalOnlineFriends = 0;
    private $_bIsMobile = false;
    
    public function getTotalOnlineFriends() {
        return $this->_iTotalOnlineFriends;
    }
    /**
     * We accept status in {'available','away','busy','invisible','offline'}
     * @param string $sStatus
     */
    public function updateUserStatus($iUserId, $sStatus = 'available'){
        if((int)$iUserId > 0){
            $table = Engine_Api::_()->getDbTable('status', 'ynchat');
            // remove old status
            $where = $table->getAdapter()->quoteInto('user_id = ?', $iUserId);
            $table->delete($where);
          
            // insert new status
            $status = $table->createRow();
            $status->status = $sStatus;
            $status->user_id = $iUserId;
            $status->save();
            return true;
            $logFile = APPLICATION_PATH . '/temporary/log/result.log';
            file_put_contents($logFile, $e, FILE_APPEND);
            return true;
        }

        return false;
    }
    
    public function validateUser($sUserIdHash){
        $this->reconnectDatabase();
        $iUserId = (int)$this->decryptUserId($sUserIdHash);
        if ($iUserId) {
            return $iUserId;
        }
        return false;
    }
    
    public function decryptUserId($user_id){
        $salt = $this->getSalt();
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = $salt;
        $secret_iv = $salt . '_iv';

        // hash
        $key = hash('sha256', $secret_key);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        $output = openssl_decrypt(base64_decode($user_id), $encrypt_method, $key, 0, $iv);

        return $output;
    }
    
    public function getSalt(){
        $cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
        return $cryptKey;
    }
    
    //not use now
    public function updateLastActivity($iUserId = null) {
        return true;
    }
    
    public function getUserId(){
        $viewer = Engine_Api::_()->user()->getViewer();
        return $viewer->getIdentity();    
    }
    
    public function addMessage($aVals = array()){
        // init
        // process
        $aInsert = array(
            'from' => (int) $aVals['from'],
            'to' => (int) $aVals['to'],
            'message' => $aVals['message'],
            'sent' => $this->getTimeStamp(),
            'read' => (int)$aVals['read'],
            'direction' => 0,
        );
        if(isset($aVals['message_type'])){
            $aInsert['message_type'] = $aVals['message_type'];
        } else {
            $aInsert['message_type'] = 'text';
        }
        if($aInsert['message_type'] == 'text'){
            $aInsert['message'] = $this->checkBan($aInsert['message']);
            $aInsert['message'] = htmlspecialchars($aInsert['message']);
            $aInsert['message'] = nl2br($aInsert['message']);
            $aInsert['message'] = $this->parseEmoticon($aInsert['message']);
        } else if($aInsert['message_type'] == 'sticker'){
            $sText = $this->parseSticker($aVals['sticker_id']);
            $aInsert['message'] = $sText;
        }
        if(isset($aVals['data'])){
            $aInsert['data'] = $aVals['data'];
        }
        
        $this->reconnectDatabase();
        $db = Engine_Api::_()->getDbtable('messages', 'ynchat')->getAdapter();
        $db->beginTransaction();
        try {
            $table = Engine_Api::_()->getDbtable('messages', 'ynchat');
            $message = $table->createRow();
            $message->setFromArray($aInsert);
            $message->save();
            $iMessageId = $message->getIdentity();
        }
        catch( Exception $e ) {
            $db->rollBack();
            throw $e;
        }       

        $db->commit();

        if((int)$iMessageId > 0){
            $aMessage = $this->__generateMessageData(array(
                'message_id' => $iMessageId,
                'from' => (int) $aVals['from'],
                'to' => (int) $aVals['to'],
                'message' => $aInsert['message'],
                'sent' => $this->getTimeStamp(),
                'read' => (int)$aVals['read'],
                'direction' => 0,
                'message_type' => $aInsert['message_type'],
                'data' => (isset($aVals['data'])) ? $aVals['data'] : '',
            ), 'large');
        } else {
            $aMessage = array();
        }
		
		
		if($iMessageId >0){
			if(Engine_Api::_()->hasModuleBootstrap('ynmobile')){
				Engine_Api::_()->getApi('Ynchat','Ynmobile')->pushNotification(
					$aInsert
				);
			}
		}

        // end
        return $aMessage;
    }

    public function getTimeStamp(){
        // Default time to GMT
        $now = new Zend_Date();

        return $now->getTimestamp();
    }

    public function checkBan($sTxt) {
        $table = Engine_Api::_()->getDbTable('banwords', 'ynchat');
        $aFilters = $table->fetchAll();
        foreach($aFilters as $aItem){
            $sTxt = str_ireplace($aItem->find_value, $aItem->replacement, $sTxt);
        }
        return $sTxt;
    }
    
    public function getFriendsList($userid, $time = null, $search = '') {
        if((int)$userid <= 0){
            return array();
        }

        $result = array();
        if(null == $time){
            $time = $this->getTimeStamp();
        }
        $hideOffline = 0;
        $settings = Engine_Api::_()->getApi('settings', 'core');
        $iLimit = $settings->getSetting('ynchat.num.show.friends', 1000);
        if($iLimit < 1){
            $iLimit = 1000;
        }

        // process
        // get user setting
        $aUserSetting = $this->getUserSettingsByUserId($userid);
        if(isset($aUserSetting['iUserId']) == false){
            return array();
        }

        //// show ONLY friends of user
        $sWhere = '';
        if (strlen(trim($search)) > 0) {
            $sWhere .= ' AND user.displayname LIKE "%'. $search .'%" ';
            $iLimit = 20;
        }

        $sSql = '';
        $sSql .= 'SELECT DISTINCT
                      user.user_id user_id,
                      user.displayname full_name,
                      user.photo_id user_image,
                      user.username user_name,
                      user.status message,
                      
                      yus.is_goonline,
                      yus.turnonoff,
                      IFNULL(ys.status, \'offline\')  AS `status`, 
                      IFNULL(ys.agent, \'\')  AS `agent`
            ';
        $userTbl = Engine_Api::_()->getDbTable('users', 'user');
        $userTblName = $userTbl->info('name');
        $sSql .= ' FROM ' . $userTblName . ' AS `user` ';
        
        $usersettingTbl = Engine_Api::_()->getDbTable('usersettings', 'ynchat');
        $usersettingTblName = $usersettingTbl->info('name');
        $sSql .= ' LEFT JOIN ' . ($usersettingTblName) . ' AS `yus` ';
        $sSql .= ' ON yus.user_id = user.user_id  ';
        
        $statusTbl = Engine_Api::_()->getDbTable('status', 'ynchat');
        $statusTblName = $statusTbl->info('name');
        $sSql .= ' LEFT JOIN ' . ($statusTblName) . ' AS `ys` ';
        $sSql .= ' ON user.user_id = ys.user_id  ';

        // NOT get pages
        //TODO check later
        //$sSql .= ' WHERE user.profile_page_id = 0   ' . $sWhere;

        $sSql .= ' WHERE user.user_id NOT IN '. $sWhere;
        $sSql .= ' (   ';
        $sSql .= ' SELECT ub1.blocked_user_id   ';
        $blockTbl = Engine_Api::_()->getDbTable('block', 'user');
        $blockTblName = $blockTbl->info('name');
        $sSql .= ' FROM ' . $blockTblName . ' AS `ub1`     ';
        $sSql .= ' WHERE ub1.user_id = ' . (int)$userid;
        $sSql .= ' UNION  ';
        $sSql .= ' SELECT ub2.user_id   ';
        $sSql .= ' FROM ' . $blockTblName . ' AS `ub2`     ';
        $sSql .= ' WHERE ub2.blocked_user_id = ' . (int)$userid;
        $sSql .= ' )   ';

        $sSql .= ' AND user.user_id IN   ';
        $sSql .= ' (   ';
        $friendTbl = Engine_Api::_()->getDbTable('membership', 'user');
        $friendTblName = $friendTbl->info('name');
        $sSql .= ' SELECT fr1.resource_id    ';
        $sSql .= ' FROM ' . $friendTblName . ' AS `fr1`     ';
        $sSql .= ' WHERE fr1.user_id = ' . (int)$userid;
        $sSql .= ' AND fr1.active = 1)   ';

        $sSql .= ' AND user.user_id IN    ';
        $sSql .= ' (   ';
        $sSql .= ' SELECT fr2.user_id     ';
        $sSql .= ' FROM ' . $friendTblName . ' AS `fr2`     ';
        $sSql .= ' WHERE fr2.resource_id = ' . (int)$userid;
        $sSql .= ' AND fr2.active = 1)   ';

        $sSql .= ' ORDER BY `status` ASC, user_id ASC    ';
        $table = Engine_Api::_()->getDbTable('users', 'user');
        $db = $table->getAdapter();
        $stmt = $db->query($sSql);
        $result = $stmt->fetchall();
        $blockAList = $this->getBlockListBySide($userid, 'to');
        $blockAUserId = array();
        foreach ($blockAList as $key => $item) {
            $blockAUserId[] = $item['user_id'];
        }
        $blockXList = $this->getBlockListBySide($userid, 'from');
        $blockXUserId = array();
        foreach ($blockXList as $key => $item) {
            $blockXUserId[] = $item['user_id'];
        }
        $allowAList = $this->getAllowListBySide($userid, 'to');
        $allowAUserId = array();
        foreach ($allowAList as $key => $item) {
            $allowAUserId[] = $item['user_id'];
        }
        $allowXList = $this->getAllowListBySide($userid, 'from');
        $allowXUserId = array();
        foreach ($allowXList as $key => $item) {
            $allowXUserId[] = $item['user_id'];
        }

        $buddyList = array();
        $count = 0;
        $countOnline = 0;
        foreach ($result as $key => $item) {
            if ($item['turnonoff'] == null) {
                $item['turnonoff'] = 'onall';
            }
            $bAdd = null;
            switch($aUserSetting['sTurnOnOff']){
                case 'onall':
                    $bAdd = 1;
                    break;
                case 'offall':
                    $bAdd = 0;
                    break;
                case 'onsome':
                    if (in_array($item['user_id'], $allowAUserId)) {
                        $bAdd = 1;
                    } else {
                        $bAdd = 0;
                    }
                    break;
                case 'offsome':
                    if (!in_array($item['user_id'], $blockAUserId)) {
                        $bAdd = 1;
                    } else {
                        $bAdd = 0;
                    }
                    break;
            }
            if($bAdd){
                switch($item['turnonoff']){
                    case 'offall':
                        $bAdd = -1;
                        break;
                    case 'offsome':
                        if (in_array($item['user_id'], $blockXUserId)) {
                            $bAdd = -1;
                        }
                        break;
                    case 'onsome':
                        if (!in_array($item['user_id'], $allowXUserId)) {
                            $bAdd = -1;
                        }
                        break;
                }
            }
            // -1 : can display with offline status
            // 0 : NOT display
            // 1 : can display with online status
            
            if($bAdd == -1){
                $item['status'] = 'offline';
            }
            if($bAdd == 1 || $bAdd == -1){
                if(empty($item['status'])){
                    $item['status'] = 'offline';
                }
                
                if ($item['message'] == null) {
                    $item['message'] = '';
                }

                if (!$item['is_goonline']) {
                    $item['status'] = 'offline';
                }
                
                $user_item = Engine_Api::_()->user()->getUser($item['user_id']);
                $item['link'] = $user_item->getHref();
                $item['avatar'] = $this->getAvatar($item['user_image']);
                if (empty($item['grp'])) {
                    $item['grp'] = '';
                }
                
                $item['full_name'] = trim($item['full_name']);
                $item['user_name'] = trim($item['user_name']);
                if (empty($item['full_name'])) {
                    if (empty($item['user_name'])) $item['full_name'] = 'No Name';
                    else $item['full_name'] = $item['user_name'];
                }
                
                if (($hideOffline == 0 || ($hideOffline == 1 && $item['status'] != 'offline'))) {
                    $buddyList[] = $item;
                    $count ++;
                    if($item['status'] != 'offline'){
                        $countOnline ++;
                    }
                }
            }
            
            if($count == $iLimit){
                break;  
            }
        }
        $this->_iTotalOnlineFriends = $countOnline;
        return $buddyList;
    }
    
    public function getFriendsListNotRestriction($userid, $time = null, $search = '') {
        // init
        if((int)$userid <= 0){
            return array();
        }

        $result = array();
        if(null == $time){
            $time = $this->getTimeStamp();
        }
        $settings = Engine_Api::_()->getApi('settings', 'core');
        $iLimit = $settings->getSetting('ynchat.num.search.friends', 10);
        if($iLimit < 1){
            $iLimit = 10;
        }

        // process
        $sWhere = '';
        if (strlen(trim($search)) > 0)
        {
            $sWhere .= ' WHERE user.displayname LIKE "%'. $search .'%" ';
        }

        $sSql = '';
        $sSql .= 'SELECT DISTINCT
                      user.user_id user_id,
                      user.displayname full_name,
                      user.photo_id user_image,
                      user.username user_name,
                      user.status message,
                      yus.is_goonline,
                      yus.turnonoff,
                      IFNULL(ys.status, \'offline\')  AS `status`, 
                      IFNULL(ys.agent, \'\')  AS `agent`
            ';
        $userTbl = Engine_Api::_()->getDbTable('users', 'user');
        $userTblName = $userTbl->info('name');
        $sSql .= ' FROM ' . $userTblName . ' AS `user` ';
        
        $usersettingsTbl = Engine_Api::_()->getDbTable('usersettings', 'ynchat');
        $usersettingsTblName = $usersettingsTbl->info('name');
        $sSql .= ' LEFT JOIN ' . $usersettingsTblName . ' AS `yus` ';
        $sSql .= ' ON yus.user_id = user.user_id  ';
        
        $statusTbl = Engine_Api::_()->getDbTable('status', 'ynchat');
        $statusTblName = $statusTbl->info('name');
        $sSql .= ' LEFT JOIN ' . $statusTblName . ' AS `ys` ';
        $sSql .= ' ON user.user_id = ys.user_id  ';

        $sSql .= $sWhere;

        $friendTbl = Engine_Api::_()->getDbTable('membership', 'user');
        $friendTblName = $friendTbl->info('name');
        $sSql .= ' AND user.user_id IN   ';
        $sSql .= ' (   ';
        $sSql .= ' SELECT fr1.resource_id    ';
        $sSql .= ' FROM ' . $friendTblName . ' AS `fr1`     ';
        $sSql .= ' WHERE fr1.user_id = ' . (int)$userid;
        $sSql .= ' )   ';

        $sSql .= ' AND user.user_id IN    ';
        $sSql .= ' (   ';
        $sSql .= ' SELECT fr2.user_id     ';
        $sSql .= ' FROM ' . $friendTblName . ' AS `fr2`     ';
        $sSql .= ' WHERE fr2.resource_id = ' . (int)$userid;
        $sSql .= ' )   ';

        $sSql .= ' ORDER BY user_id ASC    ';
        $sSql .= ' LIMIT 0 , ' . $iLimit;

        $table = Engine_Api::_()->getDbTable('users', 'user');
        $db = $table->getAdapter();
        $stmt = $db->query($sSql);
        $result = $stmt->fetchAll();
        $buddyList = array();
        foreach ($result as $key => $item) {
            if(empty($item['status'])){
                $item['status'] = 'offline';
            }

            if ($item['message'] == null) {
                $item['message'] = '';
            }

            if (!$item['is_goonline']) {
                $item['status'] = 'offline';
            }

            if (empty($item['grp'])) {
                $item['grp'] = '';
            }
            
            $item['full_name'] = trim($item['full_name']);
            $item['user_name'] = trim($item['user_name']);
            if (empty($item['full_name'])) {
                if (empty($item['user_name'])) $item['full_name'] = 'No Name';
                else $item['full_name'] = $item['user_name'];
            }
            
            $user_item = Engine_Api::_()->user()->getUser($item['user_id']);
            $item['link'] = $user_item->getHref();
            $item['avatar'] = $this->getAvatar($item['user_image']);

            $buddyList[] = $item;
        }

        // end
        return $buddyList;
    }

    public function parseEmoticon($sTxt) {
    $path = 'application/modules/Ynchat/externals/images/ynchat_emoticon/';
    
    $aRows = $this->getAllEmoticon(false);
    $aEmoticons = array();
    foreach ($aRows as $aItem) {
        $aEmoticons[$aItem['text']] = $aItem;
    }
    foreach ($aEmoticons as $sKey => $aEmoticon) {
        $sTxt = str_replace($sKey, '<img src="' . $path . $aEmoticon['image'] . '" alt="' . $aEmoticon['title'] . '" title="' . $aEmoticon['title'] . '" class="v_middle" />', $sTxt);
        $sTxt = str_replace(str_replace('&lt;', '<', $sKey), '<img src="' . $path . $aEmoticon['image'] . '" alt="' . $aEmoticon['title'] . '" title="' . $aEmoticon['title'] . '" class="v_middle" />', $sTxt);
        $sTxt = str_replace(str_replace('>', '&gt;', $sKey), '<img src="' . $path . $aEmoticon['image'] . '" alt="' . $aEmoticon['title'] . '" title="' . $aEmoticon['title'] . '" class="v_middle" />', $sTxt);
    }

    return $sTxt;
    }

    public function getAllEmoticon($format = false){
        $table = Engine_Api::_()->getDbTable('emoticons', 'ynchat');
        $aRows = $table->fetchAll();
        if($format){
            $result = array();
            foreach($aRows as $aItem){
                $result[] = $this->__generateEmoticonData($aItem, 'large');
            }
            return $result;
        }

        return $aRows;
    }
    
    private function __generateEmoticonData($aItem, $sMoreInfo = 'large'){
        $result = array(
            'iEmoticonId' => $aItem['emoticon_id'],
            'sTitle' => $aItem['title'],
            'sText' => $aItem['text'],
            'sImage' => $aItem['image'],
            'iOrdering' => $aItem['ordering'],
        );

        switch ($sMoreInfo) {
            case 'large':
            case 'medium':
            case 'small':
                return $result;
                break;
        }
    }
    
    public function parseSticker($iStickerId){
        $path = 'application/modules/Ynchat/externals/images/ynchat_sticker/';
        $aRow = $this->getStickerById($iStickerId);
        $sText = '';
        if(isset($aRow['sticker_id'])){
            $sText = '<img src="' . $path . $aRow['image'] .'" alt="' . $aRow['title'] . '" title="' . $aRow['title'] . '" />';
        }

        return $sText;
    }
    
    public function getStickerById($iStickerId){
        $table = Engine_Api::_()->getDbTable('stickers', 'ynchat');
        $select = $table->select()->where('sticker_id = ?', $iStickerId);
        $aRow = $table->fetchRow($select);
        return $aRow;
    }
    public function getAvatarByUserId($user_id){
        $user  =  Engine_Api::_()->getItem("user", $user_id);
        if(!$user){
            return "";
        }
        return $this->getAvatar($user->photo_id);
    }
    
    public function __generateMessageData($aItem, $sMoreInfo = 'large'){
        $timeViewer = $this->convertToUserTimeZone($aItem['sent']);
        $sDate = $this->convertTime($timeViewer, 'F j, Y');
        $sTime = $this->convertTime($timeViewer, 'g:i a');

        $sText = $aItem['message'];
        $result = array(
            'iMessageId' => $aItem['message_id'],
            'iSenderId' => $aItem['from'],
            'iReceiverId' => $aItem['to'],
            'sText' => $sText,
            'iTimeStamp' => $aItem['sent'],
            'sTime' => $timeViewer,
            'bRead' => ($aItem['read'] == 1 ? true : false),
            'iDirection' => $aItem['direction'],
            'type' => $aItem['message_type'],
            'data' => $aItem['data'],
            'avatar'=> $this->getAvatarByUserId($aItem['from']),

            'sDate' => $sDate,
            'sTime' => $sTime,
        );
        switch ($sMoreInfo) {
            case 'large':
            case 'medium':
            case 'small':
                return $result;
                break;
        }
    }
    
    public function __getMessageByMessageId($iMessageId){
        $table = Engine_Api::_()->getDbTable('messages', 'ynchat');
        $select = $table->select()->where('message_id = ?', $iMessageId);
        $aRow = $table->fetchRow($select);
        return $aRow;
    }
    
    public function getMessageByMessageId($iMessageId){
        $this->reconnectDatabase();
        $result = $this->__getMessageByMessageId($iMessageId);
        if(isset($result['message_id'])){
            $result = $this->__generateMessageData($result);
        }

        return $result;
    }
    
    public function canSendMessage($iSenderId, $iReceiverId){
        $this->reconnectDatabase();
        $receiverUserSetting = $this->getUserSettingsByUserId($iReceiverId);
        $result = true;
        if($receiverUserSetting['iIsGoOnline'] == 0){
            $result = false;
        } else {
            $this->reconnectDatabase();
            $blockRList = $this->getBlockListBySide($iReceiverId, 'to');
            $blockRUserId = array();
            foreach ($blockRList as $key => $item) {
                $blockRUserId[] = $item['user_id'];
            }
            $this->reconnectDatabase();
            $allowRList = $this->getAllowListBySide($iReceiverId, 'to');
            $allowRUserId = array();
            foreach ($allowRList as $key => $item) {
                $allowRUserId[] = $item['user_id'];
            }
            switch($receiverUserSetting['sTurnOnOff']){
                case 'offall':
                    $result = false;
                    break;
                case 'offsome':
                    if (in_array($iSenderId, $blockRUserId)) {
                        $result = false;
                    }
                    break;
                case 'onsome':
                    if (!in_array($iSenderId, $allowRUserId)) {
                        $result = false;
                    }
                    break;
            }
        }

        return $result;
    }

    public function getMessageWithSenderIdAndReceiverId($iSenderId, $iReceiverId, $iRead = null){
        // init
        if((int)$iReceiverId <= 0 || (int)$iSenderId <= 0){
            return array();
        }

        // process
        $sWhere = '';
        $settings = Engine_Api::_()->getApi('settings', 'core');
        $iLimit = $settings->getSetting('ynchat.num.old.message', 10);
        if($iLimit <= 0){
            $iLimit = 10;
        }
        $table = Engine_Api::_()->getDbTable('messages', 'ynchat');
        $select = $table->select();
        if(null !== $iRead){
            $sWhere .= ' AND read = ' . (int)$iRead;
            $select->where('`from` = ' . $iSenderId . ' AND `to` = ' . $iReceiverId. $sWhere)
            ->order('message_id DESC');
        } else {
            $select->where(' ( ( `from` = ' . $iSenderId . ' AND `to` = ' . $iReceiverId . ' ) OR ( `from` = ' . $iReceiverId . ' AND `to` = ' . $iSenderId . ') ) '
                . $sWhere
            )
            ->order('message_id DESC')
            ->limit($iLimit);
        }
        $aRows = $table->fetchAll($select);
        // end
        if ($aRows) return $aRows->toArray();
        else return array();
    }
    
    public function getUserSettingsByUserId($iUserId){
        if((int)$iUserId <= 0){
            return array();
        }
        $table = Engine_Api::_()->getDbTable('usersettings', 'ynchat');
        $select = $table->select()->where('user_id = ?', $iUserId);
        $aRow = $table->fetchRow($select);
        if(!$aRow){
            $aRow = array(
                'is_notifysound' => 1,
                'is_goonline' => 1,
                'turnonoff' => 'onall',
            );
            
            // insert if not exist
            $setting = $table->createRow();
            $setting->user_id = $iUserId;
            $setting->setFromArray($aRow);
            $setting->save();
        }
        
        $user = Engine_Api::_()->getItem('user', $iUserId);
        $result = array(
            'iUserId' => (int)$iUserId,
            'iIsNotifySound' => (int)$aRow['is_notifysound'],
            'iIsGoOnline' => (int)$aRow['is_goonline'],
            'sTurnOnOff' => $aRow['turnonoff'],
            'sUserName' => $user->username,
            'sFullName' => $user->displayname,
            'sLink' => $user->getHref(),
            'sAvatar' => $this->getAvatar($user->photo_id),
            'aData' => (!$aRow['data']) ? array() : unserialize($aRow['data']),
        );

        return $result;
    }

    public function getBlockListBySide($userid, $side = 'from'){

        $table = Engine_Api::_()->getDbTable('block', 'ynchat');
        $tableName = $table->info('name');
        $select = $table->select();
        switch($side) {
            case 'from':
                $select->from($tableName, "to_id as user_id");
                $select->where('from_id = ?', $userid);
                break;
            case 'to':
                $select->from($tableName, "from_id as user_id");
                $select->where('to_id = ?', $userid);
                break;
        }
        $aRows = $table->fetchAll($select);
        return $aRows;
    }
    
    public function getBlockListBySideMoreInfo($userid, $side = 'from'){

        $sSql = '';
        $aRows = array();
        $blockTable = Engine_Api::_()->getDbTable('block', 'ynchat');
        $blockTableName = $blockTable->info('name');
        $userTable = Engine_Api::_()->getDbTable('users', 'user');
        $userTableName = $userTable->info('name');
        switch($side){
            case 'from':
                $sSql .= ' SELECT
                              user.user_id user_id,
                              user.displayname full_name,
                              user.photo_id user_image,
                              user.username user_name
                        ';
                $sSql .= ' FROM ' . $blockTableName . ' AS `ynb1`     ';
                $sSql .= ' JOIN ' . $userTableName . ' AS `user` ';
                $sSql .= ' ON ynb1.to_id = user.user_id  ';
                $sSql .= ' WHERE ynb1.from_id = ' . (int)$userid;
                break;
            case 'to':
                $sSql .= ' SELECT
                              user.user_id user_id,
                              user.displayname full_name,
                              user.photo_id user_image,
                              user.username user_name
                        ';
                $sSql .= ' FROM ' . $blockTableName . ' AS `ynb2`     ';
                $sSql .= ' JOIN ' . $userTableName . ' AS `user` ';
                $sSql .= ' ON ynb2.from_id = user.user_id  ';
                $sSql .= ' WHERE ynb2.to_id = ' . (int)$userid;
                break;
        }
        if(strlen($sSql) > 0){
            $db = $blockTable->getAdapter();
            $stmt = $db->query($sSql);
            $aRows = $stmt->fetchall();
        }

        return $aRows;
    }

    public function getAllowListBySide($userid, $side = 'from'){

        $table = Engine_Api::_()->getDbTable('allow', 'ynchat');
        $tableName = $table->info('name');
        $select = $table->select();
        switch($side) {
            case 'from':
                $select->from($tableName, "to_id as user_id");
                $select->where('from_id = ?', $userid);
                break;
            case 'to':
                $select->from($tableName, "from_id as user_id");
                $select->where('to_id = ?', $userid);
                break;
        }
        $aRows = $table->fetchAll($select);
        return $aRows;

        return $aRows;
    }
    
    public function getAllowListBySideMoreInfo($userid, $side = 'from'){

        $sSql = '';
        $aRows = array();
        $allowTable = Engine_Api::_()->getDbTable('allow', 'ynchat');
        $allowTableName = $allowTable->info('name');
        $userTable = Engine_Api::_()->getDbTable('users', 'user');
        $userTableName = $userTable->info('name');
        switch($side){
            case 'from':
                $sSql .= ' SELECT
                              user.user_id user_id,
                              user.displayname full_name,
                              user.photo_id user_image,
                              user.username user_name
                        ';
                $sSql .= ' FROM ' . $allowTableName . ' AS `ynb1`     ';
                $sSql .= ' JOIN ' . $userTableName . ' AS `user` ';
                $sSql .= ' ON ynb1.to_id = user.user_id  ';
                $sSql .= ' WHERE ynb1.from_id = ' . (int)$userid;
                break;
            case 'to':
                $sSql .= ' SELECT
                              user.user_id user_id,
                              user.displayname full_name,
                              user.photo_id user_image,
                              user.username user_name
                        ';
                $sSql .= ' FROM ' . $allowTableName . ' AS `ynb2`     ';
                $sSql .= ' JOIN ' . $userTableName . ' AS `user` ';
                $sSql .= ' ON ynb2.from_id = user.user_id  ';
                $sSql .= ' WHERE ynb2.to_id = ' . (int)$userid;
                break;
        }
        if(strlen($sSql) > 0){
            $db = $allowTable->getAdapter();
            $stmt = $db->query($sSql);
            $aRows = $stmt->fetchall();
        }

        return $aRows;
    }

    //TODO add config
    public function getSocketConfig() {
        $settings = Engine_Api::_()->getApi('settings', 'core');
        $iPort = $settings->getSetting('ynchat.websocket.server.port', 9009);
        $iTunnelPort = $settings->getSetting('ynchat.websocket.stunnel.port', 9010);
        $sAction = 'chat';
        $sIpListenServer = '0.0.0.0';
        $iMaxClient = 999999;
        $bCheckOrigin = true;
        $iMaxConnectPerIp = 100;
        $iMaxRequestPerMinute = 2000;

        return array(
            'iPort' => $iPort,
            'iStunnelPort' => $iTunnelPort,
            'sAction' => $sAction,
            'sIpListenServer' => $sIpListenServer,
            'iMaxClient' => $iMaxClient,
            'bCheckOrigin' => $bCheckOrigin,
            'iMaxConnectPerIp' => $iMaxConnectPerIp,
            'iMaxRequestPerMinute' => $iMaxRequestPerMinute,
            'bEnableSSL' => 0,
            'sIpEnable' => $settings->getSetting('ynchat.chatbox.userip', 0),
            'sIpAddress' => $settings->getSetting('ynchat.chatbox.ipaddress', ''),
        );
    }
    
    public function startWebSocketServer(){
        shell_exec('bash ' . ENGINE_DIR . '/ynchat/runcheck.sh start ' . ENGINE_DIR . '/ynchat/');
    }
    
    //TODO add config
    public function getLangAndConfig(){
        $settings = Engine_Api::_()->getApi('settings', 'core');
        $viewer = Engine_Api::_()->user()->getViewer();
        $view = Zend_Registry::get('Zend_View');
        $iPort = $settings->getSetting('ynchat.websocket.server.port', 9009);
        $iTunnelPort = $settings->getSetting('ynchat.websocket.stunnel.port', 9010);
        $sAction = 'chat';
        $iIntervalUpdateFriendList = 10 * 1000;
        $iTimeOut = 30 * 1000;
        $iImageSizeLimit = $settings->getSetting('ynchat.photo.maxsize', 1024);
        $iFileSizeLimit = $settings->getSetting('ynchat.file.maxsize', 1024);
        $iNumberOfFriendList = $settings->getSetting('ynchat.num.show.friends', 1000);
        $iNumberOfFriendSearch = $settings->getSetting('ynchat.num.search.friends', 10);

        $aEmoticon = $this->getAllEmoticons(true);
        $aSticker = $this->getAllStickers(true);
        $aBanWord = $this->getAllBanWords();
        $sPicUrl = $view->baseUrl() .'/application/modules/Ynchat/externals/images/';
        $chatBoxPosition = $settings->getSetting('ynchat.chatbox.position', 1);
        
        return array(
            'lang' => array(
                'your_browser_does_not_support_javascript' => $view->translate('Your browser does not support JavaScript!'),
                'nothing_friend_s_found' => $view->translate('No friends found.'),
                'connection_timed_out_please_try_again' => $view->translate('Connection timed out, please try again.'),
                'search' => $view->translate('Search'),
                'too_big_image_file' => $view->translate('Too big image file!'),
                'please_try_another_image' => $view->translate('Please try another image.'),
                'upload' => $view->translate('Upload'),
                'add' => $view->translate('Add'),
                'view_old_conversation' => $view->translate('View old conversation'),
                'yesterday' => $view->translate('Yesterday'),
                'days' => $view->translate('days'),
                'month' => $view->translate('months'),
                'show_message_from' => $view->translate('Show messages from'),
                'advanced_settings' => $view->translate('Advanced settings'),
                'close_all_chat_tabs' => $view->translate('Close all chat tabs'),
                'go_offline' => $view->translate('Go Offline'),
                'go_online' => $view->translate('Go Online'),
                'play_sound_on_new_message' => $view->translate('Play sound on new message'),
                'yes' => $view->translate('Yes'),
                'no' => $view->translate('No'),
                'enter_name' => $view->translate('Enter name'),
                'searching' => $view->translate('Searching...'),
                'turn_on_chat_except' => $view->translate('Turn on chat except'),
                'turn_on_chat_for_only_some_friends' => $view->translate('Turn on chat for only some friends'),
                'save' => $view->translate('Save'),
                'chat' => $view->translate('Chat'),
                'mobile' => $view->translate('Mobile'),
                'web' => $view->translate('Web'),
                'message_history' => $view->translate('Message History'),
                'video' => $view->translate('Video'),
                'photo' => $view->translate('Photo'),
                'add_files' => $view->translate('Add file(s)'),
                'upload_photo' => $view->translate('Upload Photo'),
                'choose_a_sticker_or_emoticon' => $view->translate('Choose a sticker or emoticon'),
                'please_try_again_make_sure_you_are_uploading_a_valid_photo' => $view->translate('Please try again. Make sure you are uploading a valid photo. Supported photo types: png, gif, jpeg, pjpeg. Max size of photo upload: %s Kb', $iImageSizeLimit),
                'unable_to_connect_to_chat_check_your_internet_connection' => $view->translate('Unable to connect to chat. Check your Internet connection.'),
                'file_is_too_large' => $view->translate('Error: File is too large.'),
                'max_file_size' => $view->translate('Max size of upload file: %s Kb', $iFileSizeLimit),
                'deleted_message' => $view->translate('This message has been deleted')
            ),
            'config' => array(
                'sServerUrl' => trim($_SERVER['HTTP_HOST'], '/'),
                'sSiteLink' => $view->baseUrl().'/',
                'iTimeOut' => $iTimeOut,
                'iPort' => $iPort,
                'iStunnelPort' => $iTunnelPort,
                'sAction' => $sAction,
                'sUserIdHash' => $this->encryptUserId($viewer->getIdentity()),
                'iIntervalUpdateFriendList' => $iIntervalUpdateFriendList,
                'iNumberOfFriendList' => $iNumberOfFriendList,
                'iNumberOfFriendSearch' => $iNumberOfFriendSearch,
                'iImageSizeLimit' => $iImageSizeLimit,
                'iFileSizeLimit' => $iFileSizeLimit,
                'bIsEnableVideoAction' => 0,
                'bIsEnablePhotoAction' => 1,
                'bIsEnableLinkAction' => 1,
                'bIsEnableEmoticonStickerAction' => 1,
                'aEmoticon' => $aEmoticon,
                'aSticker' => $aSticker,
                'sPicUrl' => $sPicUrl,
                'aBanWord' => $aBanWord,
                'bEnableSSL' => 0,
                'sApiKeyEmbedly' => $settings->getSetting('ynchat.embedly.api.key', '90659766ff4e43a9b9eeabfe9768d75c'),
                'iPlacementOfChatFrame' => $chatBoxPosition,
                'sIpEnable' => $settings->getSetting('ynchat.chatbox.userip', 0),
                'sIpAddress' => $settings->getSetting('ynchat.chatbox.ipaddress', ''),
            ),
            'usersettings' => $this->getUserSettingsByUserId($viewer->getIdentity()),
        );
    }

    public function getAllEmoticons($format = false) {
        $table = Engine_Api::_()->getDbTable('emoticons', 'ynchat');
        $rows = $table->fetchAll();
        $emoticons = array();
        if (!$format) return $rows;
        foreach ($rows as $row) {
            $icon = array(
                'iEmoticonId' => $row->emoticon_id,
                'sTitle' => $row->title,
                'sText' => $row->text,
                'sImage' => $row->image,
                'iOrdering' => $row->ordering,
            );
            array_push($emoticons, $icon);
        }
        return $emoticons;
    }
    
    public function getAllStickers($format = false) {
        $table = Engine_Api::_()->getDbTable('stickers', 'ynchat');
        $rows = $table->fetchAll();
        if (!$format) return $rows;
        $stickers = array();
        foreach ($rows as $row) {
            $sticker = array(
                'iStickerId' => $row->sticker_id,
                'sTitle' => $row->title,
                'sImage' => $row->image,
                'iOrdering' => $row->ordering,
            );
            array_push($stickers, $sticker);
        }
        return $stickers;
    }
    
    public function getAllSenderIdOfReceiverId($iReceiverId, $iRead = null){
        // init
        if((int)$iReceiverId <= 0){
            return array();
        }

        // process
        $sWhere = '';
        if(null !== $iRead){
            $sWhere .= ' AND ynm.read = ' . (int)$iRead;
        }
        $table = Engine_Api::_()->getDbTable('messages', 'ynchat');
        $tableName = $table->info('name');
        $select = $table->select()->distinct()
            ->from("$tableName as ynm", 'ynm.*')
            ->where('ynm.to = ' . $iReceiverId
                . $sWhere
            )
            ->order('ynm.from ASC');
        $aRows = $table->fetchAll($select);
        // end
        return $aRows;
    }
    
    public function getAllBanWords() {
        $table = Engine_Api::_()->getDbTable('banwords', 'ynchat');
        $rows = $table->fetchAll();
        $banwords = array();
        foreach ($rows as $row) {
            $banword = array(
                'find_value' => $row->find_value,
                'replacement' => $row->replacement
            );
            array_push($banwords, $banword);
        }
        return $banwords;
    }
    
    public function encryptUserId($user_id) {

        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'qJB0rGtIn5UB1xG03efyCp';
        $secret_iv = 'qJB0rGtIn5UB1xG03efyCp' . '_iv';

        // hash
        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        $output = openssl_encrypt($user_id, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);

        return $output;
    }
    
    public function getAvatar($id) {
        if ($id) {
            $photo = Engine_Api::_()->getItemTable('storage_file')->getFile($id, 'thumb.icon');
            if( !$photo ) {
                return 'application/modules/User/externals/images/nophoto_user_thumb_icon.png';
            }
            return $photo->map();
        }
        return 'application/modules/User/externals/images/nophoto_user_thumb_icon.png';
    }
    
    public function __getInfoFriend($iFriendId){
        $friend = Engine_Api::_()->user()->getUser($iFriendId);
        if (!$friend) {
            return array();
        }
        $userTbl = Engine_Api::_()->getDbTable('users', 'user');
        $userTblName = $userTbl->info('name');
        $statusTbl = Engine_Api::_()->getDbTable('status', 'ynchat');
        $statusTblName = $statusTbl->info('name');
        $select = $userTbl->select()
        ->from("$userTblName as user", "user.user_id as user_id,
                      user.displayname as full_name,
                      user.photo_id as user_image,
                      user.username as user_name,
                      user.status as message,
                      IFNULL(ys.status, 'offline')  as status")
         ->joinLeft("$statusTblName as ys","ys.user_id = user.user_id", "")
         ->where('user.user_id = ' . $iFriendId);      
        $aRow = $userTbl->fetchRow($select);
        if(isset($aRow['user_id']) == false){
            $aRow = array();
        } else {
            if(empty($aRow['status'])){
                $aRow['status'] = 'offline';
            }

            if ($aRow['message'] == null) {
                $aRow['message'] = '';
            }
            $aRow = $aRow->toArray();
            $aRow['link'] = $friend->getHref();
            $aRow['avatar'] = $this->getAvatar($aRow['user_image']);

            if (empty($aRow['grp'])) {
                $aRow['grp'] = '';
            }
        }

        return $aRow;
    }

    public function __getMessagesByFriendId($iFriendId, $iMessageId = null, $bLoadAll = false, $sOrder = null, $iStartTime = null, $iEndTime = null){
        $sWhere = '';
        $viewer = Engine_Api::_()->user()->getViewer();
        if((int)$iMessageId > 0){
            $sWhere .= ' AND ynm.message_id < ' . (int)$iMessageId;
        }
        if((int)$iStartTime > 0 && (int)$iEndTime > 0){
            $sWhere .= ' AND ynm.sent >= ' . (int)$iStartTime . ' AND ynm.sent <= ' . (int)$iEndTime;
        }
        $table = Engine_Api::_()->getDbTable('messages', 'ynchat');
        $tableName = $table->info('name');
        $select = $table->select()
        ->from("$tableName as ynm", "ynm.*")
        ->where(' ((ynm.from = ' . $viewer->getIdentity() . ' AND ynm.to = ' . $iFriendId
                . ') OR (ynm.from = ' . $iFriendId . ' AND ynm.to = ' . $viewer->getIdentity() . ')) '
                . $sWhere
                );
        if(null !== $sOrder){
            $select->order($sOrder);
        } else {
            $select->order('ynm.message_id DESC');
        }
        if($bLoadAll == false){
            $settings = Engine_Api::_()->getApi('settings', 'core');
            $iLimit = $settings->getSetting('ynchat.num.old.message', 10);
            if($iLimit <= 0){
                $iLimit = 10;
            }
            $select->limit($iLimit);
        }
        $aRows = $table->fetchAll($select);

        if ($aRows) return $aRows->toArray();
        else return array();
    }

    public function addSeparatorMessage($aMessages, $iSenderId, $iReceiverId){
        $result = array();
        $view = Zend_Registry::get('Zend_View');
        if(count($aMessages) > 0){
            $now = $this->getTimeStamp();
            $nowOfViewer = $this->convertToUserTimeZone($now);
            $y = $this->convertTime($nowOfViewer, 'Y-m-d');
            $oneOfViewer = $this->convertToUserTimeZone($aMessages[0]['iTimeStamp']);
            $x = $this->convertTime($oneOfViewer, 'Y-m-d');
            $z = null;
            $separator = null;
            $limit = 5 * 60 * 60;

            // first check
            if($x == $y){
                $separator = $view->timestamp($aMessages[0]['iTimeStamp']);
                $z = $aMessages[0]['iTimeStamp'];
            } else {
                $separator = $this->convertTime($oneOfViewer, 'F j, Y');
            }
            $result[] = array(
                'iMessageId' => 0,
                'iSenderId' => $iSenderId,
                'iReceiverId' => $iReceiverId,
                'sText' => '<div><span>' . $separator . '</span></div>',
                'iTimeStamp' => $aMessages[0]['iTimeStamp'] - 1,
                'bRead' => true,
                'iDirection' => 0,
                'sType' => 'separator'
            );

            // check all
            $separator = null;
            foreach($aMessages as $aItem){
                $dateOfViewer = $this->convertToUserTimeZone($aItem['iTimeStamp']);
                $date = $this->convertTime($dateOfViewer, 'Y-m-d');
                if($date != $x){
                    $x = $date;
                    $separator = $this->convertTime($dateOfViewer, 'F j, Y');
                }
                if($x == $y){
//                    if(null == $z){
//                        $z = $aItem['iTimeStamp'];
//                    }

                    if($aItem['iTimeStamp'] - $z > $limit || null == $z){
                        $z = $aItem['iTimeStamp'];
                        $separator = $view->timestamp($aItem['iTimeStamp']);
                    }
                }

                if(null != $separator){
                    $result[] = array(
                        'iMessageId' => 0,
                        'iSenderId' => $iSenderId,
                        'iReceiverId' => $iReceiverId,
                        'sText' => '<div><span>' . $separator . '</span></div>',
                        'iTimeStamp' => $aItem['iTimeStamp'] - 1,
                        'bRead' => true,
                        'iDirection' => 0,
                        'sType' => 'separator'
                    );

                    $separator = null;
                }

                $result[] = $aItem;
            }
        }

        return $result;
    }
    
    public function addFile($userId, $receiverId, $title, $type, $storage_file_id) {
        $db = Engine_Api::_()->getDbtable('files', 'ynchat')->getAdapter();
        $db->beginTransaction();
        try {
            $table = Engine_Api::_()->getDbtable('files', 'ynchat');
            $file = $table->createRow();
            $file->user_id = $userId;
            $file->receiver_id = $receiverId;
            $file->title = $title;
            $file->type = $type;
            $file->storage_file_id = $storage_file_id;
            $file->save();
        }
        catch( Exception $e ) {
            $db->rollBack();
            throw $e;
        }       

        $db->commit();
        return $file->getIdentity();
    }
    
    public function updateStatusMessageBySenderId($iSenderId, $iRead = 1){
        if((int)$iSenderId <= 0){
            return false;
        }
        $table = Engine_Api::_()->getDbtable('messages', 'ynchat');
        $db = $table->getAdapter();
        $db->beginTransaction();
        
        $unRead = ($iRead) ? 0 : 1;
        try {
            $table->update(array(
                'read' => $iRead,
            ), array(
                '`from` = ?' => $iSenderId,
                '`read` = ?' => $unRead,
            ));
      
        
        } catch( Exception $e ) {
            $db->rollBack();
            throw $e;
        }
        $db->commit();
        return true;
    }
    
    /**
     * Show datetime in interface
     */
    public function convertToUserTimeZone($iTime) {
        $viewer = Engine_Api::_()->user()->getViewer();
        $timezone = Engine_Api::_()->getApi('settings', 'core')
        ->getSetting('core_locale_timezone', 'GMT');
        if( $viewer && $viewer->getIdentity() && !empty($viewer->timezone) ) {
            $timezone = $viewer->timezone;
        }
        $dateTimeZone = new DateTimeZone($timezone);
        $creation_date = new DateTime();
        $creation_date->setTimestamp($iTime);
        $creation_date->setTimezone($timezone);
        $offset = $dateTimeZone->getOffset($creation_date);
        return ($iTime + $offset);
    }
    

    public function convertTime($iTimeStamp, $format = '') {
        if(!$iTimeStamp) {
            return 'none';
        }
        return date($format, $iTimeStamp);
    }
    
    public function getBrowser() {
        static $sAgent;
        
        if ($sAgent)
        {
            return $sAgent;
        }
            
        $sAgent = $this->getServer('HTTP_USER_AGENT'); 
        if (preg_match("/Firefox\/(.*)/i", $sAgent, $aMatches) && isset($aMatches[1])) {
            $sAgent = 'Firefox ' . $aMatches[1];
        }
        elseif (preg_match("/MSIE (.*);/i", $sAgent, $aMatches)) {
            if(preg_match("/Phone\s?O?S?\s?(.*)/i", $aMatches[1]))
            {
                $this->_bIsMobile = true;
                $aParts = explode(' ', trim($aMatches[1]));
                $sAgent = 'MSIE Windows Phone ' . $aParts[0];
            }
            else
            {
                $aParts = explode(';', $aMatches[1]);
                $sAgent = 'IE ' . $aParts[0];
            }
        }
        elseif (preg_match("/Opera\/(.*)/i", $sAgent, $aMatches))
        {
            if(preg_match("/mini/i", $aMatches[1]))
            {
                $this->_bIsMobile = true;
                $aParts = explode(' ', trim($aMatches[1]));
                $sAgent = 'Opera Mini ' . $aParts[0];
            }
            else
            {
                $aParts = explode(' ', trim($aMatches[1]));
                $sAgent = 'Opera ' . $aParts[0];
            }
        }
        elseif (preg_match('/\s+?chrome\/([0-9.]{1,10})/i', $sAgent, $aMatches))
        {
            if (preg_match('/android/i', $sAgent))
            {
                $this->_bIsMobile = true;
                $sAgent = 'Android';
            }
            else
            {
                $aParts = explode(' ', trim($aMatches[1]));
                $sAgent = 'Chrome ' . $aParts[0];
            }
        }
        elseif (preg_match('/android/i', $sAgent))
        {
            $this->_bIsMobile = true;
            $sAgent = 'Android';            
        }    
        elseif (preg_match('/opera mini/i', $sAgent))
        {
            $this->_bIsMobile = true;
            $sAgent = 'Opera Mini';         
        }   
        elseif (preg_match('/(pre\/|palm os|palm|hiptop|avantgo|fennec|plucker|xiino|blazer|elaine)/i', $sAgent))
        {
            $this->_bIsMobile = true;
            $sAgent = 'Palm';           
        }       
        elseif (preg_match('/blackberry/i', $sAgent))
        {
            $this->_bIsMobile = true;
            $sAgent = 'Blackberry';
        }       
        elseif (preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile|windows phone)/i', $sAgent))
        {
            $this->_bIsMobile = true;
            $sAgent = 'Windows Smartphone';
        }       
        elseif (preg_match("/Version\/(.*) Safari\/(.*)/i", $sAgent, $aMatches) && isset($aMatches[1]))
        {
            if (preg_match("/iPhone/i", $sAgent) || preg_match("/ipod/i", $sAgent) || preg_match("/iPad/i", $sAgent))
            {
                $aParts = explode(' ', trim($aMatches[1]));
                $sAgent = 'Safari iPhone ' . $aParts[0];    
                $this->_bIsMobile = true;
            }
            else 
            {
                $sAgent = 'Safari ' . $aMatches[1];
            }
        }
        elseif (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i', $sAgent))
        {
            $this->_bIsMobile = true;
        }
        
        return $sAgent;        
    }
    
    public function getServer($sVar) {
        switch($sVar) {
            case 'SERVER_NAME':
                $sVar = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? 'HTTP_X_FORWARDED_HOST' : $sVar);
                break;
            case 'HTTP_HOST':
                $sVar = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? 'HTTP_X_FORWARDED_HOST' : $sVar);
                break;
            case 'REMOTE_ADDR':
                return $this->getIp();
                break;
        }
        return (isset($_SERVER[$sVar]) ? $_SERVER[$sVar] : '');
    }
    
    public function isMobile() {
        return $this->_bIsMobile;
    }
    
    public function writeCheckAlive($path) {
        $filepath = $path . 'checkalive' . '.log';
        $message = '';

        if (!file_exists($filepath))
        {
            $message .= "";
        }

        if (!$fp = @fopen($filepath, 'w'))
        {
            return FALSE;
        }

        $message .= $this->getTimeStamp();

        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, 0666);
        return TRUE;
    }
    
    public function readCheckAlive($path) {
        $filepath = $path . 'checkalive' . '.log';
        if (!file_exists($filepath))
        {
            return 0;
        }
        $number = trim(file_get_contents($filepath));

        return (int)$number;
    }
    
    public function reconnectDb() {
        $db = Engine_Db_Table::getDefaultAdapter();
        try {
            $db->closeConnection();
            $conn = $db->getConnection();
        }
        catch(Exception $e) {}
        return;
    }
    
    public function reconnectDatabase(){ 
        // check ONLY action from server socket
        if(defined('YNCHAT_DIR')){
            $newCheck = $this->getTimeStamp();
            $oldCheck = $this->readCheckAlive(ENGINE_DIR . 'ynchat/log/');
            if(($newCheck - $oldCheck) > CHECK_ALIVE_CONNECTION_DB_INTERVAL){
                $this->reconnectDb();   
                $this->writeCheckAlive(ENGINE_DIR . 'ynchat/log/');
            }
        }
    }
}        
?>
