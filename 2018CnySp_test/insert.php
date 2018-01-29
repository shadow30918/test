<?php
/*
* Function 說明
**************************************************
* func = isActivity
* 檢查活動時間
* Output : ok (1=未開始; 2=進行中; 3=結束)
**************************************************
* func = uploadImg
* 上傳圖檔
* Input : myfile
* Output : ok (-3=沒有檔案; -2=上傳失敗; -1=格式錯誤,只接受JPG/JPEG/PNG; 0=檔案太大, 1=上傳成功)
* Output :  file (編號+"."+副檔名) ==> ok=1 時才有值
**************************************************
* func = checkDuplicate
* 資料是否重複
* Input : *name (len<=20), phone (len<=20,與mobile二擇一), mobile(len<=10,與phone二擇一), *address (len<=50),
		    *prod_type (tint), *prod_model (len<=20), *prod_sn_no (len<=15),
	   	 *extra_model (len<=20), *extra_sn_no (len<=15)
* Output : ok (-3=有必填欄位是空值; -2=手機跟電話至少要填一個; -1=格式錯誤; 0=資料重複; 1=無重複)
* Output : field (有問題的欄位, 以 array 傳送) ==> ok=-2/-1 時才有值)
 Output : gift
**************************************************
* func = saveData
* 記錄登錄資料
* Input : *name (len<=20), phone (len<=20,與mobile二擇一), mobile (len<=10,與phone二擇一), *zipcode (len<=5), *address (len<=50), *email (len<=50),
          *invoice_no (len<=10), *inovice_total (len<=10,全數字), *invoice_date (yyyy-mm-dd),
		  *prod_type (tint), *prod_model (len<=20), *prod_sn_no (len<=15),
		  *prod_buy_1 (tint), prod_buy_2 (sint,d=0), prod_buy_other (if prod_buy_1=7),
		  *img_invoice_img, img_guar_img,
		  *extra_model (len<=20), *extra_sn_no (len<=15)
* Output : ok (-4=購買日期未在活動期間內, -3=有必填欄位是空值, 或手機/電話須擇一填寫; -2=格式錯誤; -1=找不到組合贈品(無此組合),
*		   0=新增失敗; 1=新增成功; 2=新增成功, 但主產品機碼重覆; 3=新增成功, 但組合購產品機碼重覆; 4=新增成功, 但主產品&組合購產品機碼重覆)
* Output : field (有問題的欄位, 以 array 傳送) ==> ok=-4/-3/-2 時才有值)
* Output : gift (回傳贈品品項, 以 array 傳送, 因為有主產品&組合購產品贈品; 若無組合購產品, 則無組合購贈品)
**************************************************
* func = getStatus
* 取得審核及配送的狀態
* Input : tel
* Output : ok (-2=空值; -1=格式錯誤; 0=狀態取得失敗; 1=狀態成功取得)
* Output : status (0/1=資料審核中; 3=資料審核不通過; 2=資料審核成功; 4=贈品已寄送) ==> ok=1 時才有值
**************************************************
* func = getProdModel
* 取得各種產品類型所屬的型號List
* Output : ok (1=成功)
* Output : WM (以 array 傳送) ==> ok=1 時才有值
* Output : REF (以 array 傳送) ==> ok=1 時才有值
* Output : OTHER (以 array 傳送) ==> ok=1 時才有值
* Output : Styler (以 array 傳送) ==> ok=1 時才有值
**************************************************
*/

ini_set('memory_limit', '-1');

include 'db.inc.php';
include 'class/common.class.php';
include_once 'soap/nusoap.php';

date_default_timezone_set('Asia/Taipei');
header("Content-Type:text/html; charset=utf-8");

$myIp = $_SERVER['REMOTE_ADDR'];
$catchdIp="122.116.59.202";


//******Turn Error Message ==> Debug用, 鎖 Catchd IP, 只有在公司內部才能看到錯誤息
//PHP錯誤顯示設定
if (strcmp($myIp,$catchdIp)==0) {
	error_reporting(E_ALL);
	ini_set('display_errors', 'ON');		//ON(顯示), OFF(不顯示)
	ini_set('display_startup_errors', TRUE);
} else {
	ini_set('display_errors', 'OFF');		//ON(顯示), OFF(不顯示)
}

//***** Define information -- 功典DB資訊
$ListID=47;
$MemberID="lge";
$UserID="LG_HA_2015";
$Pwd="CM6HQLB2";
$ClientID="HASP2015Q3";

//class init
$common=new Common();

//***** Define information -- 活動期間  06/01/2017-09/30/2017
$startDate = "2017-09-25";	//正式為2017-10-01
$endDate = "2017-12-15"; //***本日仍在活動中


//***** Format pattern
$prod_virtualFormat="/111MKT00(066|067|068|069|0[7-9]{1}[0-9]{1}|[1-4]{1}[0-9]{2}|500)/";
							//虛擬機碼格式 (排除111MKT00065以前的已使用, 111MKT00066~111MKT00500)
