<?php
class Yncontest_Widget_SearchContestController extends Engine_Content_Widget_Abstract
{
    public function indexAction()
    {
       $this->view->form = $form = new Yncontest_Form_SearchContest();
	   $requests = Zend_Controller_Front::getInstance()->getRequest()->getParams();
	   $form->isValid($requests);
	   $values = $form->getValues();
	   
	   
       $this->view->formValues = array_filter($values);
    }

}
