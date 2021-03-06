<?php 
$this -> headScript() 
		-> appendFile($this -> layout() -> staticBaseUrl . 'application/modules/Ynmusic/externals/scripts/music-actions.js') 
		-> appendFile($this -> layout() -> staticBaseUrl . 'application/modules/Ynmusic/externals/scripts/render-music-player.js');
?>
<div class="ynbusinesspages-profile-module-header">
	<!-- Menu Bar -->
	<div class="ynbusinesspages-profile-header-right">
		<?php if( $this->paginator->getTotalItemCount() > 0 ): ?>
			<?php echo $this->htmlLink(array(
				'route' => 'ynbusinesspages_extended',
				'controller' => 'social-music',
				'action' => 'list',
				'type' => 'song',
				'business_id' => $this->business->getIdentity(),
				'tab' => $this->identity,
			), '<i class="fa fa-list"></i>'.$this->translate('View all Social Music Songs'), array(
				'class' => 'buttonlink'
			))
			?>
		<?php endif; ?>

		<?php if ($this->canCreate):?>
			<?php echo $this->htmlLink(array(
				'route' => 'ynmusic_song',
				'action' => 'upload',
				'business_id' => $this->business->getIdentity(),
				'parent_type' => 'ynbusinesspages_business',
			), '<i class="fa fa-plus-square"></i>'.$this->translate('Create Social Music Songs'), array(
				'class' => 'buttonlink'
			))
			?>
		<?php endif; ?>
	</div>      

	<div class="ynbusinesspages-profile-header-content">
		<?php if( $this->paginator->getTotalItemCount() > 0 ): 
			$business = $this->business;?>
			<span class="ynbusinesspages-numeric"><?php echo $this->paginator->getTotalItemCount(); ?></span> 
			<?php echo $this-> translate(array("ynmusic_song_count", "Songs", $this->paginator->getTotalItemCount()), $this->paginator->getTotalItemCount());?>
		<?php endif; ?>
	</div>
</div>
<?php if( $this->paginator->getTotalItemCount() > 0 ): ?>
<div id="ynmusic_listing_song_<?php echo $this->identity;?>" class="music-listing">
	<div id="ynmusic-listing-content-<?php echo $this ->identity;?>" class="ynmusic-listing-content">
		<?php echo $this->partial('_song-listing.tpl', 'ynmusic', array('paginator' => $this->paginator, 'formValues' => $this->formValues, 'paging' => false, 'ajaxPaging' => true, 'business_id' => $this->subject()->getIdentity()));?>
	</div>
	
	<div class="clearfix ynmusic-navigation-button">
	    <div id="ynmusic_songs_previous_<?php echo $this->identity;?>" class="paginator_previous">
	    	<?php echo $this->htmlLink('javascript:void(0);', $this->translate('Previous'), array(
	    	  'onclick' => '',
	    	  'class' => 'buttonlink icon_previous'
	    	)); ?>
	    </div>
	    <div id="ynmusic_songs_next_<?php echo $this->identity;?>" class="paginator_next">
	    	<?php echo $this->htmlLink('javascript:void(0);', $this->translate('Next'), array(
	    	  'onclick' => '',
	    	  'class' => 'buttonlink_right icon_next'
	    	)); ?>
	    </div>
  	</div>
</div>

<script type="text/javascript">
en4.core.language.addData({'Like': ' <?php echo $this->translate('Like')?>'});
en4.core.language.addData({'Unlike': ' <?php echo $this->translate('Unlike')?>'});

function addNewPlaylist(ele, guid) {
	var nextEle = ele.getNext();
	if(nextEle.hasClass("ynmusic_active_add_playlist")) {
		//click to close
		nextEle.removeClass("ynmusic_active_add_playlist");
		nextEle.setStyle("display", "none");
	} else {
		//click to open
		nextEle.addClass("ynmusic_active_add_playlist");
		nextEle.setStyle("display", "block");
	}
	$$('.play_list_span').each(function(el){
		if(el === nextEle){
			//do not empty the current box
		} else {
			el.empty();
			el.setStyle("display", "none");
			el.removeClass("ynmusic_active_add_playlist");
		}
	});
	var data = guid;
	var url = '<?php echo $this->url(array('action' => 'get-playlist-form'), 'ynmusic_playlist', true);?>';
	var request = new Request.HTML({
        url : url,
        data : {
        	subject: data,
        },
        onSuccess: function(responseTree, responseElements, responseHTML, responseJavaScript) {
            var spanEle = nextEle;
            spanEle.innerHTML = responseHTML;
            eval(responseJavaScript);
            
            var popup = spanEle.getParent('.action-pop-up');
            var layout_parent = popup.getParent('.layout_middle');
	    	if (!layout_parent) layout_parent = popup.getParent('#global_content');
	    	var y_position = popup.getPosition(layout_parent).y;
			var p_height = layout_parent.getHeight();
			var c_height = popup.getHeight();
    		if(p_height - y_position < (c_height + 1)) {
    			layout_parent.addClass('popup-padding-bottom');
    			var margin_bottom = parseInt(layout_parent.getStyle('padding-bottom').replace( /\D+/g, ''));
    			layout_parent.setStyle('padding-bottom', (margin_bottom + c_height + 1 + y_position - p_height)+'px');
			}
        }
    });
    request.send();
}

