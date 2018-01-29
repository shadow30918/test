<?php
ini_set('memory_limit', '-1');

define("CONFIG_DIR",dirname(__FILE__).'/../../');
include CONFIG_DIR.'db.inc.php';
include CONFIG_DIR.'class/common.class.php';

//PHP錯誤顯示設定
ini_set("display_errors", "On"); // 顯示錯誤是否打開( On=開, Off=關 )
error_reporting(E_ALL & ~E_NOTICE);

//class init
$common=new Common();
$db=new Database();

//variable init
$arrEvents = array();
$arrInfo = array();
$eventCode="2018CnySp_test";
$ok = 0;
$show=1;
$json=null;
$tmp='';

//取得來源呼叫的主機
if(isset($_SERVER["HTTP_REFERER"])) {
	$http_p=explode("/",$_SERVER['HTTP_REFERER']);
}

if ( strcmp(strtolower($http_p[2]),"www.lgevent-tw.com")==0 || strcmp(strtolower($http_p[2]),"lgevent-tw.com")==0 ||  strcmp(strtolower($http_p[2]),"www.lghatw.com")==0 || strcmp(strtolower($http_p[2]),"lghatw.com")==0){//只接受本機傳送資料
  if (isset($_POST['func'])) {
     $func=trim($_POST['func']);

     switch ($func) {
        case 'getEvent':  //input ==> e_code
            $json = func_getEvent($eventCode);
           break;
        case 'getPage':   //input ==> e_code, ec_type (都小寫)
           if(isset($_POST['type'])){
             $type= strtolower($common->replaceParameter(trim($_POST['type'])));
             $json = func_getPage($type,$eventCode);
           }else{
             $json=array('func' => 'getEvent', 'ok' => $ok); //有值為空
           }
           break;
     }

     echo json_encode($json);
  }
}

function func_getEvent($e_code){
  global $arrEvents,$db,$show,$ok,$tmp,$eventCode;

  $sqlString = "SELECT * FROM Event E JOIN Event_Home H WHERE E.e_show = :show AND E.e_code = :code AND H.eh_e_no = E.e_no";
  $db->query($sqlString);
  $db -> bind(':show',$show,'int');
  $db -> bind(':code',$e_code,'string');
  $rows = $db -> resultset();
  $rowLength = $db -> rowCount();

  if($rowLength > 0){
    $ok = 1;
    $arrEvents[0]=array();
    $arrEvents[0]['title']=$rows[0]['e_title'];
		$arrEvents[0]['menu']['home']['main']='index';
		$arrEvents[0]['menu']['home']['title']=$rows[0]['e_home_title'];
		$arrEvents[0]['menu']['home']['show']=$rows[0]['e_home_show'];
		$arrEvents[0]['menu']['home']['name']=$rows[0]['e_home_name'];
		$arrEvents[0]['menu']['home']['order']=$rows[0]['e_home_order'];
		$arrEvents[0]['menu']['rule']['main']='rule';
		$arrEvents[0]['menu']['rule']['title']=$rows[0]['e_rule_title'];
		$arrEvents[0]['menu']['rule']['show']=$rows[0]['e_rule_show'];
		$arrEvents[0]['menu']['rule']['name']=$rows[0]['e_rule_name'];
		$arrEvents[0]['menu']['rule']['order']=$rows[0]['e_rule_order'];
		$arrEvents[0]['menu']['login']['main']='login';
		$arrEvents[0]['menu']['login']['title']=$rows[0]['e_login_title'];
    $arrEvents[0]['menu']['login']['show']=$rows[0]['e_login'];
		$arrEvents[0]['menu']['login']['name']=$rows[0]['e_login_name'];
		$arrEvents[0]['menu']['login']['order']=$rows[0]['e_login_order'];
		$arrEvents[0]['menu']['process']['main']='process';
		$arrEvents[0]['menu']['process']['title']=$rows[0]['e_process_title'];
	  $arrEvents[0]['menu']['process']['show']=$rows[0]['e_process'];
		$arrEvents[0]['menu']['process']['name']=$rows[0]['e_process_name'];
		$arrEvents[0]['menu']['process']['order']=$rows[0]['e_process_order'];
		$arrEvents[0]['menu']['product']['main']='product';
		$arrEvents[0]['menu']['product']['title']='product';
		$arrEvents[0]['menu']['product']['show']=$rows[0]['e_product_show'];
		$arrEvents[0]['menu']['product']['name']=$rows[0]['e_product_name'];
		$arrEvents[0]['menu']['product']['order']=$rows[0]['e_product_order'];
		$arrEvents[0]['menu']['product']['url']=$rows[0]['e_product_url'];
		$arrEvents[0]['menu']['product']['window']=$rows[0]['e_product_window'];
		$arrEvents[0]['menu']['winner']['main']='winner';
		$arrEvents[0]['menu']['winner']['title']=$rows[0]['e_winner_title'];
		$arrEvents[0]['menu']['winner']['show']=$rows[0]['e_winner_show'];
		$arrEvents[0]['menu']['winner']['name']=$rows[0]['e_winner_name'];
		$arrEvents[0]['menu']['winner']['order']=$rows[0]['e_winner_order'];
		$arrEvents[0]['menu']['winner']['msg']=$rows[0]['e_winner_msg'];
		$arrEvents[0]['menu']['faq']['main']='faq';
		$arrEvents[0]['menu']['faq']['title']=$rows[0]['e_faq_title'];
		$arrEvents[0]['menu']['faq']['show']=$rows[0]['e_faq_show'];
		$arrEvents[0]['menu']['faq']['name']=$rows[0]['e_faq_name'];
		$arrEvents[0]['menu']['faq']['order']=$rows[0]['e_faq_order'];
		$arrEvents[0]['menu']['extra1']['main']='extra1';
		$arrEvents[0]['menu']['extra1']['title']=$rows[0]['e_extra1_title'];
		$arrEvents[0]['menu']['extra1']['show']=$rows[0]['e_extra1_show'];
		$arrEvents[0]['menu']['extra1']['name']=$rows[0]['e_extra1_name'];
		$arrEvents[0]['menu']['extra1']['order']=$rows[0]['e_extra1_order'];
		$arrEvents[0]['menu']['extra2']['main']='extra2';
		$arrEvents[0]['menu']['extra2']['title']='extra2';
		$arrEvents[0]['menu']['extra2']['show']=$rows[0]['e_extra2_show'];
		$arrEvents[0]['menu']['extra2']['name']=$rows[0]['e_extra2_name'];
		$arrEvents[0]['menu']['extra2']['order']=$rows[0]['e_extra2_order'];
		$arrEvents[0]['menu']['extra2']['url']=$rows[0]['e_extra2_url'];
		$arrEvents[0]['menu']['extra2']['window']=$rows[0]['e_extra2_window'];
    $arrEvents[0]['desk_img']=$rows[0]['eh_desk_i_no'].'.jpg';
    $arrEvents[0]['mobile_img']=$rows[0]['eh_mobile_i_no'].'.jpg';

		usort($arrEvents[0]['menu'], function($a, $b) {
		    return $a['order'] - $b['order'];
		});

  }else{
    $ok = -1; //無此活動
  }
    $tmp=array('func' => 'getEvent', 'ok' => $ok, 'event' => $arrEvents, 'eventcode'=>$eventCode );

    return $tmp;
}

