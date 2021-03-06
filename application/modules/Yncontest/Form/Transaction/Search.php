<?php
class Yncontest_Form_Transaction_Search extends Engine_Form {

  public function init() {
    $this->setAttribs(array(
                'id' => 'filter_form',
                'method'=>'GET',
                'class'=>'global_form_box',
            ));
	
	// search by title
    //$title = new Zend_Form_Element_Text('title');
	
	$this->addElement('text','contest_name', array(
		'label'=>'Contest Name',
		'decorators'=>array(
			//array('ViewHelper'), 
			//array('Label', array('tag'=>null,'placement'=>'PREPEND')),
			//array('HtmlTag', array('tag'=>'div'))
		)
	));

		
	$this->addElement('text','from', array(
		'label'=>'From',
		'decorators'=>array(
			//array('ViewHelper'), 
			//array('Label', array('tag'=>null,'placement'=>'PREPEND')),
			//array('HtmlTag', array('tag'=>'div'))
		)
	));
	
	$this->addElement('text','to', array(
		'label'=>'To',
		'decorators'=>array(
			//array('ViewHelper'), 
			//array('Label', array('tag'=>null,'placement'=>'PREPEND')),
			//array('HtmlTag', array('tag'=>'div'))
		)
	));
	
    //$submit = new Zend_Form_Element_Button('search', array('type' => 'submit','name'=>'checksub'));
    //$submit
           // ->setLabel('Search')
            //->clearDecorators()
           // ->addDecorator('ViewHelper')
          //  ->addDecorator('HtmlTag', array('tag' => 'div', 'class' => 'buttons'))
          //  ->addDecorator('HtmlTag2', array('tag' => 'div'));
	$this->addElement('Button', 'btnsubmit', array(
      'label' => 'Search',
      'type' => 'submit',
      'decorators' => array(
        'ViewHelper',
      ),
    ));

    // DisplayGroup: buttons
    $this->addDisplayGroup(array(
      'btnsubmit',
      'cancel',
    ), 'buttons', array(
      'decorators' => array(
        'FormElements',
        'DivDivDivWrapper'
      ),
    ));
    // Element: order
    $this->addElement('Hidden', 'order', array(
      'order' => 10004,
    ));

    // Element: direction
    $this->addElement('Hidden', 'direction', array(
      'order' => 10005,
    ));
	
	//$this->addElement('Hidden', 'page', array(
    //  'order' => 100
   // ));
	
    $this->addElements(array(
        $submit
    ));

  }

}