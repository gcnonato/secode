<?php	
$this->headScript()
         ->appendFile($this->baseUrl() . '/application/modules/Mp3music/externals/scripts/music_function.js'); 
	  
?>
              <ul class="global_form_box" style="margin-bottom: 15px;">
<div class="mp3_album_widgets">
<?php 
        $playlists    = $this->playlists;
        $index = 0;
        foreach ($playlists as $playlist): 
        if($playlist->getSongIDFirst()): $index ++?>
        <li class="mp3_title_link">
             <div class="mp3_image_album" title="<?php echo $playlist->getOwner()->getTitle(); ?>">    
                 <?php echo $this->htmlLink($playlist->getOwner(),
                 $this->itemPhoto($playlist, 'thumb.normal')) ?>
             </div>
             <div class="mp3_title_album_right" >    
                 <a title="<?php echo $playlist->getTitle(); ?>" class="mp3_title_link_album" rel="balloon_playlist_<?php echo $playlist->playlist_id; ?>" href="javascript:;" onClick="return openPage('<?php echo $this->url(array('playlist_id'=>$playlist->playlist_id), 'mp3music_playlist');?>',500,890)">
                 	<?php echo $this->string()->truncate($playlist->getTitle(), 11);?></a><br/>
                 <div class="mp3_album_info_right">
                 <?php echo $this->timestamp($playlist->creation_date); ?><br/>
                 <span title="<?php echo $playlist->getOwner()->getTitle()?>">
                 <?php echo $this->translate('Author: ');?>
                 <a  href="<?php echo $playlist->getOwner()->getHref()?>" class ='title_thongtin3'>
                 	<?php echo $this->string()->truncate($playlist->getOwner()->getTitle(), 10);?></a> 
                 </span>
                 </div>
             </div>
          </li>
         <?php endif; endforeach; ?>    
           
        <?php if($index >= $this->limit): ?>
        <li class="mp3_link_more" style="padding-top: 10px;">
            <?php echo $this->htmlLink($this->url(array('search'=>'browse_playlists'), 'mp3music_browse_playlists'),
                         $this->translate('&raquo; View more'),
                        array('class'=>'')); ?>
            </li> 
        <?php endif;?>
</div>    
</ul>
<script type="text/javascript">
	window.addEvent('domready', function() {
		if(<?php echo $index?> == 0)
		{
			$$('.layout_mp3music_new_playlists').each(function(e){
				e.style.display = 'none';
			});
		}
	});
</script>