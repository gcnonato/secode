<?php
/**
 * SocialEngine
 *
 * @category   Application_Ynmobile
 * @package    Ynmobile
 * @copyright  Copyright 2013-2013 YouNet Company
 * @license    http://socialengine.younetco.com/
 * @version    $Id: Video.php minhnc $
 * @author     MinhNC
 */

class Ynmobile_Service_Video extends Ynmobile_Service_Base
{

    protected $module = 'video';
    protected $mainItemType = 'video';
    /**
     * @since 4.08
     */
    function fetch($aData)
    {
        extract($aData);

        $viewer = Engine_Api::_() -> user() -> getViewer();

        $iPage = isset($iPage)?intval($iPage): 1;
        $iLimit  = isset($iLimit)?intval($iLimit):10;
        $sView   = isset($sView)?$sView: 'all';
        $sOrder  = isset($sOrder)?$sOrder: 'creation_date';
        $sSearch = isset($sSearch)?$sSearch:'';
        $sItemType  =  isset($sItemType)? $sItemType: '';
        $iItemId  =  isset($iItemId)? $iItemId: '';
        $iCategory  =  isset($iCategory)?intval($iCategory): 0;
        $sParentType = isset($sParentType)?$sParentType: '';
        $iParentId = isset($iParentId)?intval($iParentId): '';

        // support ynlisting
        if($sParentType == 'ynlistings_listing'){
            return Engine_Api::_()->getApi('ynlisting','ynmobile')->fetch_video(array(
                'iListingId'=>$iParentId,
                'iPage'=>$iPage,
                'iLimit'=>$iLimit,
            ));
        }

        // support ynbusinesspages
        if($sParentType == 'ynbusinesspages_business'){
            return Engine_Api::_()->getApi('ynbusinesspages','ynmobile')->fetch_video(array(
                'iBusinessId'=>$iParentId,
                'iPage'=>$iPage,
                'iLimit'=>$iLimit,
            ));
        }

        $table = $this->getWorkingTable('videos','video');

        $rName = $table -> info('name');

        $tmTable = Engine_Api::_() -> getDbtable('TagMaps', 'core');

        $tmName = $tmTable -> info('name');

        $select = $table -> select() -> from($table -> info('name'));

        if ($sOrder)
        {
            $select->order($sOrder . " DESC");
        }
        else
        {
            $select = $select->order("$rName.video_id DESC");
        }

        if (!empty($sSearch))
        {
            $searchTable = Engine_Api::_() -> getDbtable('search', 'core');
            $db = $searchTable -> getAdapter();
            $sName = $searchTable -> info('name');

            $select
                ->joinRight($sName, $sName . '.id=' . $rName . '.video_id', null)
                ->where($sName . '.type = ?', 'video')
                ->where($sName . '.title LIKE ?', "%{$aData['sSearch']}%");
        }

        if ($sView == 'pending')
        {
            $select -> where($rName . '.status = 0');
        }

        if ($sView == 'my' && $viewer -> getIdentity())
        {
            $select
                -> where("$rName.owner_id = ?", $viewer -> getIdentity())
                -> where("$rName.owner_type=?", $viewer ->getType())
            ;
        }else if($iItemId && $sItemType){
            $select
                -> where("$rName.owner_id = ?", $viewer -> getIdentity())
                -> where("$rName.owner_type=?", $viewer ->getType())
                -> where("$rName.search=1");
            ;
        }
        else
        {
            $select -> where("$rName.search = 1");
        }

        if ($iCategory){
            $select -> where($rName . '.category_id = ?', $iCategory);
        }

        if ($sParentType && $iParentId){
            $select -> where($rName . '.parent_type = ?', $sParentType);
            $select -> where($rName . '.parent_id = ?', $iParentId);
        }

        return Ynmobile_AppMeta::_exports_by_page($select, $iPage, $iLimit, array('listing'));

    }
    /**
     * Input data:
     * + sType : string, required.
     *
     * Output data:
    - sPrivacyValue
    - sPrivacyName
     *
     * @see Mobile - API SE/Api V3.0
     * @see album/privacy
     *
     * @param array $aData
     * @return array
     */
    public function privacy($aData)
    {
        $roles = array(
            'everyone' => 'Everyone',
            'registered' => 'All Registered Members',
            'owner_network' => 'Friends and Networks',
            'owner_member_member' => 'Friends of Friends',
            'owner_member' => 'Friends Only',
            'owner' => 'Just Me'
        );
        $sType = isset($aData['sType']) ? $aData['sType'] : 'view';
        $viewer = Engine_Api::_() -> user() -> getViewer();
        switch ($sType)
        {
            case 'view' :
                $viewOptions = (array)Engine_Api::_() -> authorization() -> getAdapter('levels') -> getAllowed('video', $viewer, 'auth_view');
                return array_intersect_key($roles, array_flip($viewOptions));
                break;

            case 'comment' :
                $commentOptions = (array)Engine_Api::_() -> authorization() -> getAdapter('levels') -> getAllowed('video', $user, 'auth_comment');
                return array_intersect_key($roles, array_flip($commentOptions));
                break;
        }
    }
    /**
     * Input data:
     * + sLink: string, required.
     * + iType: string, required.
     *
     * Output data:
     * + iVideoId: integer.
     * + iPhotoId: integer.
     * + sTitle: string.
     * + sDescription: string.
     * + sThumb: string.
     *
     * @see Mobile - API SE/Api V2.0
     * @see link/parser
     *
     * @param array $aData
     * @return array
     */
    public function parser($aData)
    {
        $sLink = isset($aData['sLink']) ? $aData['sLink'] : '';
        $iType = isset($aData['iType']) ? $aData['iType'] : 0;
        if (empty($sLink) || !$iType)
        {
            return array(
                'error_message' => Zend_Registry::get('Zend_Translate') -> _("Parameter is not valid!"),
                'error_code' => 1
            );
        }
        if (isset($aData['sParentType']) && $aData['sParentType'] == "message")
        {
            $composer_type = 'message';
        }
        else
        {
            $composer_type = 'wall';
        }
        $viewer = Engine_Api::_() -> user() -> getViewer();
        $sLink = trim(strip_tags($sLink));
        $code = $this -> extractCode($sLink, $iType);
        // check if code is valid
        // check which API should be used
        if ($iType == 1)
        {
            $valid = $this -> checkYouTube($code);
        }
        if ($iType == 2)
        {
            $valid = $this -> checkVimeo($code);
        }

        // check to make sure the user has not met their quota of # of allowed video uploads
        // set up data needed to check quota
        $values['user_id'] = $viewer -> getIdentity();

        $paginator = $this->getWorkingApi('core','video')
            -> getVideosPaginator($values);

        $quota = Engine_Api::_() -> authorization() -> getPermission($viewer -> level_id, 'video', 'max');

        $current_count = $paginator -> getTotalItemCount();

        if (($current_count >= $quota) && !empty($quota))
        {
            // return error message
            return array(
                'error_message' => Zend_Registry::get('Zend_Translate') -> _("You have already uploaded the maximum number of videos allowed. If you would like to upload a new video, please delete an old one first."),
                'error_code' => 1
            );
        }

        else
            if ($valid)
            {
                $db = $this -> getWorkingTable('videos', 'video') -> getAdapter();
                $db -> beginTransaction();

                try
                {
                    $information = $this -> handleInformation($iType, $code);

                    // create video
                    $table = $this -> getWorkingTable('videos', 'video');
                    $video = $table -> createRow();
                    $video -> title = $information['title'];
                    $video -> description = $information['description'];
                    $video -> duration = $information['duration'];
                    $video -> owner_id = $viewer -> getIdentity();
                    $video -> parent_id = $viewer -> getIdentity();
                    $video -> parent_type = 'user';
                    $video -> code = $code;
                    $video -> type = $iType;
                    if (isset($aData['sParentType']) && $aData['sParentType'] == "message")
                    {
                        $video -> search = 0;
                    }
                    $video -> save();

                    // Now try to create thumbnail
                    $thumbnail = $this -> handleThumbnail($video -> type, $video -> code);
                    $ext = ltrim(strrchr($thumbnail, '.'), '.');
                    $thumbnail_parsed = @parse_url($thumbnail);

                    $tmp_file = APPLICATION_PATH . '/temporary/link_' . md5($thumbnail) . '.' . $ext;
                    $thumb_file = APPLICATION_PATH . '/temporary/link_thumb_' . md5($thumbnail) . '.' . $ext;
                    $src_fh = fopen($thumbnail, 'r');
                    $tmp_fh = fopen($tmp_file, 'w');
                    stream_copy_to_stream($src_fh, $tmp_fh, 1024 * 1024 * 2);

                    $image = Engine_Image::factory();
                    $image -> open($tmp_file) -> resize(640, 480) -> write($thumb_file) -> destroy();

                    $thumbFileRow = Engine_Api::_() -> storage() -> create($thumb_file, array(
                        'parent_type' => $video -> getType(),
                        'parent_id' => $video -> getIdentity()
                    ));

                    // If video is from the composer, keep it hidden until the post is complete
                    if ($composer_type)
                        $video -> search = 0;

                    $video -> photo_id = $thumbFileRow -> file_id;
                    $video -> status = 1;
                    $video -> save();
                    $db -> commit();
                }

                catch( Exception $e )
                {
                    $db -> rollBack();
                    throw $e;
                }

                // make the video public
                $auth = Engine_Api::_() -> authorization() -> context;
                if ($composer_type === 'wall')
                {
                    // CREATE AUTH STUFF HERE
                    $roles = array(
                        'owner',
                        'owner_member',
                        'owner_member_member',
                        'owner_network',
                        'registered',
                        'everyone'
                    );
                    foreach ($roles as $i => $role)
                    {
                        $auth -> setAllowed($video, $role, 'view', ($i <= $roles));
                        $auth -> setAllowed($video, $role, 'comment', ($i <= $roles));
                    }
                }
                else if ($composer_type == 'message')
                {
                    $auth -> setAllowed($video, 'owner', 'view', 1);
                    $auth -> setAllowed($video, 'owner', 'comment', 1);
                }

                $sProfileImage = $video -> getPhotoUrl(TYPE_OF_USER_IMAGE_PROFILE);
                if ($sProfileImage)
                {
                    $sProfileImage = Engine_Api::_() -> ynmobile() -> finalizeUrl($sProfileImage);
                }
                else
                {
                    $sProfileImage = NO_VIDEO_MAIN;
                }
                return array(
                    'iVideoId' => $video -> video_id,
                    'iPhotoId' => $video -> photo_id,
                    'sTitle' => $video -> title,
                    'sDescription' => $video -> description,
                    'sThumb' => $sProfileImage,
                );
            }
            else
            {
                return array(
                    'error_message' => Zend_Registry::get('Zend_Translate') -> _("We could not find a video there - please check the URL and try again."),
                    'error_code' => 1
                );
            }
    }

