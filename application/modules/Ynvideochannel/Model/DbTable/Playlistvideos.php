<?php

/**
 * YouNet Company
 *
 * @category   Application_Extensions
 * @package    Ynvideochannel
 * @author     YouNet Company
 */
class Ynvideochannel_Model_DbTable_Playlistvideos extends Engine_Db_Table {
    protected $_name = 'ynvideochannel_playlistvideos';
    protected $_rowClass = 'Ynvideochannel_Model_Playlistvideo';

	public function getMapRow($playlist_id, $video_id) {
		$select = $this -> select()
						-> where("video_id = ?", $video_id)
						-> where("playlist_id = ?", $playlist_id)
						-> limit(1);
		return $this -> fetchRow($select);
	}

	public function getVideoIds($playlist_id) {
		$select = $this -> select()
				-> from($this->info('name'), 'video_id')
				-> where("playlist_id = ?", $playlist_id);
		$videoIds = $select->query()->fetchAll(PDO::FETCH_ASSOC, 0);
		return $videoIds;
	}

	public function updateVideosOrder($playlist_id, $order) {
		foreach ($order as $id => $video_id) {
			if ($video_id) {
				$where = array (
					$this->getAdapter()->quoteInto('playlist_id = ?', $playlist_id),
					$this->getAdapter()->quoteInto('video_id = ?', $video_id)
				);
				$data = array ('video_order' => $id);
				$this->update($data, $where);
			}
		}
	}

	public function deleteVideos($playlist_id, $deleted) {
		foreach ($deleted as $video_id) {
			if ($video_id) {
				$where = array (
					$this->getAdapter()->quoteInto('playlist_id = ?', $playlist_id),
					$this->getAdapter()->quoteInto('video_id = ?', $video_id)
				);
				$this->delete($where);
			}
		}
	}
}