$prod_sn_noFormat="/[0-9]{3}[A-Z]{3,4}[0-9A-Z]{5,6}/";		//產品機碼格式 (3位數字+3~4位大寫字母+5~6位數字或大寫字母)
$imgFormat = "/JPG|JPEG|PNG/";	//圖片可接受檔案格式(要先把檔名都轉大寫)
$mobileFormat="/09[0-9]{8}/";		//手機 (09+8位數字)
$phoneFormat="/[0-9]{2,3}[-][0-9]{5,8}/";	//電話 (2~3區碼+'-'+5~8號碼)
$zipcodeFormat="/[0-9]{3,5}/";	//郵遞區號 (3~5位數字)
$invoiceNoFormat="/[A-Z]{2}[0-9]{8}/";	 //發票號碼 (2位大寫字母+8位數字)
$invoiceDateFormat="/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";	//發票日期 (YYYY-MM-DD)
$fileFormat="/[0-9].[JPG|JPEG|PNG]/";	//檔名格式 (數字.JPG|JPEG|PNG)
$invoice_dateStart = "2017-09-25"; //正式為2017-10-01
$invoice_dateEnd = "2017-12-14";

//***** Prod Model List
$arrWM=array();
$arrREF=array(array("GW-BF380SV","GW-BF386SV","GR-FL40SV","GR-R40SV"));
$arrOTHER=array();
$arrStyler=array();

//***** Prod Gift
$arrGift=array();

//***** 組合購 Model List
$arrWMExtra=array();

$arrREFExtra=array(
	array("GW-BF380SV","GW-BF380SV"),array("GW-BF386SV","GW-BF386SV"),array("GW-BF380SV","GW-BF386SV"),array("GR-FL40SV","GR-FL40SV"),array("GR-R40SV","GR-R40SV"),array("GR-FL40SV","GR-R40SV"));

//***** 組合購 Gift
$arrGiftExtra=array(
	"REFExtra_0"=>"7-11商品卡 NT2,000元","REFExtra_1"=>"7-11商品卡 NT2,000元","REFExtra_2"=>"7-11商品卡 NT2,000元","REFExtra_3"=>"7-11商品卡 NT2,000元","REFExtra_4"=>"7-11商品卡 NT2,000元","REFExtra_5"=>"7-11商品卡 NT2,000元");
//***** 上傳檔案限制的大小
$imgSizeMax=6;	//6MB

//***** Other parameter
$json=null;


//取得來源呼叫的主機
if(isset($_SERVER["HTTP_REFERER"])) {
	$http_p=explode("/",$_SERVER['HTTP_REFERER']);
}


