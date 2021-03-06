<?php echo $this->content()->renderWidget('yncontest.main-menu') ?>
<?php echo $this->form->render($this);?>

<div>
	<h3><?php echo $this->translate("List of Awards") ?></h3>
	<br/>
</div>

<?php if( count($this->paginator) ): ?>

<script type="text/javascript">
	
	}
  </script>
<table class='yncontest_admin_table admin_table'>
  <thead>
    <tr>
     
      <th><?php echo $this->translate("Name") ?></th>
      <th><?php echo $this->translate("Description") ?></th> 
      <th><?php echo $this->translate("Quantities") ?></th> 
      <th><?php echo $this->translate("Value") ?></th>
      <th><?php echo $this->translate("Actions") ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($this->paginator as $item): ?>
      <tr>
	      <td><?php echo $item->award_name; ?></td> 
	      <td><?php echo $item->description; ?></td> 
	      <td><?php echo $item->quantities; ?></td> 
	       <td>
	      <?php if($item->value != 0):?>
	      	<?php echo $item->currency.$item->value; ?>
	      <?php endif;?>
	   	  </td>	
	      <td>
	          <?php echo $this->htmlLink(
	                  array('route' => 'yncontest_myaward', 'action' => 'edit-award','contest'=>$this->contest, 'award' => $item->award_id),
	                  $this->translate('edit'),
	                  array('class' => 'smoothbox')) ?>
	          |
	          <?php echo $this->htmlLink(
	                  array('route' => 'yncontest_myaward', 'action' => 'delete-award','contest'=>$this->contest, 'award' => $item->award_id),
	                  $this->translate('delete'),
	                  array('class' => 'smoothbox')) ?>
	          
	      </td>    
        
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<br />

<div>
   <?php  echo $this->paginationControl($this->paginator, null, null, array(
      'pageAsQuery' => false,
      'query' => $this->formValues,
    ));     ?>
</div>

<br />
<form class="global_form" method="" action="<?php echo $this->url(array('action' => 'edit-contest-setting', 'contest'=>$this->contest), 'yncontest_mysetting', true)?>" enctype="multipart/form-data">
<div class='buttons'>
	<button type="submit" id="submit" name="">Continue</button>
</div>

</form>

<?php else:?>
  <div class="tip">
    <span>
      <?php echo $this->translate("Award has not created yet.") ?>
    </span>
  </div>
<?php endif; ?> 