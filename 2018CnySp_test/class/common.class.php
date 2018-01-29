<?php

class Common {
	
	public function dumpAllSession() {
		echo '<pre>';
		var_dump($_SESSION);
		echo '</pre>';		
	}
	
	public function clearAllSession() {
		unset($_SESSION);
	}
	
	public function checkAdminIdentification($accesstoken,$token) {		
		if ($accesstoken->is_token("AdminLogin",$token)==true) {
			if (isset($_SESSION['a_no']) && isset($_SESSION['a_name']) && isset($_SESSION['a_company']) && isset($_SESSION['a_type'])) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function getTablePrefix($tablename) {	//只適用 table name="AXX"(prefix=a) 或是 "AXX_BXX" (prefix=ab)
		$tableprefix="";
		if (strstr($tablename,'_')==false) {	//沒有找到
			$tableprefix=strtolower(substr($tablename,0,1));
		} else {
			$tableprefix=strtolower(substr($tablename,0,1)).strtolower(substr($tablename,strpos($tablename,'_')+1,1));
		}
		
		return $tableprefix;
	}
	
	public function replaceParameter($data) {
		// remove whitespaces from begining and end
		$data = trim($data);
		
		// apply stripslashes to pevent double escape if magic_quotes_gpc is enabled
		if(get_magic_quotes_gpc())
		{
			$data = stripslashes($data);
		}
		return $data;		
	}
	
	public function getFileExt($fileName) {	//取得檔案副檔名
		if (false !== $pos = strripos($fileName, '.')) {
			return strtolower(substr($fileName, $pos+1, strlen($fileName)));		//小寫
		} else {
			return '';		//沒有副檔名
		}
	}
	
	public function getLaL($addr) {	//取得地址經緯度
		$gmap_api="http://maps.googleapis.com/maps/api/geocode/json?sensor=true&language=zh-TW&region=tw&address=".$addr;
		$geo = file_get_contents($gmap_api);
		$geo = json_decode($geo,true);

		$geo_lat = $geo['results'][0]['geometry']['location']['lat'];
		$geo_lng = $geo['results'][0]['geometry']['location']['lng'];
		
		return $geo_lat.",".$geo_lng;
	}
}

?>