//if (strcmp(strtolower($http_p[2]),"www.lgevent-tw.com")==0 || strcmp(strtolower($http_p[2]),"lgevent-tw.com")==0 || strcmp(strtolower($http_p[2]),"www.lghatw.com")==0 || strcmp(strtolower($http_p[2]),"lghatw.com")==0){	//鎖Catchd 公司內部, 或是 LG主機

	if(isset($_REQUEST['func'])) {

		$func=$common->replaceParameter($_REQUEST['func']);
      if (strcmp($func,"isDuplicate")==0 || strcmp($func,"checkDuplicate")==0 || strcmp($func,"saveData")==0 || strcmp($func,"getStatus")==0) {
         $ticket=getTicket();
      }
		switch ($func) {
				case "isActivity":		//檢查活動時間
					$json=func_isActivity();
					break;
				case "uploadImg":		//上傳圖檔
					if (!isset($_FILES["myfile"])) {
						$json=array('func' => 'uploadImg', 'ok' => '-3');	//沒有檔案
					} else {
						$json=func_uploadImg($_FILES["myfile"]);
					}
					break;
				case "checkDuplicate":
						$arrData=array("name"=>"","phone"=>"","mobile"=>"","address"=>"","prod_type"=>"","prod_model"=>"","prod_sn_no"=>"","extra_model"=>'',"extra_sn_no"=>"");
						$msg=array();	//用來記錄那些欄位沒有值

						foreach($arrData as $key => $value) {
							if (isset($_REQUEST[$key]) && strlen($_REQUEST[$key]) > 0 ) {
								$arrData[$key]=$common->replaceParameter($_REQUEST[$key]);
							} else {	//沒有傳這個參數
								array_push($msg, $key);
							}
						}

						if (in_array('phone',$msg) && in_array('mobile',$msg)) {
							$json=array('func' => 'checkDuplicate', 'ok' => '-2', 'msg' => $msg);	//手機跟電話至少填一個
						} else {
							if (count($msg)==1 && (in_array('phone',$msg) || in_array('mobile',$msg))) {
								unset($msg);	//因為手機或電話擇一填寫, 所以一定會有一個是空的 ==> reset $msg array
								$msg=array();
							}
							if(count($msg)==0){
								if (strlen($arrData['name'])>20)
										array_push($msg, 'name');

								if (strlen($arrData['phone'])>0 && !preg_match($phoneFormat, $arrData['phone']))
										array_push($msg, 'phone');

								if (strlen($arrData['mobile'])>0 && !preg_match($mobileFormat, $arrData['mobile']))
										array_push($msg, 'mobile');

								//主產品
								if (!preg_match($prod_sn_noFormat, $arrData['prod_sn_no']) && !preg_match($prod_virtualFormat, $arrData['prod_sn_no']))	//不符合正常機號, 也不符合虛擬機號 (至少要符合一項)
										array_push($msg, 'prod_sn_no');
								//組合購產品
								if (!preg_match($prod_sn_noFormat, $arrData['extra_sn_no']) && !preg_match($prod_virtualFormat, $arrData['extra_sn_no']))	//不符合正常機號, 也不符合虛擬機號 (至少要符合一項)
										array_push($msg, 'extra_sn_no');


								if(count($msg)==0){
									$json = func_checkDuplicate($arrData);
								}else {
									$json=array('func' => 'checkDuplicate', 'ok' => '-1', 'msg' => $msg);	//格式錯誤
								}
							}else{
								$json=array('func' => 'checkDuplicate', 'ok' => '-3', 'msg' => $msg);	//欄位有空值
							}
						}
						break;
			  case "saveData":   //紀錄資料
						//POST過來的欄位
						$arrData=array("name"=>"", "phone"=>"", "mobile"=>"", "zipcode"=>"", "address"=>"", "email"=>"", "invoice_no"=>"", "invoice_total"=>"", "invoice_date"=>"",
										 "prod_buy_1"=>"", "prod_buy_2"=>"", "prod_buy_other"=>"",
										 "img_invoice_img"=>"", "img_guar_img"=>"",
										 "prod_type"=>"", "prod_model"=>"", "prod_sn_type"=>"", "prod_sn_no"=>"", "precheck_rep_prod"=>"0",
										 "extra_model"=>"", "extra_sn_type"=>"", "extra_sn_no"=>"", "precheck_rep_extra"=>"0", "extra_gift"=>"",
										 "check_status"=>"1");
						$msg=array();	//用來記錄那些欄位沒有值

						// start of foreach
						foreach($arrData as $key => $value){
							if (true && !(strcmp($key,"prod_buy_2")==0  || strcmp($key,"prod_buy_other")==0 ||
										   strcmp($key,"prod_sn_type")==0 || strcmp($key,"precheck_rep_prod")==0 ||
										   strcmp($key,"extra_sn_type")==0 || strcmp($key,"precheck_rep_extra")==0 ||
											strcmp($key,"extra_gift")==0 || strcmp($key,"check_status")==0 || strcmp($key,"img_guar_img")==0)) {	//這幾個欄位非必填或已有預設值
									if (isset($_REQUEST[$key])) {
										if ($key=="invoice_no" || $key=="prod_model" || $key=="prod_sn_no" || $key=="extra_model" || $key=="extra_sn_no") {	//轉成大寫
											$arrData[$key]=strtoupper($common->replaceParameter($_REQUEST[$key]));
										} else {
											$arrData[$key]=$common->replaceParameter($_REQUEST[$key]);
										}
									} else {	//沒有傳這個參數
										array_push($msg, $key);
									}
							} else {	 //非必填欄位
				            if (strcmp($key,"prod_buy_2")==0 || strcmp($key,"prod_buy_other")==0 || strcmp($key,"img_guar_img")==0) {
				                $arrData[$key]=$common->replaceParameter($_REQUEST[$key]);
				          	}
							}

							//主產品判別是否為正常機號 或 虛擬機號
							if (preg_match($prod_sn_noFormat, $arrData['prod_sn_no'])) 		//主產品 -- 正常機號
								$arrData['prod_sn_type']=1;
							if (preg_match($prod_virtualFormat, $arrData['prod_sn_no'])) 	//主產品 -- 虛擬機號
								$arrData['prod_sn_type']=2;

							//組合產品判別是否為正常機號 或 虛擬機號
							if (preg_match($prod_sn_noFormat, $arrData['extra_sn_no'])) 		//組合產品 -- 正常機號
								$arrData['extra_sn_type']=1;
							if (preg_match($prod_virtualFormat, $arrData['extra_sn_no'])) 	//組合產品 -- 虛擬機號
								$arrData['extra_sn_type']=2;

							if(($arrData['prod_sn_type'] == 1 || $arrData['extra_sn_type'] == 1) && $arrData['img_guar_img'] == ""){		//主產品或組合產品只要有一個是正常機號, 就應該有保證書
								array_push($msg, "img_guar_img");
							}

							//處理購買通路=其他
							if ($arrData['prod_buy_1']==7 && strcmp($key,"prod_buy_other")==0) {
								 $arrData[$key]=trim($_REQUEST[$key]);
								 $arrData['prod_buy_2'] =0;
							}
						} //End of Foreach

						if (in_array('phone',$msg) && in_array('mobile',$msg)) {
							$json=array('func' => 'saveData', 'ok' => '-3', 'field' => $msg);	//有必填欄位是空值(或手機/電話須擇一填寫)
						} else {
							$msg = array_diff($msg, array('phone', 'mobile'));
							if (count($msg)>0) {
								$json=array('func' => 'saveData', 'ok' => '-3', 'field' => $msg);	//有必填欄位是空值
							} else {
								//檢查格式
								unset($msg);	//reset message array
								$msg=array();

								if (strlen($arrData['name'])>20)
									array_push($msg, 'name');

								if (strlen($arrData['phone'])>0 && !preg_match($phoneFormat, $arrData['phone']))
									array_push($msg, 'phone');

								if (strlen($arrData['mobile'])>0 && !preg_match($mobileFormat, $arrData['mobile']))
									array_push($msg, 'mobile');

								if (!preg_match($zipcodeFormat, $arrData['zipcode']))
									array_push($msg, 'zipcode');

								if (!filter_var($arrData['email'], FILTER_VALIDATE_EMAIL))
									array_push($msg, 'email');

								if (!preg_match($invoiceNoFormat, $arrData['invoice_no']))
									array_push($msg, 'invoice_no');

								if (!preg_match($invoiceDateFormat, $arrData['invoice_date']))
									array_push($msg, 'invoice_date');

								if (!is_numeric($arrData['invoice_total']) || $arrData['invoice_total']<=0)
									array_push($msg, 'invoice_total');

								if (!is_numeric($arrData['prod_buy_1']) || ($arrData['prod_buy_1']<=0 && $arrData['prod_buy_1']>=8))
									array_push($msg, 'prod_buy_1');

								if (!is_numeric($arrData['prod_buy_2']) || ($arrData['prod_buy_2']<=0 && $arrData['prod_buy_2']>=700))
									array_push($msg, 'prod_buy_2');

								if ($arrData['prod_buy_1']==7 && (strlen($arrData['prod_buy_other'])==0))
									array_push($msg, 'prod_buy_other');

								if (strlen($arrData['img_invoice_img'])==0 && !preg_match($fileFormat, $arrData['img_invoice_img']))
									array_push($msg, 'img_invoice_img');

								if (($arrData['prod_sn_type'] == 1 || $arrData['extra_sn_type'] == 1) && (strlen($arrData['img_guar_img'])==0 && !preg_match($fileFormat, $arrData['img_guar_img'])))
									//如果主產品或組合產品是正常機號, 則需上傳保證書圖檔
									array_push($msg, 'img_guar_img');

								//主產品
								if (!is_numeric($arrData['prod_type']) || !($arrData['prod_type']==1))
									array_push($msg, 'prod_type');

								if (checkProdModel($arrData['prod_model'])==false) //無此產品型號
									array_push($msg, 'prod_model');

								if (!preg_match($prod_sn_noFormat, $arrData['prod_sn_no']) && !preg_match($prod_virtualFormat, $arrData['prod_sn_no']))	//不符合正常機號, 也不符合虛擬機號 (至少要符合一項)
									array_push($msg, 'prod_sn_no');

								//組合購產品
								if (checkExtraModel($arrData['extra_model'])==false) //無此產品型號
									array_push($msg, 'extra_model');

								if (!preg_match($prod_sn_noFormat, $arrData['extra_sn_no']) && !preg_match($prod_virtualFormat, $arrData['extra_sn_no']))	//不符合正常機號, 也不符合虛擬機號 (至少要符合一項)
									array_push($msg, 'extra_sn_no');

								if (count($msg)>0) {
									$json=array('func' => 'saveData', 'ok' => '-2', 'field' => $msg);	//格式錯誤
								} else {
									if (strtotime($invoice_dateStart) > strtotime($arrData['invoice_date'])) {		//活動未開始
										array_push($msg, 'invoice_date');
									} else if (strtotime($arrData['invoice_date']) > strtotime($invoice_dateEnd)) {	//活動截止
										array_push($msg, 'invoice_date');
									}

									if (count($msg)>0) {
										if (strcmp($msg[0],'invoice_date')==0) {
											$json=array('func' => 'saveData', 'ok' => '-4', 'field' => $msg);	//格式錯誤,發票日期未在活動間
										}
									} else {
										//組合購產品 ==> 檢查是否有這組組合
						            if (checkExtraGift($arrData['prod_type'],$arrData['prod_model'],$arrData['extra_model'])==""){	//找不到組合贈品(無此組合)
						               $json=array('func' => 'saveData', 'ok' => '-1');	//無此組合
						            } else {
									  		//$json = $arrData['back_type'];
						               $json=func_saveData($arrData);	//有組合購
						            }
									}
								}
							}
						}

			 		break;
				case "getStatus":		//取得審核及配送的狀態
					if (!isset($_REQUEST['tel'])) {
						$json=array('func' => 'getStatus', 'ok' => '-2');	//空值
					} else {
						//檢查格式
						$tel=$common->replaceParameter(trim($_REQUEST['tel']));
						$tel_type=0;	//1=電話 phone, 2=手機 mobile
						if (strlen($tel)==0) {
							$json = array('func' => 'getStatus', 'ok' => '-2');
						} else {
								if (preg_match($phoneFormat, $tel)) {
									$tel_type=1;
								}
								if (preg_match($mobileFormat, $tel)) {
									$tel_type=2;
								}
								if ($tel_type==0) {	//不是電話, 也不是手機 ==> 格式錯誤
									$json = array('func' => '5', 'ok' => '-1');
								} else {	//==> 查詢記錄
										$json=func_getStatus($tel_type,$tel);
									 }
							}
						}
						break;
					case "getProdModel":	//取得各種產品類型所屬的型號List
						$json=array('func' => 'getProdModel', 'ok' => '1', 'WM' => array_to1d($arrWM), 'REF'=> array_to1d($arrREF), 'OTHER'=> array_to1d($arrOTHER), 'Styler'=> array_to1d($arrStyler));
						break;
		}
      echo json_encode($json);
	}