function func_getPage($type,$e_code){
  global $arrInfo,$db,$show,$ok,$tmp,$eventCode;
  $sqlString= '';
  if($type === "rule" || $type === "winner"){
    $sqlString = "SELECT * FROM Event_Content C JOIN Event E WHERE E.e_code = :code AND C.ec_type = :type AND E.e_no = C.ec_e_no AND C.ec_show = :show ORDER BY ec_order DESC";
    $db->query($sqlString);
    $db -> bind(':show',$show,'int');
    $db -> bind(':code',$e_code,'string');
    $db -> bind(':type',$type,'string');
  }else{
    $sqlString = "SELECT * FROM Event_Content C JOIN Event E WHERE E.e_code = :code AND C.ec_type = :type AND E.e_no = C.ec_e_no";
    $db->query($sqlString);
    $db -> bind(':code',$e_code,'string');
    $db -> bind(':type',$type,'string');
  }

  $rows = $db -> resultset();
  $rowLength = $db -> rowCount();

  if($rowLength > 0){
    $ok = 1;

    if ($type==='rule' || $type==='winner') {
      for ($i=0;$i<$rowLength;$i++) {
         $arrInfo[$i] = array();
         $arrInfo[$i]['title'] = $rows[$i]['ec_title'];
         $arrInfo[$i]['color'] = $rows[$i]['ec_color'];
         $arrInfo[$i]['desk_img'] = $rows[$i]['ec_desk_i_no'].'.jpg';
         $arrInfo[$i]['mobile_img'] = $rows[$i]['ec_mobile_i_no'].'.jpg';
      }
    }
    if ($type==='faq' || $type==='fbcheckin') {
       $arrInfo[0]=array();
       $arrInfo[0]['desk_img']=$rows[0]['ec_desk_i_no'].'.jpg';
       $arrInfo[0]['mobile_img']=$rows[0]['ec_mobile_i_no'].'.jpg';
    }

  }else{
    $ok = -1;
  }

  $tmp=array('func' => 'getPage', 'ok' => $ok, 'info' => $arrInfo, 'eventcode'=>$eventCode );

  return $tmp;
}
?>
