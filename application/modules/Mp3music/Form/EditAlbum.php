<?php
class Mp3music_Form_EditAlbum extends Mp3music_Form_CreateAlbum
{
	public function init()
	{
		// Init form
		parent::init();
		$this -> setDescription('') -> setAttrib('id', 'form-upload-music') -> setAttrib('name', 'album_edit') -> setAttrib('enctype', 'multipart/form-data') -> setAction(Zend_Controller_Front::getInstance() -> getRouter() -> assemble(array()));
		// Pre-fill form values
		$this -> addElement('Hidden', 'album_id');
		$this -> removeElement('fancyuploadfileids');
		$this -> removeElement('music_categorie_id');
		$this -> removeElement('music_artist_id');
		$this -> removeElement('other_artist');
		// Override submit button
		$this -> removeElement('submit');
		$this -> addElement('Button', 'save', array(
			'label' => 'Save Changes',
			'type' => 'submit',
		));
	}

	public function populate($album)
	{
		if ($album -> type == 1)
		{
			$this -> setTitle('Edit Track');
			$this -> title -> setLabel("Track Name");
			$this -> price -> setLabel("Track Price");
			$this -> description -> setLabel("Track Description");
			$this -> art -> setLabel("Track Artwork");
		}
		else
		{
			$this -> setTitle('Edit Album');
			$this -> title -> setLabel("Album Name");
			$this -> price -> setLabel("Album Price");
			$this -> description -> setLabel("Album Description");
			$this -> art -> setLabel("Album Artwork");
		}
		foreach (array(
		'album_id' => $album->getIdentity(),
		'title'       => htmlspecialchars_decode($album->getTitle()),
		'description' => $album->description,
		'search'      => $album->search,
		'price'       => $album->price,
		'download'    => $album->is_download
		) as $key => $value)
		{
			$this -> getElement($key) -> setValue($value);
		}
		// If this is THE profile playlist, hide the title/desc fields
		if ($album -> composer)
		{
			$this -> removeElement('title');
			$this -> removeElement('description');
			$this -> removeElement('search');
		}
		$this -> removeElement('type');
		$this -> removeElement('music_singer_id');
		$this -> removeElement('other_singer');
		// AUTHORIZATIONS
		$auth = Engine_Api::_() -> authorization() -> context;
		$lowest_viewer = array_pop(array_keys($this -> _roles));
		foreach (array_reverse(array_keys($this->_roles)) as $role)
		{
			if ($auth -> isAllowed($album, $role, 'view'))
			{
				$lowest_viewer = $role;
			}
		}
		if ($this -> getElement('auth_view'))
		{
			$this -> getElement('auth_view') -> setValue($lowest_viewer);
		}
		$lowest_commenter = array_pop(array_keys($this -> _roles));
		foreach (array_reverse(array_keys($this->_roles)) as $role)
		{
			if ($auth -> isAllowed($album, $role, 'comment'))
			{
				$lowest_commenter = $role;
			}
		}
		if ($this -> getElement('auth_comment'))
		{
			$this -> getElement('auth_comment') -> setValue($lowest_commenter);
		}
	}

	public function saveValues()
	{
		$album = parent::saveValues();
		$values = $this -> getValues();
		$translate = Zend_Registry::get('Zend_Translate');
		if ($album && $album -> isEditable())
		{
			$album -> title = htmlspecialchars($values['title']);
			if (trim($album -> title) == '')
			{
				$album -> title = $translate -> _('_MUSIC_UNTITLED_ALBUM');
			}
			$str = $album -> title;
			$str = strtolower($str);
			$str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", "a", $str);
			$str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", "e", $str);
			$str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", "i", $str);
			$str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", "o", $str);
			$str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", "u", $str);
			$str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", "y", $str);
			$str = preg_replace("/(đ)/", "d", $str);
			$str = preg_replace("/(!|@|%|\^|\*|\(|\)|\+|\=|\<|\>|\?|\/|,|\.|\:|\;|\'| |\"|\&|\#|\[|\]|~|$|_)/", "-", $str);
			$str = preg_replace("/(-+-)/", "-", $str);
			//thay thế 2- thành 1-
			$str = preg_replace("/(^\-+|\-+$)/", "", $str);
			$str = preg_replace("/(-)/", " ", $str);
			$album -> title_url = $str;
			$album -> description = $values['description'];
			$album -> search = $values['search'];
			$user = Engine_Api::_() -> user() -> getViewer();
			$selling_settings = Mp3music_Api_Cart::getSettingsSelling($user -> level_id);
			if (isset($values['price']))
			{
				if (!is_numeric($values['price']))
				{
					$this -> getElement('price') -> addError('The price number is invalid! (Ex: 2000.25)');
					return false;
				}
				$min_price = $selling_settings['min_price_album'];
				if($album -> type == 1)
				{
					$min_price = $selling_settings['min_price_song'];
				}
				if (round($values['price'], 2) <  $min_price && round($values['price'], 2) != 0)
				{
					$this -> getElement('price') -> addError('The price is must larger than or equal to ' . $min_price . ' USD');
					return false;
				}
				if ($values['price'] == "")
				{
					$values['price'] = '0.00';
				}
				$album -> price = $values['price'];
			}
			$album -> is_download = $values['download'];
			$album -> save();
			
			$file_ids = array();
			foreach (explode(' ', $values['html5uploadfileids']) as $file_id)
			{
				$file_id = trim($file_id);
				if (!empty($file_id))
					$file_ids[] = $file_id;
			}
			// Attach songs (file_ids) to album
			if (!empty($file_ids))
			{
				foreach ($file_ids as $file_id)
				{
					$user = Engine_Api::_() -> user() -> getViewer();
					$max_songs = Engine_Api::_() -> authorization() -> getAdapter('levels') -> getAllowed('mp3music_album', $user, 'max_songs');
					if ($max_songs == "")
						$max_songs = 5;
					$song_count = count($album -> getSongs());
					if ($song_count < $max_songs)
					{
						$album -> addSong($file_id, 0, 0, 0);
					}
				}
			}
			// Rebuild privacy
			$actionTable = Engine_Api::_() -> getDbtable('actions', 'activity');
			foreach ($actionTable->getActionsByObject($album) as $action)
			{
				$actionTable -> resetActivityBindings($action);
			}
			return $album;
		}
		else
		{
			return false;
		}
	}

}