//}

//======================================= Function
function func_isActivity() {	//檢查活動時間
	global $startDate, $endDate,$fixEnd,$fiXStart;
	$ok=0;
	$tmp="";
	$today = date("Y-m-d");
	$time = date("Y-m-d H:m:s");
	if (strtotime($startDate) > strtotime($today)) {		//活動未開始
		$ok=1;
	} else if (strtotime($today) > strtotime($endDate)) {	//活動截止
		$ok=3;
	} else{				//活動進行中
			$ok=2;
	}

	$tmp=array('func' => 'isActivity', 'ok' => $ok,'year'=>(int)date('Y'), 'mon' => (int)date('n'), 'day' => (int)date('d'));
	return $tmp;
}

function func_uploadImg($varFile) {	//上傳圖檔
	global $imgSizeMax, $imgFormat;
	$i_no=0;	//檔案編號
	$ok=0;
	$tmp="";

	if(($_FILES["myfile"]["size"]/1024) > 1024*$imgSizeMax){
		$ok=-2;	//檔案太大, 超過限制
	} else {
		//上傳單一檔案
		$fileName = strtoupper($varFile["name"]);	//把檔名轉為大寫
		$fileExt = getFileExt($fileName);

		if (preg_match($imgFormat,$fileExt)){
			$db=new Database();
			$db->query('INSERT INTO lghatwco_img (i_ext) VALUES (:i_ext)');
			$db->bind(':i_ext', $fileExt,'string');
			$db->execute();
			$i_no=$db->lastInsertId();

			$fileFullName=$i_no.".".$fileExt;
			if (move_uploaded_file($varFile["tmp_name"],"../../upload/ori/".$fileFullName)) {
				$ok=1;	//上傳成功
			} else {
				$ok=0; 	//上傳失敗
			}
		} else {
			$ok=-1;	//檔案格式錯誤
		}
	}

	if ($ok==1) {
		$tmp=array('func' => '3', 'ok' => $ok, 'file' => $fileFullName);
	} else {
		$tmp=array('func' => '3', 'ok' => $ok);
	}
	return $tmp;
}