    /**
     * @since 4.08
     */
    public function detail($aData)
    {
        extract($aData);

        if(empty($fields)) $fields = 'detail';
        $fields = explode(',',$fields);

        $video = $this->getWorkingTable('videos','video')->findRow(intval($iVideoId));

        if (!$video){
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("Video does not exists!")
            );
        }

        return Ynmobile_AppMeta::getInstance()->getModelHelper($video)->toArray($fields);
    }

    /**
     * Delete image only.
     *
     * Input data:
     * + iVideoId: int, required.
     *
     * Output data:
     * + result: int.
     * + error_code: int.
     * + message: string.
     *
     * @see Mobile - API SE/Api V3.0
     * @see video/deleteImage
     *
     * @param array $aData
     * @return array
     */
    public function deleteImage($aData)
    {

        extract($aData);

        $iVideoId = intval($iVideoId);

        $video = $this -> getWorkingItem('video', (int)$aData['iVideoId']);

        $table = $this->getWorkingTable('videos','video');

        $video = $table->findRow($iVideoId);

        if (!$video){
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("Video does not exists!"),
            );
        }

        if (!Engine_Api::_() -> authorization() -> isAllowed($video, null, 'edit')){
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("You don't have permission to edit this video!"),
                'result' => 0
            );
        }

        $video -> photo_id = 0;
        $video -> save();

        return array(
            'result' => 1,
            'error_code' => 0,
            'message'=>Zend_Registry::get('Zend_Translate') -> _("Delete image successfully!")
        );
    }

    /** input data:
     * + parent_type: string, optional.
     * + parent_id: int, optional.
     * + category_id: string, optional.
     * + title: string, required.
     * + description: string, optional.
     * + search: int, optional.
     * + auth_view: string, optional.
     * + auth_comment: string, optional.
     * + type: int, required Ex: 1,2,4,5 (3: upload video from PC)
     * + sUrl: string, required
     *
     * output data:
     * + result: 1 if success and 0 otherwise.
     * + error_code: 1 if error, and 0 otherwise.
     * + message: Message to show the bug.
     * + iVideoId: Video id.
     * + sVideoTitle: Title of video.
     *
     * @see Mobile - API SE/Api V3.0
     * @see video/create
     *
     * @param array $aData
     * @return array
     *
     */
    public function create($aData)
    {
        $sUrl = isset($aData['sUrl']) ? $aData['sUrl'] : '';
        $iListingId  = 0;

        if (!$sUrl)
        {
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("Url is not valid!"),
            );
        }

        $viewer = Engine_Api::_() -> user() -> getViewer();

        $videoTable = $this->getWorkingTable('videos','video');

        $video = $videoTable->fetchNew();

        $itemType =  $video->getType();

        if (!Engine_Api::_() -> authorization() -> isAllowed($itemType, null, 'create'))
        {
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("You don't have permission to create video!"),

            );
        }

        $values['user_id'] = $viewer -> getIdentity();

        $paginator = $this -> getWorkingApi('core','video')-> getVideosPaginator($values);

        $quota = Engine_Api::_() -> authorization() -> getPermission($viewer -> level_id, 'video', 'max');

        $current_count = $paginator -> getTotalItemCount();

        if (($current_count >= $quota) && !empty($quota))
        {
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("You have already uploaded the maximum number of videos allowed. If you would like to upload a new video, please delete an old one first.")
            );
        }

        $parent_type = 'user';
        $parent_id = $viewer -> getIdentity();
        $type = 0;
        if (!empty($aData['parent_type']))
        {
            $parent_type = $aData['parent_type'];
        }
        if (!empty($aData['parent_id']))
        {
            $parent_id = $aData['parent_id'];
        }
        if (!empty($aData['type']))
        {
            $type = $aData['type'];
        }

        $isYNListingItem =  false;
        if($parent_type == 'ynlistings_listing'){
            $isYNListingItem =  true;
            $iListingId = $aData['parent_id'];
        }

        $values = $aData;
        $values['owner_type'] =  $viewer->getType();
        $values['owner_id'] = $viewer -> getIdentity();
        $values['parent_type'] = $isYNListingItem?'user':$parent_type;
        $values['parent_id'] = $isYNListingItem?$parent_id:$viewer -> getIdentity();
        $values['type'] = $type;
        $code = $this -> extractCode($sUrl, $type);
        // check if code is valid
        // check which API should be used
        if ($type == 1)
        {
            $valid = $this -> checkYouTube($code);
        }
        if ($type == 2)
        {
            $valid = $this -> checkVimeo($code);
        }
        if (!$valid)
        {
            return array(
                'error_code' => 1,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _("Url is not valid!")
            );
        }
        $values['code'] = $code;
        $insert_action = false;


        $db = $videoTable->getAdapter();

        $db -> beginTransaction();
        try
        {
            // Create video

            $video -> setFromArray($values);
            $video -> save();

            // Now try to create thumbnail
            $thumbnail = $this -> handleThumbnail($video -> type, $video -> code);
            $ext = ltrim(strrchr($thumbnail, '.'), '.');
            $thumbnail_parsed = @parse_url($thumbnail);

            if (@GetImageSize($thumbnail))
            {
                $valid_thumb = true;
            }
            else
            {
                $valid_thumb = false;
            }

            if ($valid_thumb && $thumbnail && $ext && $thumbnail_parsed && in_array($ext, array(
                    'jpg',
                    'jpeg',
                    'gif',
                    'png'
                )))
            {
                $tmp_file = APPLICATION_PATH . '/temporary/link_' . md5($thumbnail) . '.' . $ext;
                $thumb_file = APPLICATION_PATH . '/temporary/link_thumb_' . md5($thumbnail) . '.' . $ext;

                $src_fh = fopen($thumbnail, 'r');
                $tmp_fh = fopen($tmp_file, 'w');
                stream_copy_to_stream($src_fh, $tmp_fh, 1024 * 1024 * 2);

                $image = Engine_Image::factory();
                $image -> open($tmp_file) -> resize(120, 240) -> write($thumb_file) -> destroy();

                try
                {
                    $thumbFileRow = Engine_Api::_() -> storage() -> create($thumb_file, array(
                        'parent_type' => $video -> getType(),
                        'parent_id' => $video -> getIdentity()
                    ));

                    // Remove temp file
                    @unlink($thumb_file);
                    @unlink($tmp_file);
                }
                catch( Exception $e )
                {

                }
                $information = $this -> handleInformation($video -> type, $video -> code);

                $video -> duration = $information['duration'];
                if (!$video -> description)
                {
                    $video -> description = $information['description'];
                }
                $video -> photo_id = $thumbFileRow -> file_id;
                $video -> status = 1;

                // Add tags
                if (isset($values['tags']))
                {
                    $tags = preg_split('/[,]+/', $values['tags']);
                    $video->tags()->addTagMaps($viewer, $tags);
                }

                $video -> save();




                // Insert new action item
                $insert_action = true;
            }

            if ($valid)
            {
                $video -> status = 1;
                $video -> save();
                $insert_action = true;
            }

            // CREATE AUTH STUFF HERE
            $auth = Engine_Api::_() -> authorization() -> context;
            $roles = array(
                'owner',
                'owner_member',
                'owner_member_member',
                'owner_network',
                'registered',
                'everyone'
            );
            if (isset($values['auth_view']))
                $auth_view = $values['auth_view'];
            else
                $auth_view = "everyone";
            $viewMax = array_search($auth_view, $roles);
            foreach ($roles as $i => $role)
            {
                $auth -> setAllowed($video, $role, 'view', ($i <= $viewMax));
            }
            if (isset($values['auth_comment']))
                $auth_comment = $values['auth_comment'];
            else
                $auth_comment = "everyone";
            $commentMax = array_search($auth_comment, $roles);
            foreach ($roles as $i => $role)
            {
                $auth -> setAllowed($video, $role, 'comment', ($i <= $commentMax));
            }


            if ($insert_action){
                $owner = $video -> getOwner();

                $activityType = 'video_new';

                if($this->getWorkingModule('video') == 'ynvideo'){
                    // $activityType = 'ynvideo_new';
                }

                if($isYNListingItem){
                    $activityType =  'ynlistings_video_create';
                    $listing  =  Engine_Api::_()->getItem('ynlistings_listing', $iListingId);
                    $action = Engine_Api::_() -> getDbtable('actions', 'activity') -> addActivity($owner, $listing, $activityType);
                }else{
                    $action = Engine_Api::_() -> getDbtable('actions', 'activity') -> addActivity($owner, $video, $activityType);
                }

                if ($action != null)
                {
                    Engine_Api::_() -> getDbtable('actions', 'activity') -> attachActivity($action, $video);
                }
            }

            // Rebuild privacy
            $actionTable = Engine_Api::_() -> getDbtable('actions', 'activity');

            foreach ($actionTable->getActionsByObject($video) as $action)
            {
                $actionTable -> resetActivityBindings($action);
            }

            if($isYNListingItem){
                if(isset($aData['video_type']) && $aData['video_type'] ){
                    $video_type = $aData['video_type'];
                }
                else {
                    $video_type = 'video';
                }

                $table = Engine_Api::_() -> getDbTable('mappings', 'ynlistings');
                $row = $table -> createRow();

                $row -> setFromArray(array(
                    'listing_id' => $iListingId,
                    'item_id' => $video->getIdentity(),
                    'user_id' => $video -> owner_id,
                    'type' => $video_type,
                    'creation_date' => date('Y-m-d H:i:s'),
                    'modified_date' => date('Y-m-d H:i:s'),
                ));
                $row -> save();
            }
            $db -> commit();

            return array(
                'message'=>Zend_Registry::get('Zend_Translate') -> _("Video successfully created."),
                'iVideoId' => $video -> getIdentity(),
                'iVideoTitle' => $video -> getTitle(),
                'action'=>$action?$action->toArray():null,
            );
        }

        catch( Exception $e )
        {
            $db -> rollBack();
            return array(
                'error_code' => 1,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _('Upload failed by database query')
            );
        }
    }

    /** input data:
     * + parent_type: string, optional.
     * + parent_id: int, optional.
     * + category_id: string, optional.
     * + title: string, required.
     * + description: string, optional.
     * + search: int, optional.
     * + auth_view: string, optional.
     * + auth_comment: string, optional.
     * + video: file, required
     *
     * output data:
     * + result: 1 if success and 0 otherwise.
     * + error_code: 1 if error, and 0 otherwise.
     * + message: Message to show the bug.
     * + iVideoId: Video id.
     * + sVideoTitle: Title of video.
     *
     * @see Mobile - API SE/Api V3.0
     * @see video/upload
     * @param array $aData
     * @return array
     *
     */
    public function upload($aData)
    {
        // var postData = {
        // parent_id: $scope.statusData.iSubjectId,
        // parent_type: $scope.statusData.sSubjectType,
        // status_text: $scope.statusData.sContent,
        // title: gettext('Untitled')
        // };

        $viewer = Engine_Api::_() -> user() -> getViewer();

        $values['user_id'] = $viewer -> getIdentity();

        $paginator = $this->getWorkingApi('core','video')
            -> getVideosPaginator($values);

        $quota = Engine_Api::_() -> authorization()
            -> getPermission($viewer -> level_id, 'video', 'max');

        $current_count = $paginator -> getTotalItemCount();

        if (($current_count >= $quota) && !empty($quota)){
            return array(
                'error_code' => 1,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _("You have already uploaded the maximum number of videos allowed. If you would like to upload a new video, please delete an old one first."),
                'result' => 0
            );
        }

        $iListingId =  0;
        $isYNListingItem =  false;
        if($aData['parent_type'] == 'ynlistings_listing'){
            $isYNListingItem =  true;
            $iListingId =  $aData['parent_id'];
            $aData['parent_type'] = 'user';
            $aData['parent_id'] =  $viewer->getIdentity();
        }


        if( empty($aData['video']) && !isset($_FILES['video'])){
            return array(
                'error_code' => 2,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _("Upload failed, file size is too large!"),
                'result' => 0
            );
        }

        $illegal_extensions = array(
            'php',
            'pl',
            'cgi',
            'html',
            'htm',
            'txt'
        );
        if (in_array(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION), $illegal_extensions))
        {
            return array(
                'error_code' => 3,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _('Invalid Upload'),
                'result' => 0
            );
        }

        $parent_type = 'user';
        $parent_id = $viewer -> getIdentity();
        $type = 3;

        if (!empty($aData['parent_type']))
        {
            $parent_type = $aData['parent_type'];
        }

        if (!empty($aData['parent_id']))
        {
            $parent_id = $aData['parent_id'];
        }

        $table = $this->getWorkingTable('videos','video');

        $db = $table -> getAdapter();
        $db -> beginTransaction();

        try
        {
            $values['default_video_module'] = true;
            $params = array(
                'owner_type' => 'user',
                'owner_id' => $viewer -> getIdentity()
            );

            //fix issues.
            // $params  =  array_merge(array('code'=>''), $params);

            $video = Engine_Api::_() -> ynmobile()
                -> createVideo($params, $_FILES['video'], $isYNListingItem?0:1);

            // sets up title and owner_id now just incase members switch page as soon as upload is completed
            $video -> title = $_FILES['video']['name'];
            $video -> owner_id = $viewer -> getIdentity();
            $video -> type = 3;
            $video -> parent_type = $parent_type;
            $video -> parent_id = $parent_id;

            if (!empty($aData['title']))
            {
                $video -> title = $aData['title'];
            }
            if (!empty($aData['description']))
            {
                $video -> description = $aData['description'];
            }
            if (!empty($aData['search']))
            {
                $video -> search = $aData['search'];
            }
            if (!empty($aData['category_id']))
            {
                $video -> category_id = $aData['category_id'];
            }

            // Add tags
            if (isset($aData['sTags']))
            {
                $tags = preg_split('/[,]+/', $aData['sTags']);
                $video->tags()->addTagMaps($viewer, $tags);
            }

            if (isset($aData['status_text']))
            {
                $video->status_text = $aData['status_text'];
            }

            $video -> save();
            // CREATE AUTH STUFF HERE
            $auth = Engine_Api::_() -> authorization() -> context;
            $roles = array(
                'owner',
                'owner_member',
                'owner_member_member',
                'owner_network',
                'registered',
                'everyone'
            );
            if (isset($aData['auth_view']))
                $auth_view = $aData['auth_view'];
            else
                $auth_view = "everyone";
            $viewMax = array_search($auth_view, $roles);
            foreach ($roles as $i => $role)
            {
                $auth -> setAllowed($video, $role, 'view', ($i <= $viewMax));
            }
            if (isset($aData['auth_comment']))
                $auth_comment = $aData['auth_comment'];
            else
                $auth_comment = "everyone";

            $commentMax = array_search($auth_comment, $roles);

            foreach ($roles as $i => $role)
            {
                $auth -> setAllowed($video, $role, 'comment', ($i <= $commentMax));
            }

            $insert_action = true;

            if ($insert_action){
                $owner = $video -> getOwner();

                if($parent_type == 'group' || $parent_type == 'advgroup'){
                    $activityType =  'advgroup_video_create';
                }else{
                    $activityType =  'video_new';
                }

                if($isYNListingItem){
                    $activityType =  'ynlistings_video_create';
                    $listing  =  Engine_Api::_()->getItem('ynlistings_listing', $iListingId);
                    $action = Engine_Api::_() -> getDbtable('actions', 'activity') -> addActivity($owner, $listing, $activityType);
                }else{
                    $action = Engine_Api::_() -> getDbtable('actions', 'activity') -> addActivity($owner, $video, $activityType);
                }

                if ($action != null)
                {
                    Engine_Api::_() -> getDbtable('actions', 'activity') -> attachActivity($action, $video);
                }
            }

            // Rebuild privacy
            $actionTable = Engine_Api::_() -> getDbtable('actions', 'activity');

            foreach ($actionTable->getActionsByObject($video) as $action)
            {
                $actionTable -> clearActivityBindings($action);
            }

            if($isYNListingItem){
                if(isset($aData['video_type']) && $aData['video_type'] ){
                    $video_type = $aData['video_type'];
                }
                else {
                    $video_type = 'video';
                }

                $table = Engine_Api::_() -> getDbTable('mappings', 'ynlistings');
                $row = $table -> createRow();

                $row -> setFromArray(array(
                    'listing_id' => $iListingId,
                    'item_id' => $video->getIdentity(),
                    'user_id' => $video -> owner_id,
                    'type' => $video_type,
                    'creation_date' => date('Y-m-d H:i:s'),
                    'modified_date' => date('Y-m-d H:i:s'),
                ));
                $row -> save();
            }

            $db -> commit();

            return array(
                'result' => 1,
                'message' => Zend_Registry::get('Zend_Translate') -> _("Video successfully created."),
                'iVideoId' => $video -> getIdentity(),
                'iVideoTitle' => $video -> getTitle(),
                'action'=>$action?$action->toArray():null,
            );
        }catch( Exception $e ){
            $db -> rollBack();
            return array(
                'error_code' => 4,
                'error_message' => $e->getMessage()
            );

        }
    }

    /** input data:
     * + iVideoId: int, required.
     * + category_id: string, optional.
     * + title: string, required.
     * + description: string, optional.
     * + search: int, optional.
     * + auth_view: string, optional.
     * + auth_comment: string, optional.
     *
     * output data:
     * + result: 1 if success and 0 otherwise.
     * + error_code: 1 if error, and 0 otherwise.
     * + message: Message to show the bug.
     * + iVideoId: Video id.
     * + sVideoTitle: Title of video.
     *
     * @see Mobile - API SE/Api V3.0
     * @see video/edit
     *
     * @param array $aData
     * @return array
     *
     */
    public function edit($aData)
    {
        extract($aData);

        $iVideoId = intval($iVideoId);

        $video = $this -> getWorkingItem('video', $iVideoId);
        $viewer = Engine_Api::_() -> user() -> getViewer();

        $translator = Zend_Registry::get('Zend_Translate');

        if (!$video){
            return array(
                'error_code' => 1,
                'error_message' => $translator -> _("Video doesn't exists!")
            );
        }

        if (!Engine_Api::_() -> authorization() -> isAllowed($video, null, 'edit')){
            return array(
                'error_code' => 1,
                'error_message' => $translator-> _("You don't have permission to edit this video!"),
                'result' => 0
            );
        }

        if (!isset($aData['title']) || trim($aData['title']) == "")
        {
            return array(
                'error_code' => 1,
                'error_message' => $translator -> _("Title is empty!")
            );
        }
        // Process
        $table  = $this->getWorkingTable('videos','video');
        $db = $table -> getAdapter();

        $db -> beginTransaction();

        try
        {
            $values = $aData;
            $video -> setFromArray($values);

            // Add tags
            $tags = preg_split('/[,]+/', $tags);
            $video->tags()->setTagMaps($viewer, $tags);

            $video -> save();

            // CREATE AUTH STUFF HERE
            $auth = Engine_Api::_() -> authorization() -> context;
            $roles = array(
                'owner',
                'owner_member',
                'owner_member_member',
                'owner_network',
                'registered',
                'everyone'
            );
            if (isset($values['auth_view']))
                $auth_view = $values['auth_view'];
            else
                $auth_view = "everyone";
            $viewMax = array_search($auth_view, $roles);
            foreach ($roles as $i => $role)
            {
                $auth -> setAllowed($video, $role, 'view', ($i <= $viewMax));
            }

            if (isset($values['auth_comment']))
                $auth_comment = $values['auth_comment'];
            else
                $auth_comment = "everyone";
            $commentMax = array_search($auth_comment, $roles);
            foreach ($roles as $i => $role)
            {
                $auth -> setAllowed($video, $role, 'comment', ($i <= $commentMax));
            }

            // Rebuild privacy
            $actionTable = Engine_Api::_() -> getDbtable('actions', 'activity');
            foreach ($actionTable->getActionsByObject($video) as $action)
            {
                $actionTable -> resetActivityBindings($action);
            }
            $db -> commit();
            return array(
                'result' => 1,
                'message' => Zend_Registry::get('Zend_Translate') -> _("Video successfully edited."),
                'iVideoId' => $video -> getIdentity(),
                'iVideoTitle' => $video -> getTitle()
            );
        }
        catch( Exception $e )
        {
            $db -> rollBack();
            return array(
                'error_code' => 1,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _('An error occurred.')
            );
        }
    }

    public function delete_videos($aData){
        extract($aData);

        $videoTable =$this->getWorkingTable('videos','video');
        $viewer = Engine_Api::_() -> user() -> getViewer();

        $deleteIds =  array();

        foreach($aVideos as $iVideoId){
            $video =  $videoTable->fetchRow(array(
                'video_id=?'=> (int)$iVideoId,
            ));

            if (!$video || ! Engine_Api::_() -> authorization() -> isAllowed($video, null, 'delete')){
                continue;
            }

            $deleteIds[] =  $iVideoId;

            $this -> getWorkingApi('core', 'video') -> deleteVideo($video);
        }

        // delete all mapping api

        if($deleteIds){
            $table =  Engine_Api::_()->getDbTable('mappings','ynlistings');
            $select =  $table->select()->where('item_id in (?)', $deleteIds);

            foreach($table->fetchAll($select) as $mapping){
                $mapping->delete();
            }
        }

        return array(
            'result' => 1,
            'message'=>Zend_Registry::get('Zend_Translate') -> _("Video has been deleted."),
        );

    }

    /** input data:
     * + iVideoId: int, required.
     *
     * output data:
     * + result: 1 if success and 0 otherwise.
     * + error_code: 1 if error, and 0 otherwise.
     * + error_message: Message to show the bug.
     *
     * @see Mobile - API SE/Api V3.0
     * @see video/delete
     *
     * @param array $aData
     * @return array
     *
     */
    public function delete($aData)
    {

        extract($aData);
        $iVideoId = intval($iVideoId);


        $videoTable =$this->getWorkingTable('videos','video');
        $video =  $videoTable->findRow($iVideoId);

        $viewer = Engine_Api::_() -> user() -> getViewer();

        if (!$video)
        {
            return array(
                'error_code' => 1,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _("Video doesn't exists!")
            );
        }

        if (!Engine_Api::_() -> authorization() -> isAllowed($video, null, 'delete')){
            return array(
                'error_code' => 1,
                'error_message' => Zend_Registry::get('Zend_Translate') -> _("You don't have permission to delete this video!"),
                'result' => 0
            );
        }
        // Process
        $db = $videoTable-> getAdapter();

        $db -> beginTransaction();
        try
        {
            $this -> getWorkingApi('core', 'video') -> deleteVideo($video);

            $db -> commit();
            return array(
                'result' => 1,
                'message'=>Zend_Registry::get('Zend_Translate') -> _("Video has been deleted."),
            );
        }
        catch( Exception $e )
        {
            $db -> rollBack();
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("You can not delete this video"),
                'error_debug'=> $e->getMessage(),
            );
        }
    }

    private function extractCode($url, $type)
    {
        switch ($type)
        {
            //youtube
            case "1" :
                // change new youtube URL to old one
                $new_code = @pathinfo($url);
                $url = preg_replace("/#!/", "?", $url);

                // get v variable from the url
                $arr = array();
                $arr = @parse_url($url);
                $code = "code";
                $parameters = $arr["query"];
                parse_str($parameters, $data);
                $code = $data['v'];
                if ($code == "")
                {
                    $code = $new_code['basename'];
                }

                return $code;
            //vimeo
            case "2" :
                // get the first variable after slash
                $code = @pathinfo($url);
                return $code['basename'];
        }
    }

    // YouTube Functions
    private function checkYouTube($code)
    {
        $api_key = Engine_Api::_()->getApi('settings', 'core')->getSetting('ynvideo.youtube.apikey', 'AIzaSyDpUPT_nafV_MFSAlc-8AH4e1Gy578iK0M');
        $url = "https://www.googleapis.com/youtube/v3/videos?id=$code&key=$api_key&part=snippet,contentDetails";
        $data = @file_get_contents($url);
        $data = json_decode($data);
        if (empty($data->items)) {
            return false;
        } else {
            $jsonData = $data->items[0];
            return $jsonData;
        }
    }

    // Vimeo Functions
    private function checkVimeo($code)
    {
        //http://www.vimeo.com/api/docs/simple-api
        //http://vimeo.com/api/v2/video
        $data = @simplexml_load_file("http://vimeo.com/api/v2/video/" . $code . ".xml");
        $id = count($data -> video -> id);
        if ($id == 0)
            return false;
        return true;
    }

    // handles thumbnails
    private function handleThumbnail($type, $code = null)
    {
        switch ($type)
        {
            //youtube
            case "1" :
                //http://img.youtube.com/vi/Y75eFjjgAEc/default.jpg
                return "http://img.youtube.com/vi/$code/default.jpg";
            //vimeo
            case "2" :
                //thumbnail_medium
                $data = simplexml_load_file("http://vimeo.com/api/v2/video/" . $code . ".xml");
                $thumbnail = $data -> video -> thumbnail_medium;
                return $thumbnail;
        }
    }

    // retrieves infromation and returns title + desc
    private function handleInformation($type, $code)
    {
        switch ($type)
        {
            //youtube
            case "1" :
                $information = array();
                $data = $this->checkYouTube($code);
                $information['title'] = $data->snippet->title;
                $information['description'] = $data->snippet->description;
                $start = new DateTime('@0'); // Unix epoch
                $start->add(new DateInterval($data->contentDetails->duration));
                $duration = $start->format('H')*60*60 + $start->format('i')*60 + $start->format('s');
                $information['duration'] = sprintf("%s", $duration);
                return $information;
            //vimeo
            case "2" :
                //thumbnail_medium
                $data = simplexml_load_file("http://vimeo.com/api/v2/video/" . $code . ".xml");
                $thumbnail = $data -> video -> thumbnail_medium;
                $information = array();
                $information['title'] = $data -> video -> title;
                $information['description'] = $data -> video -> description;
                $information['duration'] = $data -> video -> duration;
                //http://img.youtube.com/vi/Y75eFjjgAEc/default.jpg
                return $information;
        }
    }

    private function getRichContent($video, $view = false)
    {
        // if video type is youtube
        if ($video -> type == 1)
        {
            $videoEmbedded = '
		        <iframe
		         title="YouTube video player"
		        id="videoFrame' . $video -> video_id . '"
		        class="youtube_iframe_big"' . 'width="640"
		        height="360"
		        src="http://www.youtube.com/embed/' . $video -> code . '?showinfo=0&wmode=opaque"
		        frameborder="0"
		        allowfullscreen=""
		        scrolling="no">
		        </iframe>';

        }
        // if video type is vimeo
        if ($video -> type == 2)
        {
            $videoEmbedded = '<iframe
		        title="Vimeo video player"
		        id="videoFrame' . $video -> video_id . '"
		        class="vimeo_iframe_big"' . 'width="640"
		        height="360"
		        src="http://player.vimeo.com/video/' . $video -> code . '?title=0&amp;byline=0&amp;portrait=0&amp;wmode=opaque&amp;badge=0"
		        frameborder="0" webkitallowfullscreen="" mozallowfullscreen="" allowfullscreen="" scrolling="no">
		        </iframe>';
        }

        // if video type is uploaded
        if ($video -> type == 3)
        {
            $videoEmbedded = "";
        }

        if($video->type == 4){
            $videoEmbedded = '<iframe frameborder="0"
								width="640" height="360"
								src="//www.dailymotion.com/embed/video/ '. $video -> code .'"'.
                '></iframe>';

        }
        return $videoEmbedded;
    }

    public function getVideoTypes()
    {
        return array(
            '1' => 'youtube',
            '2' => 'vimeo',
            '3' => 'uploaded',
            '4' => 'dailymotion'
        );
    }
    public function rate($aData)
    {

        extract($aData);

        $iVideoId = intval($iVideoId);

        $iRating =  $iRating?intval($iRating): 3;

        $viewer = Engine_Api::_() -> user() -> getViewer();

        $video =  $this->getWorkingTable('videos','video')->findRow($iVideoId);


        if (!is_object($video)){
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("Video does not exists!"),
            );
        }

        $bCanView = Engine_Api::_() -> authorization() -> isAllowed($video, null, 'view');

        if (!($bCanView)){
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("You don't have permission to rate this video!"),
            );
        }

        $videoCoreApi =  $this->getWorkingApi('core','video');

        $ratedAlready = $videoCoreApi->checkRated($iVideoId, $viewer->getIdentity());

        if ($ratedAlready){
            return array(
                'error_code' => 1,
                'error_message'=>Zend_Registry::get('Zend_Translate') -> _("You rated already!"),
            );
        }

        //process for rating a video
        $ratingTable = $this->getWorkingTable('ratings', 'video');

        $rName = $ratingTable->info('name');
        $select = $ratingTable->select()

            ->from($rName)
            ->where($rName . '.video_id = ?', $iVideoId)
            ->where($rName . '.user_id = ?', $viewer->getIdentity())
            ->limit(1);

        $result = $ratingTable->fetchAll($select);
        if (!count($result)) {
            // create rating
            $ratingTable->insert(array(
                'video_id' => $iVideoId,
                'user_id' => $viewer->getIdentity(),
                'rating' => $iRating,
            ));
        }

        //save rating to video table
        $video -> rating = $videoCoreApi -> getRating($iVideoId);
        $video -> save();
        $total = $videoCoreApi -> ratingCount($iVideoId);

        return array(
            'error_code' => 0,
            'iTotal' => $total,
            'fRating' => $video -> rating,
        );

    }

    function test(){
        return array(
            'module'=> $this->getWorkingModule('video')
        );
    }
}

