<?php
class Yncomment_Form_Admin_ActivityEdit extends Engine_Form {

    public function init() {
        $this
                ->setTitle('Advanced Activity Settings')
                ->setDescription("Use the form below to manage advanced commenting features for advanced activity feeds widget.");
        
        $this->addElement('Radio', 'enabled', array(
            'label' => 'Enable Advanced Comment',
            'description' => 'Do you want to enable Advanced Comments on Advanced Activity feeds?',
            'multiOptions' => array(
                1 => 'Yes',
                0 => 'No'
            ),
            'value' => 1,
        ));
        
        $multiOptions = array('friends' => 'Friends');
        $multiComposerOptions = array('addSmilies' => 'Emoticons', 'addLink' => 'Link');
        $include = array('group', 'advgroup', 'album', 'advalbum');
        $module_table = Engine_Api::_()->getDbTable('modules', 'core');
        $module_name = $module_table->info('name');
        $select = $module_table->select()
                ->from($module_name, array('name', 'title'))
                ->where($module_name . '.type =?', 'extra')
                ->where($module_name . '.name in(?)', $include)
                ->where($module_name . '.enabled =?', 1);

        $contentModule = $select->query()->fetchAll();
        $include[] = 'friends';
        foreach ($contentModule as $module) {
            if ($module['name'] != 'album' && $module['name'] != 'advalbum')
                $multiOptions[$module['name']] = $module['title'];
            if ($module['name'] == 'album' || $module['name'] == 'advalbum')
                $multiComposerOptions['addPhoto'] = 'Photo';
        }

        if (isset($multiOptions['group'])) {
            $multiOptions['group'] = 'Groups (SE Core)';
        }

        $this->addElement('MultiCheckbox', 'taggingContent', array(
            'description' => "Which Content Type do you want to tag in comments/replies? (‘@’ symbol is used)? ",
            'label' => 'Taggable Content Types',
            'multiOptions' => $multiOptions,
            'value' => $include
        ));

        $this->addElement('Radio', 'ynfeed_comment_show_bottom_post', array(
            'label' => 'Quick Comment Box',
            'description' => 'Do you want to post the comments by pressing “Enter” key?',
            'multiOptions' => array(
                1 => 'Yes',
                0 => 'No'
            ),
        ));

        $this->addElement('Radio', 'ynfeed_comment_like_box', array(
            'label' => 'Comments Detail Box',
            'description' => 'Do you want to hide the Comments Details box on Activity feed by default?',
            'multiOptions' => array(
                1 => 'Yes [Note : only the number of Like/Dislike/Comments & Replies are shown]',
                0 => 'No'
            ),
        ));

        $this->addElement('MultiCheckbox', 'showComposerOptions', array(
            'description' => "Which attachment type do you want to add into comments/replies?",
            'label' => 'Attachment types',
            'multiOptions' => $multiComposerOptions,
            'value' => array('addPhoto', 'addSmilies', 'addLink')
        ));

        $bothLikeandDislike = "Both Like and Dislike";
        $this->addElement('Radio', 'showAsLike', array(
            'description' => "Which option of Like/Dislike do you want to use?",
            'label' => 'Like and Dislike',
            'multiOptions' => array(
                1 => 'Like only',
                0 => $bothLikeandDislike
            ),
            'value' => 1,
            'escape' => false,
            'onclick' => 'hideOptions(this.value)',
        ));

        $this->addElement('Radio', 'showDislikeUsers', array(
            'description' => "Do you want to show the user’s name who dislike the feeds/comments/replies? [Note: this setting will work only if both Like and Dislike setting is set]",
            'label' => 'Dislike users list',
            'multiOptions' => array(
                1 => 'Yes',
                0 => 'No'
            ),
            'value' => 1
        ));

        $this->addElement('Radio', 'showLikeWithoutIcon', array(
            'description' => "How do you want to display Like, Dislike, Comment, etc… for a feed?",
            'label' => 'Like, Dislike, Comment, etc... display for a feed',
            'multiOptions' => array(
                1 => 'Text only',
                0 => 'Text with icon'
            ),
            'value' => 1
        ));

        $voteUp = "Vote up/Vote down? [Note: this setting will work only if both Like and Dislike setting is set]";
        $this->addElement('Radio', 'showLikeWithoutIconInReplies', array(
            'description' => "How do you want to display Like, Dislike for comments/replies?",
            'label' => 'Like, Dislike display for comments/replies',
            'multiOptions' => array(
                1 => 'Text only',
                2 => 'Icon only',
                0 => 'Text with icon',
                3 => $voteUp
            ),
            'escape' => false,
            'value' => 1
        ));

        $this->addElement('Button', 'submit', array(
            'label' => 'Save Settings',
            'type' => 'submit',
            'ignore' => true
        ));
    }

}