function func_isDuplicate($varProd_type, $varProd_model, $varProd_sn_no, $returnType) {	//檢查產品機號是否重覆, $returnType (1=以JSON方式回傳, 2=以數值回傳)
   global $ListID;
	global $arrData, $prod_sn_noFormat;
	$ok=1;  //0=重覆, 1=沒有重覆
    $ok_1=0;
    $ok_2=0;
	$tmp="";

	//檢查產品機碼有沒有重覆 (0=重覆, 1=沒有重覆)
	//主產品
	$list = array(array('ruledetail' =>array('leftop' => '','listid' => $ListID,'fieldid' => 'prod_type','operator' => '=','value' =>$varProd_type,'rightop' => '')),
				  array('ruledetail' =>array('leftop' => 'AND','listid' => $ListID,'fieldid' => 'prod_model','operator' => '=','value' => $varProd_model,'rightop' => '')),
				  array('ruledetail' => array('leftop' => 'AND','listid' => $ListID,'fieldid' => 'prod_sn_no','operator' => '=','value' => $varProd_sn_no,'rightop' => '')));

	$ok_1=getSOAP('GetQueryDataCount',$list);

	//組合購產品
	$list = array(array('ruledetail' =>array('leftop' => '','listid' => $ListID,'fieldid' => 'prod_type','operator' => '=','value' =>$varProd_type,'rightop' => '')),
				  array('ruledetail' =>array('leftop' => 'AND','listid' => $ListID,'fieldid' => 'extra_model','operator' => '=','value' => $varProd_model,'rightop' => '')),
				  array('ruledetail' => array('leftop' => 'AND','listid' => $ListID,'fieldid' => 'extra_sn_no','operator' => '=','value' => $varProd_sn_no,'rightop' => '')));

	$ok_2=getSOAP('GetQueryDataCount',$list);
	//echo $varProd_type.'*'.$varProd_model.'*'.$varProd_sn_no.'========';
	//echo $ok_1."****".$ok_2.'<br />';
	if ($ok_1>0 || $ok_2>0) {
		$ok=0;
		//echo '...'.$ok;
	}

	if ($returnType==1) {	//以JSON方式回傳
		$tmp=array('func' => 'isDuplicate', 'ok' => $ok);
		return $tmp;
	} else {		//以數值方式回傳
		return $ok;
	}
}

function func_checkDuplicate($varArrData){
	global $ListID;
	global $arrData, $prod_sn_noFormat;
	$ok=1;  //0=重覆, 1=沒有重覆
	$tmp="";
	$gift="";


	$list = array(array('ruledetail' =>array('leftop' => '','listid' => $ListID,'fieldid' => 'name','operator' => '=','value' =>$varArrData['name'],'rightop' => '')),
			  array('ruledetail' =>array('leftop' => 'AND','listid' => $ListID,'fieldid' => 'address','operator' => '=','value' => $varArrData['address'],'rightop' => '')),
			  array('ruledetail' => array('leftop' => 'AND(','listid' => $ListID,'fieldid' => 'phone','operator' => '=','value' => $varArrData['phone'],'rightop' => '')),
			  array('ruledetail' =>array('leftop' => 'OR','listid' => $ListID,'fieldid' => 'mobile','operator' => '=','value' => $varArrData['mobile'],'rightop' => ')')),
			  array('ruledetail' =>array('leftop' => 'AND(','listid' => $ListID,'fieldid' => 'prod_sn_no','operator' => '=','value' => $varArrData['prod_sn_no'],'rightop' => '')),
			  array('ruledetail' =>array('leftop' => 'OR','listid' => $ListID,'fieldid' => 'extra_sn_no','operator' => '=','value' => $varArrData['extra_sn_no'],'rightop' => ')')));


	$tmpArr=getSOAP('GetQueryDataCount',$list);

	if($tmpArr > 0){
			if(!($varArrData['extra_model']=='')){
				$arrData['extra_gift']=checkExtraGift($varArrData['prod_type'],$varArrData['prod_model'],$varArrData['extra_model']);
					$gift=$arrData['extra_gift'];
			}
		$tmp=array('func' => 'checkDuplicate', 'ok' => '0', 'gift' => $gift );
	}else{
		$tmp=array('func' => 'checkDuplicate', 'ok' => '1' );
	}
	return $tmp;
}

