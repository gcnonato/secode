<?php

/**
 * SocialEngine
 *
 * @category   Application_Extensions
 * @package    Sitemobile
 * @copyright  Copyright 2012-2013 BigStep Technologies Pvt. Ltd.
 * @license    http://www.socialengineaddons.com/license/
 * @version    $Id: dropdown.tpl 6590 2013-06-03 00:00:00Z SocialEngineAddOns $
 * @author     SocialEngineAddOns
 */
?>

<?php if ($this->pageCount): ?>
<select id="paginationControl" size="1">
<?php foreach ($this->pagesInRange as $page): ?>
  <?php $selected = ($page == $this->current) ? ' selected="selected"' : ''; ?>
  <option value="<?php echo $this->url(array('page' => $page));?>"<?php echo $selected ?>>
    <?php echo $page; ?>
  </option>
<?php endforeach; ?>
</select>
<?php endif; ?>

<script type="text/javascript">
$('paginationControl').addEvent('change', function() {
    window.location = this.options[this.selectedIndex].value;
});
</script>