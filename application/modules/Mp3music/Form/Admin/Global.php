<?php
class Mp3music_Form_Admin_Global extends Engine_Form
{
  public function init()
  {
    $this
      ->setTitle('Global Settings')
      ->setDescription('These settings affect all members in your community.');

    $this->addElement('Radio', 'mp3music_public', array(
      'label' => 'Public Permissions',
      'description' => 'MUSIC_FORM_ADMIN_GLOBAL_MUSICPUBLIC_DESCRIPTION',
      'multiOptions' => array(
        1 => 'Yes, the public can view albums unless they are made private.',
        0 => 'No, the public cannot view albums.'
      ),
      'value' => 1,
    ));
    $this->addElement('Radio', 'mp3music_artist', array(
      'label' => 'Owner Of Album Is Artist',
      'description' => 'The album owner to be the artist of albums.',
      'multiOptions' => array(
        1 => 'Yes',
        0 => 'No'
      ),
      'value' => Engine_Api::_()->getApi('settings', 'core')->getSetting('mp3music.artist', 1),
    ));
    $this->addElement('Text', 'songsPerPage', array(
      'label' => 'Songs Per Page',
      'description' => 'How many Songs will be shown per page? (Enter a number between 1 and 999)',
      'value' => Engine_Api::_()->getApi('settings', 'core')->getSetting('mp3music.songsPerPage', 10),
      'validators' => array(
						new Zend_Validate_Between(1,999)
				)
    ));
	
	$this->addElement('Text', 'timePreviewSong', array(
      'label' => 'Preview Song Time (%)',
      'description' => 'How many percentage will be play preview per song? (Enter a number between 1 and 100)',
      'value' => Engine_Api::_()->getApi('settings', 'core')->getSetting('mp3music.timePreviewSong', 30),
      'validators' => array(
						new Zend_Validate_Between(1,100)
				)
    ));


    // Add submit button
    $this->addElement('Button', 'submit', array(
      'label' => 'Save Changes',
      'type' => 'submit',
      'ignore' => true
    ));
  }

  public function saveValues()
  {
    $values = $this->getValues();
    if (!is_numeric($values['songsPerPage'])
           || 0  >= $values['songsPerPage']
           || 999 < $values['songsPerPage'])
      $values['songsPerPage'] = 10;
   
    Engine_Api::_()->getApi('settings', 'core')->setSetting('mp3music.songsPerPage', $values['songsPerPage']);
    Engine_Api::_()->getApi('settings', 'core')->setSetting('mp3music.artist', $values['mp3music_artist']);
	Engine_Api::_()->getApi('settings', 'core')->setSetting('mp3music.timePreviewSong', $values['timePreviewSong']);

    $auth = Engine_Api::_()->getApi('core', 'authorization')->getAdapter('levels');
    $auth->setAllowed('mp3music_album', Engine_Api::_()->getDbtable('levels', 'authorization')->getPublicLevel()->level_id, 'view', $values['mp3music_public']);
  }
}