function func_saveData($varArrData) {
	global $prod_sn_noFormat,$prod_virtualFormat;
	$ok=0;	//0=新增失敗; 1=新增成功; 2=新增成功, 但主產品機碼重覆; 3=新增成功, 但組合購產品機碼重覆; 4=新增成功, 但主產品&組合購產品機碼重覆
	$gift="";
	$multi="";

	//******* 主產品
	//判斷是否重覆
	if (func_isDuplicate($varArrData['prod_type'], $varArrData['prod_model'], $varArrData['prod_sn_no'], 0)==0) {	//判別主產品機號是否重覆
		$varArrData['precheck_rep_prod']=1;
		$ok=2;
	}

	//******* 組合產品
	//判斷是否重覆
	if (func_isDuplicate($varArrData['prod_type'], $varArrData['extra_model'], $varArrData['extra_sn_no'], 0)==0) {	//判別組合產品機號是否重覆
		$varArrData['precheck_rep_extra']=1;
		if ($ok==0) {	//主產品未重覆
			$ok=3;
		} else {
			$ok=4;
		}
	}

	if (preg_match($prod_sn_noFormat, $varArrData['extra_sn_no'])) 		//組合產品 -- 正常機號
		$varArrData['extra_sn_type']=1;
	if (preg_match($prod_virtualFormat, $varArrData['extra_sn_no'])) 	//組合產品 -- 虛擬機號
		$varArrData['extra_sn_type']=2;

	//判別贈品為何
	$varArrData['extra_gift']=checkExtraGift($varArrData['prod_type'],$varArrData['prod_model'],$varArrData['extra_model']);
	$gift=$varArrData['extra_gift'];


	//對圖檔網址進行處理
	$img_invoice_img="http://www.lgevent-tw.com/upload/ok(2017OctSp)/no.".getFileExt($varArrData['img_invoice_img']);

	//==> 主產品 或 組合產品, 只要有一項為正常機號, 就應該上傳保證卡
	if ($varArrData['prod_sn_type']==1 || $varArrData['extra_sn_type']==1) {
		$img_guar_img="http://www.lgevent-tw.com/upload/ok(2017OctSp)/no_1.".getFileExt($varArrData['img_guar_img']);
	} else {
		$img_guar_img="";
	}

	//登錄資料
	$list = array('row' => array('name' => $varArrData['name'],'phone' => $varArrData['phone'],'mobile' => $varArrData['mobile'],
					  'zipcode' => $varArrData['zipcode'],'address' => replaceAddress($varArrData['address']),'email' => $varArrData['email'],
					  'invoice_no' => $varArrData['invoice_no'],'invoice_total' => $varArrData['invoice_total'],'invoice_date' => $varArrData['invoice_date'],
					  'prod_type' => $varArrData['prod_type'],'prod_model' => $varArrData['prod_model'],'prod_sn_type' => $varArrData['prod_sn_type'],
					  'prod_sn_no' => $varArrData['prod_sn_no'],'precheck_rep_prod' => $varArrData['precheck_rep_prod'],
					  'prod_buy_1' => $varArrData['prod_buy_1'],'prod_buy_2' => $varArrData['prod_buy_2'],'prod_buy_other' => $varArrData['prod_buy_other'],
					  'img_invoice_img' => $img_invoice_img,'img_guar_img' => $img_guar_img,
					  'extra_model' => $varArrData['extra_model'],'extra_sn_type' => $varArrData['extra_sn_type'],
					  'extra_sn_no' => $varArrData['extra_sn_no'],'precheck_rep_extra' => $varArrData['precheck_rep_extra'],'extra_gift' => $varArrData['extra_gift'],
					  'check_status' => $varArrData['check_status']));

	if (getSOAP('InsertListItem',$list)==0) {
		//找出剛剛登錄記錄的編號
		$list = array('pk' => array('fieldid' => 'prod_sn_no','value' =>  $varArrData['prod_sn_no'],),);
		$no=getSOAP('GetListItemData_1',$list);

		//COPY圖檔, 並將圖檔重新命名
		$oInvoice_img="../../upload/ori/".strtoupper($varArrData['img_invoice_img']);
  		$nInvoice_img="../../upload/ok(2017OctSp)/".$no.".".getFileExt($varArrData['img_invoice_img']);
		copy($oInvoice_img , $nInvoice_img);


  		//==> 主產品 或 組合產品, 只要有一項為正常機號, 就應該有保證書
		if ($varArrData['prod_sn_type']==1 || $varArrData['extra_sn_type']==1) {
			$oGuar_img="../../upload/ori/".strtoupper($varArrData['img_guar_img']);
  			$nGuar_img="../../upload/ok(2017OctSp)/".$no."_1.".getFileExt($varArrData['img_guar_img']);
  			copy($oGuar_img , $nGuar_img);
		}

		//寄發通知信
		mailto($varArrData['email']);

		if ($ok==0) {	//如果不是重覆產品機號
			$ok=1;
		}
	} else {
		$ok=0;
	}
	$tmp=array('func' => 'saveData', 'ok' => $ok, 'gift' => $gift );
	return $tmp;

}


function func_getStatus($varTel_type,$varTel) {
	if ($varTel_type==1) {	//電話
		$list = array('pk' => array('fieldid' => 'phone','value' => $varTel));
	}
	if ($varTel_type==2) {	//手機
		$list = array('pk' => array('fieldid' => 'mobile','value' => $varTel));
	}

	$tmpArr=getSOAP('GetListItemData_2',$list);

	if (count($tmpArr)>0) {	//有找到資料
	  $tmp=array('func' => '5', 'ok' => '1', 'data' => $tmpArr);
	} else {	//沒有資料
	  $tmp=array('func' => '5', 'ok' => '0');
	}
	return $tmp;
}
//======================================= Common -- Other
function checkProdModel($varProd_model) {	//檢查主產品型號是不是包含在本次活動的型號中
	global $arrWM, $arrREF, $arrOTHER, $arrStyler;
	$arrWM_1D=array_to1d($arrWM);
	$arrREF_1D=array_to1d($arrREF);
	$arrOTHER_1D=array_to1d($arrOTHER);
	$arrStyler_1D=array_to1d($arrStyler);

	$arrAll=array_merge($arrWM_1D, $arrREF_1D, $arrOTHER_1D, $arrStyler_1D);

	$haveFound=false;		//是否有找到產品型號 (true=有找到 / false=沒有找到) ==> 要找到才行

	for ($i=0;$i<count($arrAll);$i++) {
		if (strcmp($arrAll[$i],$varProd_model)==0) {
			$haveFound=true;
			break;
		}
	}
	return $haveFound;
}