function addToPlaylist(ele, playlistId, guild) {
	var checked = ele.get('checked');
	var data = guild;
	var url = '<?php echo $this->url(array('action' => 'add-to-playlist'), 'ynmusic_playlist', true);?>';
	var request = new Request.JSON({
        url : url,
        data : {
        	subject: data,
        	playlist_id: playlistId,
        	checked: checked,
        },
        onSuccess: function(responseJSON) {
        	if (!responseJSON.status) {
        		ele.set('checked', !checked);
        	}
            var div = ele.getParent('.action-pop-up');
            var notices = div.getElement('.add-to-playlist-notices');
            var notice = new Element('div', {
            	'class' : 'add-to-playlist-notice',
            	text : responseJSON.message
            });
            notices.adopt(notice);
            notice.fade('in');
            (function() {
            	notice.fade('out').get('tween').chain(function() {
            		notice.destroy();
            	});
        	}).delay(3000, notice);
        }
    });
    request.send();
}

en4.core.runonce.add(function(){
    <?php if (!$this->renderOne): ?>
    	var smoothbox = this.Smoothbox;
        var anchor = $('ynmusic_listing_song_<?php echo $this->identity;?>').getParent();
        $('ynmusic_songs_previous_<?php echo $this->identity;?>').style.display = '<?php echo ( $this->paginator->getCurrentPageNumber() == 1 ? 'none' : '' ) ?>';
        $('ynmusic_songs_next_<?php echo $this->identity;?>').style.display = '<?php echo ( $this->paginator->count() == $this->paginator->getCurrentPageNumber() ? 'none' : '' ) ?>';

        $('ynmusic_songs_previous_<?php echo $this->identity;?>').removeEvents('click').addEvent('click', function(){
            en4.core.request.send(new Request.HTML({
                url : en4.core.baseUrl + 'widget/index/content_id/' + <?php echo sprintf('%d', $this->identity) ?>,
                data : {
                    format : 'html',
                    page : <?php echo sprintf('%d', $this->paginator->getCurrentPageNumber() - 1) ?>,
                    subject: '<?php echo $this->subject()->getGuid()?>'
                },
                onSuccess: function(responseTree, responseElements, responseHTML, responseJavaScript) {
                	var element = Elements.from(responseHTML)[0];
                	element.replaces(anchor);
	                eval(responseJavaScript);
	                smoothbox.bind();
	            }
            }))
        });

        $('ynmusic_songs_next_<?php echo $this->identity;?>').removeEvents('click').addEvent('click', function(){
            en4.core.request.send(new Request.HTML({
                url : en4.core.baseUrl + 'widget/index/content_id/' + <?php echo sprintf('%d', $this->identity) ?>,
                data : {
                    format : 'html',
                    page : <?php echo sprintf('%d', $this->paginator->getCurrentPageNumber() + 1) ?>,
                    subject: '<?php echo $this->subject()->getGuid()?>'
                },
                onSuccess: function(responseTree, responseElements, responseHTML, responseJavaScript) {
	                var element = Elements.from(responseHTML)[0];
                	element.replaces(anchor);
	                eval(responseJavaScript);
	                smoothbox.bind();
	            }
            }))
        });
    <?php endif; ?>
});

window.addEvent('domready', function(){
	if (typeof addEventForPlayBtn == 'function') { 
	  	addEventForPlayBtn(); 
	}
	if (typeof addEventsForSocialMusicPopup == 'function') { 
	  	addEventsForSocialMusicPopup(); 
	}
});
</script>
<?php else :?>
<div class="tip">
	<span>
		 <?php echo $this->translate('There are no songs.');?>
	</span>
</div>
<?php endif; ?>