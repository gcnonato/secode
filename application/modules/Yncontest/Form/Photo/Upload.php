<?php
class Yncontest_Form_Photo_Upload extends Engine_Form
{
  public function init()
  {
    // Init form
    $this
      ->setTitle('Add New Photos')
      ->setDescription('Choose photos on your computer to add to this contest. (2MB maximum)')
      ->setAttrib('id', 'form-upload')
      ->setAttrib('class', 'global_form yncontest_form_upload')
      ->setAttrib('name', 'albums_create')
      ->setAttrib('enctype','multipart/form-data')
      ->setAction(Zend_Controller_Front::getInstance()->getRouter()->assemble(array()))
      ;

    $this -> addElement('Dummy', 'html5_upload', array('decorators' => array( array(
						'ViewScript',
						array(
							'viewScript' => '_Html5Upload.tpl',
							'class' => 'form element',
						)
			)), ));
	$this -> addElement('Hidden', 'contest_id', array('order' => 1));
	$this -> addElement('Hidden', 'html5uploadfileids', array(
		'value' => '',
		'order' => 2
	));

    // Init submit
    $this->addElement('Button', 'submit', array(
      'label' => 'Save Photos',
      'type' => 'submit',
    ));
  }
}