function checkExtraModel($varExtra_model) {	//檢查組合產品型號是不是包含在本次活動的型號中
	global $arrWMExtra, $arrREFExtra;
	$arrWMExtra_1D=array_to1d($arrWMExtra);
	$arrREFExtra_1D=array_to1d($arrREFExtra);

	$arrAll=array_merge($arrWMExtra_1D, $arrREFExtra_1D);

	$haveFound=false;		//是否有找到產品型號 (true=沒有找到 / false=找到) ==> 要找到才行

	for ($i=0;$i<count($arrAll);$i++) {
		if (strcmp($arrAll[$i],$varExtra_model)==0) {
			$haveFound=true;
			break;
		}
	}
	//echo $haveFound;
	return $haveFound;
}

function checkExtraGift($varProd_type, $varProd_model, $varExtra_model) {	//組合產品確認贈品是哪一個
	global $arrWMExtra, $arrREFExtra, $arrGiftExtra;
	$type="";
	$gift="";

	switch ((int)$varProd_type) {
		case 1:
			$arrList=&$arrREFExtra;
			$type="REFExtra";
			break;
	}

	for ($i=0;$i<count($arrList);$i++) {
	     if ((strcmp($arrList[$i][0], $varProd_model)==0 && strcmp($arrList[$i][1], $varExtra_model)==0) || (strcmp($arrList[$i][0], $varExtra_model)==0 && strcmp($arrList[$i][1], $varProd_model)==0)) {
	     	break 1;
	     }
	}

   if ($i<count($arrList)) {
  		$gift=$arrGiftExtra[$type."_".$i];
	}

	return $gift;
}

function mailto($to){ //==========>待確認
	$subject = '極致禮讚 LG秋送好禮【確認登錄通知函】';
	$subject = "=?UTF-8?B?".base64_encode($subject)."?=";

	$message = '
	LG已收到您的登錄資料，我們將進行審核，您亦可上活動網站的「進度查詢」單元進行查詢，待贈品寄出前會再以手機簡訊通知，感謝您對LG的支持與愛護！
	<br />
	**若有任何問題，請於2017/12/15前電洽LG活動小組專線：0809-066-669(星期一至星期五9:00~12:00，13:30~18:00)
	<br /><br />
	LG台灣 祝您事事順心
	<br /><br />
	「極致禮讚 LG秋送好禮」活動小組敬上
	<br /><br />
	*此信件為系統自動發送，請勿直接回覆，若有問題歡迎撥打LG活動專線0809-066-669';
	$headers="From: LG@royalalliance.com.tw";
	$headers = "From: LG@royalalliance.com.tw\r\n";
	$headers .= "Reply-To: LG@royalalliance.com.tw\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=utf-8\r\n";
	mail($to, $subject, $message, $headers);
}

//======================================= Common -- Utility
function getFileExt($fileName) {	//取得檔案副檔名
	if (false !== $pos = strripos($fileName, '.')) {
	    return strtoupper(substr($fileName, $pos+1, strlen($fileName)));		//小寫
	} else {
	    return '';		//沒有副檔名
	}
}

function array_to1d($a) {		//把2D Array 轉為1D Array
    $out = array();
    foreach ($a as $b) {
        foreach ($b as $c) {
            if (isset($c)) {
                $out[] = $c;
            }
        }
    }
    return $out;
}

function array_to_xml($list, &$xml_list) {	//把 Array 轉成 XML
	foreach($list as $key => $value) {
		if(is_array($value)) {
			if(!is_numeric($key)){
				  $subnode = $xml_list->addChild("$key");
				  array_to_xml($value, $subnode);
			}else{
				  //$subnode = $xml_list->addChild("$key");
				  array_to_xml($value, $xml_list);
			}
		}else {
				  $xml_list->addChild("$key",htmlspecialchars("$value"));
		}
	}
	return $xml_list;
}

function replaceAddress($varAddr) {		//地址數字取代
	$pos = strpos($varAddr,'路');
	$oriAddr = substr($varAddr,0,$pos);
   $repAddr = substr($varAddr,$pos);

	$repAddr = str_replace("一","1",$repAddr);
	$repAddr = str_replace("二","2",$repAddr);
	$repAddr = str_replace("三","3",$repAddr);
	$repAddr = str_replace("四","4",$repAddr);
	$repAddr = str_replace("五","5",$repAddr);
	$repAddr = str_replace("六","6",$repAddr);
	$repAddr = str_replace("七","7",$repAddr);
	$repAddr = str_replace("八","8",$repAddr);
	$repAddr = str_replace("九","9",$repAddr);
	//$repAddr = str_replace("十","0",$repAddr);
	$repAddr = str_replace("１","1",$repAddr);
	$repAddr = str_replace("２","2",$repAddr);
	$repAddr = str_replace("３","3",$repAddr);
	$repAddr = str_replace("４","4",$repAddr);
	$repAddr = str_replace("５","5",$repAddr);
	$repAddr = str_replace("６","6",$repAddr);
	$repAddr = str_replace("７","7",$repAddr);
	$repAddr = str_replace("８","8",$repAddr);
	$repAddr = str_replace("９","9",$repAddr);
	$repAddr = str_replace("０","0",$repAddr);
	$repAddr = str_replace("-", "之", $repAddr);
	$at_add_replace = $oriAddr.$repAddr;
	return $at_add_replace;
}

