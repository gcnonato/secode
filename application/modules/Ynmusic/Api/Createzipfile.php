<?php
ob_start();
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(dirname(dirname(dirname(__FILE__))))));
require_once (APPLICATION_PATH . '/application/modules/Ynmusic/externals/lib/zip_min.inc');

class Ynmusic_Api_Createzipfile extends Core_Api_Abstract {
	public function downloadAlbum($album) {
		if(!$album || !$album->isViewable()) {
			return false;
		}
		$filename = $album -> getTitle();
		$filename = str_replace(" ", "_", $filename);
		$filename = $this->stripUnicode($filename);

		if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
			$filename = rawurlencode($filename);

		$filename .= '.zip';
		$this->generateZip($album, $filename);
	}
	
	
	protected function generateZip($album, $filename) {
		$zip = new zipfile();
		$songs = $album->getAvailableDownloadSongs();
		foreach ($songs as $song) {
			$file_path = $song->getFilePath();
			// to remove params from file url
			$path_parts = pathinfo(parse_url($file_path, PHP_URL_PATH));
			$contents = file_get_contents($file_path);
			if ($contents === false) {
				continue;
			}
			$zip -> addFile($contents, $song->getTitle().'.mp3');
		}
		// http headers for zip downloads
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Transfer-Encoding: binary");
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		ob_end_flush();
		echo $zip -> file();
	}
	
	protected function stripUnicode($str) { 
		if(!$str)
		{
			return false;
		} 
		$unicode = array(
            'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ', 
            'd'=>'đ', 
            'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ', 
            'i'=>'í|ì|ỉ|ĩ|ị', 
            'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ', 
            'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự', 
            'y'=>'ý|ỳ|ỷ|ỹ|ỵ', 
		);
		foreach($unicode as $nonUnicode => $uni) {
			$str = preg_replace("/($uni)/i", $nonUnicode, $str);
		}
		return $str;
    } 
}
