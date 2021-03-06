<?php if ($this->error) : ?>
<div class="tip">
    <span><?php echo $this->message?></span>
</div>
<?php else: ?>
<form method="post" class="global_form_popup">
    <div>
      <h3><?php echo $this->translate("Deny Listing?") ?></h3>
      <p>
        <?php echo $this->translate("Are you sure that you want to deny this Listing?") ?>
      </p>
      <br />
      <p>
        <input type="hidden" name="confirm" value="<?php echo $this->listing_id?>"/>
        <button type='submit'><?php echo $this->translate("Deny") ?></button>
        <?php echo $this->translate(" or ") ?> 
        <a href='javascript:void(0);' onclick='javascript:parent.Smoothbox.close()'>
        <?php echo $this->translate("cancel") ?></a>
      </p>
    </div>
  </form>

<?php if( @$this->closeSmoothbox ): ?>
<script type="text/javascript">
  TB_close();
</script>
<?php endif; ?>
<?php endif; ?>