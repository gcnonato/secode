<?php

class Ynidea_Form_HelpPage_Admin_Edit extends Ynidea_Form_HelpPage_Admin_Create{
	
	public function init(){
		parent::init();
		$this -> setTitle('Edit Help Page') -> setDescription('');
	}
}