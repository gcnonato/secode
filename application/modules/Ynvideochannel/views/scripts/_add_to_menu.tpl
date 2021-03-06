<?php if($this -> viewer() -> getIdentity()):?>
    <?php
        $favoriteTable = Engine_Api::_() -> getDbTable('favorites', 'ynvideochannel');
        $addedFavorite = $favoriteTable->isAdded($this->video->getIdentity(), $this->viewer()->getIdentity());
    ?>
    <div class="ynvideochannel-action-add-playlist show-hide-action action-container">
        <a class="ynvideochannel-action-link show-hide-btn" href="javascript:void(0)" title="<?php echo $this->translate('Add to playlist')?>">
            <i class="fa fa-plus"></i>
        </a>
        <div class="ynvideochannel-action-pop-up" style="display: none">
            <div class="add-to-playlist-notices"></div>
            <div class="video-action-add-playlist">
                <div class="video-action-favorite" onclick="ynvideochannelAddToFavorite(this, '<?php echo $this->video -> getIdentity() ?>', '<?php echo $this->url(array('action' => 'favorite', 'video_id' => ''), 'ynvideochannel_video', true);?>', '<?php echo $this->translate('Favorite') ?>', '<?php echo $this->translate('Un-favorite') ?>');">
                    <?php if ($addedFavorite): ?>
                    <i class="fa fa-star"></i>
                    <?php echo $this->translate('Un-favorites') ?>
                    <?php else: ?>
                    <i class="fa fa-star-o"></i>
                    <?php echo $this->translate('Favorites') ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="video-action-add-playlist dropdow-action-add-playlist">
                <span><?php echo $this-> translate('add to') ?></span>
                <?php $url = $this->url(array('action'=>'render-playlist-list', 'video_id'=>$this->video->getIdentity()),'ynvideochannel_video', true)?>
                <div rel="<?php echo $url;?>" class="ynvideochannel-loading add-to-playlist-loading" style="display: none;text-align: center">
                    <span class="ajax-loading">
                        <img src='application/modules/Ynvideochannel/externals/images/loading.gif'/>
                    </span>
                </div>
                <div class="box-checkbox">
                    <?php echo $this->partial('_add_exist_playlist.tpl', 'ynvideochannel', array('item' => $this->video)); ?>
                </div>
            </div>

            <?php if(Zend_Controller_Action_HelperBroker::getStaticHelper('requireAuth')->setAuthParams('ynvideochannel_playlist', null, 'create')->checkRequire()):?>
                <div class="video-action-dropdown ynvideochannel-action-dropdown">
                    <a href="javascript:void(0);" onclick="ynvideochannelAddNewPlaylist(this, '<?php echo $this->video->getGuid()?>', '<?php echo $this->url(array('action' => 'get-playlist-form'), 'ynvideochannel_general', true);?>');" class="ynvideochannel-action-link add-to-playlist" data="<?php echo $this->video->getGuid()?>"><i class="fa fa-plus"></i><span><?php echo $this->translate('Add to new playlist')?></span></a>
                    <span class="play_list_span"></span>
                </div>
            <?php endif;?>
        </div>
    </div>
<?php endif;?>