//======================================= Common -- 功典
function getTicket() {
	global $client, $MemberID, $UserID, $Pwd, $ClientID;
	$tmp="";

	//Create SOAP Client
	$serverpath = 'https://tt3.ecrm.com.tw/web_service/ListWebServiceS/WebMAX_List_Service_S.asmx?wsdl';
	$client = new nusoap_client($serverpath,true);

	//Get Ticket
	$params = array("strMemberID" => $MemberID, "strUserID" =>$UserID, "strPassword" =>$Pwd, "strClientID" =>$ClientID);
	$tmp = getSOAP('GetTicket',$params);

	return $tmp;
}

function getSOAP($soapAction, $varList) {
	global $client,$ListID,$ticket,$arrData;
	$tmp=null;
	$result=null;
	$i=0;

	if ($soapAction=='GetTicket') {
		$result = $client->call($soapAction,$varList);
		$xml = simplexml_load_string($result['GetTicket3Result']);
		$tmp = (string)$xml->result;
	} else {	//init parameters
		if ($soapAction=='InsertListItem') {
			$xml_list = new SimpleXMLElement("<rowlist></rowlist>");
			array_to_xml($varList,$xml_list);
			$strXML = $xml_list->asXML();

			$arrParams = array("strTicket" => $ticket, "nListID" => $ListID, "strXmlListItem" => $strXML, "nAttribute"=> 1, "nTimeout" => 180);
		} else if ($soapAction=='GetQueryDataCount') {	//查詢主產品/組合購產品機碼是否重覆
			$xml_list = new SimpleXMLElement("<ruledetaillist></ruledetaillist>");
			array_to_xml($varList,$xml_list);
			$strXML = $xml_list->asXML();

			$arrParams = array("strTicket" => $ticket, "nListID" => $ListID, "strXmlRuleDetailList" => $strXML, "bFilterBlackList" => false,"strXmlRelation"=>"", "nTimeout" => 180);
		} else {
			$xml_list = new SimpleXMLElement("<pklist></pklist>");
			array_to_xml($varList,$xml_list);
			$strXML = $xml_list->asXML();

			//GetListItemData_1=查詢新增的登錄記錄的編號 (用來重設上傳圖檔的編號)
			if ($soapAction=='GetListItemData_1') {
				$arrParams = array("strTicket" => $ticket, "nListID" => $ListID, "strFields" => "no,prod_type,prod_model",
				 				   "strXmlPK" => $strXML, "nTimeout" => 180, "bDecodePassword" => false);
			}

			//GetListItemData_2=查詢審核記錄
			if ($soapAction=='GetListItemData_2') {
				$arrParams = array("strTicket" => $ticket, "nListID" => $ListID, "strFields" => "prod_type,prod_model,prod_sn_no,check_status",
								   "strXmlPK" => $strXML, "nTimeout" => 180, "bDecodePassword" => false);
			}
			//echo "arrParams=".json_encode($arrParams)."<br>";
		}

	}

	if ($soapAction=='InsertListItem') {
    	$result = $client->call("InsertListItem",$arrParams);
		$xml = simplexml_load_string($result['InsertListItem7Result']);
		$tmp = (string)$xml->result;
	}


	if ($soapAction=='GetQueryDataCount') {	//查詢產品機碼是否重覆 (>0 ==> 回傳重覆的筆數)
		//echo json_encode($arrParams);
		$result = $client->call("GetQueryDataCount",$arrParams);
		$xmlObject = new SimpleXMLElement($result['GetQueryDataCount2Result']);
		$tmp = (int)$xmlObject->result;
	}

	if ($soapAction=='GetListItemData_1') {	//查詢新增的登錄記錄的編號
		$result = $client->call("GetListItemData",$arrParams);
		$xmlObject = new SimpleXMLElement($result['GetListItemData2Result']);

		$tmp=0;
		foreach ($xmlObject->children() as $node) {
			if ($node->no) {
				if (((int)$node->prod_type==(int)$arrData['prod_type']) && strcmp($node->prod_model,$arrData['prod_model'])==0) {
					if ((int)$node->no > (int)$tmp) {	//必須找出最大一筆
						$tmp=$node->no;	//流水號
					}
				}
			}
		}

		if ($tmp==null) {
			$tmp=-1;
		}
	}

	if ($soapAction=='GetListItemData_2') {	//查詢審核記錄
		$result = $client->call("GetListItemData",$arrParams);
		$xmlObject = new SimpleXMLElement($result['GetListItemData2Result']);

		$tmp=array();

		foreach ($xmlObject->children() as $node) {
			if ($node->check_status) {	//1=資料審核中, 2=資料審核不通過, 3=資料審核成功, 4=贈品已寄
				//echo var_dump($node->check_status);
				array_push($tmp, $node);
			}
		}
	}
		//echo "list=".json_encode($varList)."<br><br>";
		//echo "result=".json_encode($result)."<br><br>";
		//echo "tmp=".$tmp."<br><br>";

	return $tmp;
}

?>
