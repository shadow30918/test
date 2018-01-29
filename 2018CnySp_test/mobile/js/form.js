//userData
var name,phone,mobile,zipcode,address,email,prod_type,prod_model,prod_sn_no,invoice_no,invoice_total,invoice_date,invoive_img,buy_1,buy_2,buy_other,extra_model,extra_sn_type,extra_sn_no;
var check_tnc = 0; //了解個資規範 1=同意，0=未同意
var precheck_rep_prod = 0; //是否重覆
var prod_sn_type = 0;
var extra_sn_type = 0;
var invoice_process = 0;
var guar_process = 0;
var file_error=0;
var file2_error=0;
var guar_img=null;
var date_1 = new Date("2017-9-25");
var date_2 = new Date("2017-12-14");
var next_count = 0;
var backType = 0;
var backGift="";
var path_de = [
["101","燦坤"],["102","全國電子"],["103","BEST倍適特"],["104","上新聯晴"],["105","順發3C"],["106","大同3C展售中心"],
["201","家樂福"],["202","大潤發"],["203","愛買"],["204","COSTCO"],["205","特力屋"],
["301","臺北巿"],["302","臺中巿"],["303","基隆巿"],["304","臺南巿"],["305","高雄巿"],["306","新北市"],["307","宜蘭縣"],["308","桃園縣"],
["309","嘉義巿"],["310","新竹縣"],["311","苗栗縣"],["312","南投縣"],["313","彰化縣"],["314","新竹巿"],["315","雲林縣"],["316","嘉義縣"],
["317","屏東縣"],["318","花蓮縣"],["319","臺東縣"],["320","金門縣"],["321","澎湖縣"],["322","連江縣"],
["401","奇摩購物"],["402","PC home購物"],["403","Momo富邦"],["404","Go Happy購物"],["405","UDN買東西"],
["406","Myphone購物中心"],["407","ASAP購物中心"],["408","FriDay"],["409","森森購物中心"],["410","東森購物中心"],
["411","486"],["412","捷元B2B購物平台"],["413","其他"],
["501","Momo"],["502","東森購物"],["503","其他"],
["601","Sogo"],["602","大遠百"],["603","新光三越"],["604","漢神百貨"],["605","中友百貨"],["606","阪急百貨"],["607","夢時代"],
["608","耐斯百貨"],["609","大葉高島屋"]]
$(function(){
    ini();
});
function ini(){
  if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) {
    ieversion=new Number(RegExp.$1)
          if (ieversion<=10){
             alert('建議使用IE10以上的版本或其他瀏覽器( chrome、firefox)觀看本網站!');
          }
    } else {
      var request_1 = $.ajax({
            url: "../insert.php",
            type: "POST",
            data: {func : 'isActivity'},
            dataType: "json"
      });
      request_1.done(function(data) {
          over = parseInt(data.ok,10);
         switch (over) {   //活動期間
            case 1:
                  alert("【極致禮讚，LG秋送好禮】 歡慶周年慶 好禮有購讚 兌換登錄活動尚未開始, 敬請期待！！");
               location.href="index.html";
               break;
            case 2:
               getProModel();
               $('#twzipcode').twzipcode({
                   // 依序套用至縣市、鄉鎮市區及郵遞區號框
                   'css': ['county', 'district', 'zipcode']
               });
               $('#f_prod_model').on('change', function() {
                  showExtraModel();
               });
               break;
            case 3:
                  alert("【極致禮讚，LG秋送好禮】歡慶周年慶 好禮有購讚 兌換登錄活動已結束！！");
               window.location.href="index.html";
               break;
               case 4:
                   alert("敬愛的LG顧客您好：\n因10/13 (四)將進行後台維護作業，\n建議您於10/14(五)再做登錄或查詢，造成不便，敬請見諒。");
                   getProModel();
                break;
         }
      });
   }
}

function getProModel() {
   var request_pro = $.ajax({
      url: "../insert.php",
      type: "POST",
      data: {
         func: 'getProdModel'
      },
      dataType: "json"
   });
   request_pro.done(function(data) {
      if (data.ok == '1') {
         for (var k = 0; k <= data.REF.length - 1; k++) {
            $('#f_prod_model').append('<option value="' + data.REF[k] + '" class="1">' + data.REF[k] + '</option>');
         }
         init();
      }
   });
}

function init() {
   $('#f_prod_model').chained('#f_prod_type');
   $('#f_extra_model').chained('#f_prod_type');
   for(var k=0;k<=path_de.length-1;k++ ){
       $('#f_buy_2').append('<option value="'+path_de[k][0]+'" class="'+path_de[k][0].substr(0,1)+'">'+path_de[k][1]+'</option>');
   }
   $("#f_buy_2").chained("#f_buy_1"); //購買通路
   $('#f_buy_1').on('change',function(){
      if($(this).val()== "7"){
         $('#f_buy_2').hide();
         $('#f_buy_other').show();
      } else {
         $('#f_buy_2').show();
         $('#f_buy_other').hide();
      }
   });

   $('#f_prod_model').on('change',function() {
      showExtraModel();
   });

   if (/iphone|ipad|ipod|android|blackberry|mini|windows\sce|palm/i.test(navigator.userAgent.toLowerCase())) {
      $('#S_1 #invoiceDate').empty();
      $("#S_1 #invoiceDate").append('<span  class="ItemName">購買日期</span><input type="text" id="f_invoice_date" maxlength="10" placeholder="例如:2017-10-05.">');
   }
   //上傳圖片
  var obj = $("#Div_File #singleupload").uploadFile({
      url:"../insert.php",
      dynamicFormData: function() {
         var data ={ "func": 'uploadImg' }
         return data;
      },
      dragDrop:false,
      multiple:false,
      uploadStr:'<div id="BU_Update_Invoice" class="bu"> 發 票 上 傳</div>',
      fileName:"myfile",
      maxFileSize:6291456, //6mb 6291456
      maxFileCount: 1,
      showFileCounter:false,
      allowedTypes:"jpg,jpeg,png",
      showDone:false,
      onSelect :function(files,data,xhr) {
         $('#Div_File .ajax-file-upload').hide();
         file_error = 2; //上傳中
      },
      onError : function(files,data,xhr) {
         obj.reset();
         $('#Div_File .ajax-file-upload').show();
         file_error = 3; //上傳失敗
      },
      onSuccess:function(files,data,xhr) {
         json_data=$.parseJSON(data);
         //console.log(json_data.file);
         if (json_data.file.length>0) {
            $('#Div_File .ajax-file-upload-statusbar').remove();
            $('#Div_File .ajax-file-upload').hide();

            invoice_img = json_data.file;
            file_error = 1;

            $('#Div_File .status').empty();
            $('#Div_File .status').append('發票檔案已上傳 &nbsp;&nbsp;&nbsp;&nbsp;<span class="BU_Del_1" style="background-color:#2f8ab9;line-height:30px;padding:0px 4px;color:#fff;cursor:pointer;cursor:hand;">刪除</span>&nbsp;&nbsp;');
            $('#Div_File .status').addClass('statusDone');
            $('#Div_File span.BU_Del_1').click(function() {
                  file_error=0;
                  $('#Div_File .status').removeClass('statusDone');
                  $('#Div_File .status').empty();
                  $('#Div_File .ajax-file-upload').show();
                  obj.reset();
            });

         }
      }
   });


   //上傳圖片
   var obj2 = $("#Div_File2 #singleupload").uploadFile({
      url:"../insert.php",
      dynamicFormData: function() {
         var data ={ "func": 'uploadImg' }
         return data;
      },
      dragDrop:false,
      multiple:false,
      uploadStr:'<div id="BU_Update_Invoice" class="bu">保證卡上傳</div>',
      fileName:"myfile",
      maxFileSize:6291456, //6mb 6291456
      showFileCounter:false,
      allowedTypes:"jpg,jpeg,png",
      showDone:false,
      onSelect :function(files,data,xhr) {
         $('#Div_File2 .ajax-file-upload').hide();
         file2_error = 2; //上傳中
      },
      onError : function(files,data,xhr) {
         obj2.reset();
         $('#Div_File2 .ajax-file-upload').show();
         file2_error = 3; //上傳失敗
      },
      onSuccess:function(files,data,xhr) {
         json_data=$.parseJSON(data);
         if (json_data.file.length>0) {
            $('#Div_File2 .ajax-file-upload-statusbar').remove();
            $('#Div_File2 .ajax-file-upload').hide();

            guar_img = json_data.file;
            file2_error = 1;

            $('#Div_File2 .status').empty();
            $('#Div_File2 .status').append('保證卡檔案已上傳 &nbsp;&nbsp;&nbsp;&nbsp;<span class="BU_Del_1" style="background-color:#2f8ab9;line-height:30px;padding:0px 4px;color:#fff;cursor:pointer;cursor:hand;">刪除</span>&nbsp;&nbsp;');
            $('#Div_File2 .status').addClass('statusDone');
            $('#Div_File2 span.BU_Del_1').click(function() {
                  file2_error=0;
                  obj2.reset();
                  $('#Div_File2 .status').removeClass('statusDone');
                  $('#Div_File2 .status').empty();
                  $('#Div_File2 .ajax-file-upload').show();
            });

         }
      }
   });


   /*
   var $viewportMeta = $('meta[name="viewport"]');
   $('input, select').bind('focus blur', function(event) {
   $viewportMeta.attr('content', 'width=device-width,initial-scale=1,maximum-scale=' +        (event.type == 'blur' ? 10 : 1));
   });
   */
  //Tip
   $('#Content #ContentBody #Bu_Explain_SN').on('click',function() {
      $('#WinTip').fadeIn('fast');
      $('html,body').scrollTop(0);
      showExtraModel();
   });

   $('#WinTip #Img #BU_Close').on('click',function() {
      $('#WinTip').fadeOut('fast');
   });

   //Rule
   $('#Content #ContentBody #TnC span.bu').on('click',function() {
      $('#WinRule').fadeIn('fast');
      $('html,body').scrollTop(0,800);
   });

   $('#WinRule #Img #BU_Close').on('click',function() {
      $('#WinRule').fadeOut('fast');
   });

   $('#Bu_TnC').on('click',function() {
         if (check_tnc == 1) {
            $(this).removeClass('select');
            check_tnc = 0;
         } else {
            $(this).addClass('select');
            check_tnc = 1;
         }

   });

   $('#Content #Bu_Next').on('click',function() {
     if(next_count === 0){
       GEvent('內容區塊','按鈕',$(this).data('value'));
       next_count ++;
       checkData();
     }else{
       next_count ++;
       alert("您的資料已送出...請稍後!");
     }
   });
   $('#S_3 #GiftName .outerTable .outerMulti div').on('click',function(){
     if($('#S_3 #GiftName .outerTable .innerBox').hasClass('check')){
       $('#S_3 #GiftName .outerTable .innerBox').removeClass('check');
     }
     $(this).addClass('check');
   });
   $('#Content #Bu_Submit').on('click',function() {
     checkData();
     //saveData();
   });
}

function showExtraModel() {
  var haveOptions=0;
  $('#f_extra_model').empty();
  $('#f_extra_model').append('<option value="" selected>請選擇</option>');
  switch ($('#f_prod_model').val()) {
     case 'GW-BF380SV':
     case 'GW-BF386SV':
        $('#f_extra_model').append('<option value="GW-BF380SV" class="1">GW-BF380SV</option>');
        $('#f_extra_model').append('<option value="GW-BF386SV" class="1">GW-BF386SV</option>');
        haveOptions = 1;
        break;
     case 'GR-FL40SV':
     case 'GR-R40SV':
        $('#f_extra_model').append('<option value="GR-FL40SV" class="1">GR-FL40SV</option>');
        $('#f_extra_model').append('<option value="GR-R40SV" class="1">GR-R40SV</option>');
        haveOptions = 1;
        break;
   }
   if (haveOptions==1) {
      $('#f_extra_model').removeAttr('disabled');
   } else {
      $('#f_extra_model').attr('disabled','disabled');
   }
}

function checkData(){
   var check_format = 1; //格式檢查 0=空值， -1 =有誤
   var alertMsg = "";
   console.log(guar_img);
   name =$.trim($('#f_name').val());
   email= $.trim($('#f_email').val());
   mobile =  $.trim($('#f_mobile').val());
   phone = $.trim($('#f_phone').val());
   zipcode = $.trim($('.zipcode').val());
   address = $.trim($('#f_address').val());
   prod_type = $.trim($('#f_prod_type').val());
   prod_model = $.trim($('#f_prod_model').val());
   prod_sn_no = $.trim($('#f_prod_sn_no').val().toUpperCase());
   invoice_date = $.trim($('#f_invoice_date').val());
   invoice_total = $.trim($('#f_invoice_total').val());
   invoice_no = $.trim($('#f_invoice_no').val().toUpperCase());
   extra_model = $.trim($('#f_extra_model').val());
   extra_sn_no = $.trim($('#f_extra_sn_no').val().toUpperCase());
   buy_1 = $.trim($('#f_buy_1').val());
   buy_2 = $.trim($('#f_buy_2').val());
   buy_other = $.trim($('#f_buy_other').val());

   if($('#S_3 .innerBox').hasClass('check')){
     backGift = $.trim($('#S_3 .outerMulti .check').attr('value'));
     console.log(backGift);
   }
   if(name.length == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 姓名";
      check_format=0;
   }else{
     if (!ValidateName(name)) {
           alertMsg=checkAlertMsg(alertMsg);
           alertMsg=alertMsg+"- 姓名不可含數字";
           check_format=0;
        }
   }
   if(email.length == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 電子信箱";
      check_format=0;
   }else{
      if (!ValidateEmail(email)) {
            alertMsg=checkAlertMsg(alertMsg);
            alertMsg=alertMsg+"- 電子信箱欄位有誤";
            check_format=-1;
         }
   }

   //手機前兩碼09
   var prefix_mobile = mobile.substr(0,2);
   //市話規格
   var tel_format = /^[0-9]{2,3}\-[0-9]{5,8}$/;
   var mobile_format = /^09[0-9]{8}$/;
   var split_tel = phone.split('-');

   if (mobile.length > 0 || phone.length > 0) {
      if (mobile.length > 0) {
         if (prefix_mobile != '09' || mobile.length != 10 || !mobile_format.test(mobile)) {
            alertMsg = checkAlertMsg(alertMsg);
            alertMsg = alertMsg + "- 手機號碼有誤";
            check_format = 0;
         }
      }
      if (phone.length > 0) {
         if (!tel_format.test(phone)) {
            alertMsg = checkAlertMsg(alertMsg);
            alertMsg = alertMsg + "- 聯絡電話有誤";
            check_format = 0;
         }
      }
   } else {
      alertMsg = checkAlertMsg(alertMsg);
      alertMsg = alertMsg + "- 手機和聯絡電話須擇一填寫";
      check_format = 0;
   }


   if (address.length==0 || $('.county').val()=='' || $('.district').val()=='') {
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 聯絡地址";
         check_format=0;
      } else {
         if (address.isOnlyNumber()) {
            alertMsg=checkAlertMsg(alertMsg);
            alertMsg=alertMsg+"- 聯絡地址有誤";
            check_format=0;
         }
      }

   //產品資訊
   if(prod_type == ''){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 產品類型";
      check_format=0;
   }
   if(prod_model == ''){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 第一項產品型號";
      check_format=0;
   }
   if(prod_sn_no == ''){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 第一項產品機號";
      check_format=0;
   }else{
      if (ValidateProdno(prod_sn_no)==1) {
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 第一項產品機號有誤";
         check_format=0;
      }
   }

   //加購送
   if(extra_model == ''){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 第二項產品型號";
      check_format=0;
   }
   if(extra_sn_no == ''){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 第二項產品機號";
      check_format=0;
   }else{
      if (ValidateProdno(extra_sn_no) == 1) {
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 第二項產品機號有誤";
         check_format=0;
      } else {
         if (extra_sn_no==prod_sn_no) {
             alertMsg=checkAlertMsg(alertMsg);
             alertMsg=alertMsg+"- 第一項產品與第二項產品機號相同";
             check_format=0;
         }
      }
   }

   //購買資訊 如:日期，金額，發票號碼，通路
   //var arr = invoice_date.split('-');
   console.log(invoice_date);
   if(invoice_date.length == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 購買日期";
      check_format=0;
   }else{
     console.log(date_1 > new Date(invoice_date));
      if(date_1 > new Date(invoice_date) || new Date(invoice_date) > date_2){
         alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 購買日期不在活動時間內";
      check_format=0;
      }
   }
   if(invoice_total.length == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 購買金額";
      check_format=0;
   }else{
      if(!isNumeric(invoice_total)){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 購買金額均為數字";
         check_format=0;
      }
   }

   if(invoice_no.length == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 發票號碼";
      check_format=0;
   }else{
      if(!ValidateInvoice(invoice_no) || invoice_no.length < 10){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 發票號碼有誤";
         check_format=0;
      }
   }

   if(buy_1.length == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 購買通路-分類";
      check_format=0;
   }else if(buy_1 == 7 && buy_other.length == 0){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 購買通路-其他";
         check_format=0;
   }else if(buy_1 != 7 && buy_2.length == 0){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 購買通路-細項";
         check_format=0;
   }

   //購買證明
   if(file_error == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 購買發票證明";
      check_format=0;
   }else {
      if(file_error == 3){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 購買發票證明檔案過大";
         check_format=0;
      }

   }

   if(ValidateProdno(prod_sn_no) == 0 && file2_error == 0){
      alertMsg=checkAlertMsg(alertMsg);
      alertMsg=alertMsg+"- 購買保證卡證明";
      check_format=0;
   }else {
      if(file2_error == 3){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 購買保證卡檔案過大";
         check_format=0;
      }
   }

   //同意規範
   if(check_tnc == 0){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg=alertMsg+"- 同意個資之相關規範";
         check_format=0;
   }

   if(check_format == 1){
     replaceTable();
     if(backGift != ""){
       send_Data();
     }else{
       check_Duplicate();
     }
   }else {
      next_count = 0;
      alert('請確認以下欄位:\r\n\r\n'+alertMsg+'\r\n\r\n');
      return false;
   }
}

function check_Duplicate(){
   address = $('.county').val()+$('.district').val()+$('#f_address').val();
    var request = $.ajax({
               url: "../insert.php",
               type: "POST",
               data: {func : 'checkDuplicate', name:name, phone:phone, mobile:mobile, address:address,prod_type:prod_type, prod_model:prod_model,prod_sn_no:prod_sn_no,extra_model:extra_model,extra_sn_no:extra_sn_no},
               dataType: "json"
         });
         request.done(function(data) {
            var re_data = parseInt(data.ok, 10);

            if (re_data == 1) {
               send_Data();
            } else if (re_data == 0) {
               var prod_gift = data.gift.prod;
               var extra_gift = data.gift.extra;
               if (prod_gift != "") {
                  (prod_gift);
               }
               if (extra_gift != "") {
                  findImg(extra_gift);
               }
               alert("您所填寫的基本資料(姓名&電話&地址)以及產品機號已經登錄過了，所以無法重覆登錄！\n將顯示重覆登錄資料所得獎品頁面\n如有疑問，請洽詢LG活動小組專線0809-066-669");
               showS2();
            }
         });
}

function send_Data(){
   var request = $.ajax({
               url: "../insert.php",
               type: "POST",
               data: {func : 'saveData', name:name, phone:phone, mobile:mobile, zipcode:zipcode, address:address, email:email, invoice_no:invoice_no, invoice_total:invoice_total, invoice_date:invoice_date ,prod_type:prod_type, prod_model:prod_model,prod_sn_no:prod_sn_no, prod_buy_1:buy_1, prod_buy_2:buy_2, prod_buy_other:buy_other, img_invoice_img:invoice_img ,img_guar_img:guar_img,extra_model:extra_model,extra_sn_no:extra_sn_no,back_type:backType,back_gift:backGift},
               dataType: "json"
         });
         request.done(function(data) {
            var re_data = parseInt(data.ok, 10);
            var alertMsg = "";
            if (re_data >= 0) {
               findImg(data.gift);
            }

            if (re_data == -4) {
               alert('購買日期未在活動期間內，請再次確認！');
               next_count = 0;
               //return false;
            } else if (re_data == -3) {
               alert("組合購產品資訊不完全(有填組合購)");
               next_count = 0;
               //return false;
            } else if (re_data == -2 || re_data == -5) {
               alert("資料格式錯誤，請再次確認!")
               next_count = 0;
               //return false;

            } else if (re_data == -1) {
               alert('找不到組合贈品(無此組合)');
               //return false;
            } else if (re_data == -6) {
               alert("您所購買的產品不在指定的活動消費期間內！");
               next_count = 0;
            } else if (re_data == 1) {
               alert("資料已送出，恭喜登錄成功!");
               $("#S_2 .Title").text("感謝您參加登錄送活動!");
               showS2();
            } else if (re_data == 2) {
               alert("您所購買的主產品機號已經登錄過了!\n如有疑問，請洽詢LG活動小組專線0809-066-669");
               $("#S_2 .Title").text("感謝您參加登錄送活動!");
               showS2();
            } else if (re_data == 3) {
               alert("您所購買的組合產品機號已經登錄過了!\n如有疑問，請洽詢LG活動小組專線0809-066-669");
               $("#S_2 .Title").text("感謝您參加登錄送活動!");
               showS2();
            } else if (re_data == 4) {
               alert("您所購買的主產品機號及組合產品機號已經登錄過了!\n如有疑問，請洽詢LG活動小組專線0809-066-669");
               $("#S_2 .Title").text("感謝您參加登錄送活動!");
               showS2();
            } else if (re_data == 0) {
               alert("系統忙碌中，請稍候再進行資料登錄！");
               next_count = 0;
               //return false;
            }
         });
}

function showS2() {
   $('#S_1').hide();
   $('#S_3').hide();
   $('#Content').addClass('S_2');
   $('html,body').scrollTop(0);
   $('#S_2').fadeIn('slow');

}
function showS3($type) {
   $('#S_1').hide();
   $('#Content').addClass('S_2');
   if($type == 'type1'){
     $('#S_3 .outerPlus,#S_3 .outerBox').hide();
   }
   $('html,body').scrollTop(0);
   $('#S_3').fadeIn('slow');

}

function replaceTable(){
  var extraModel,extraNo,data_type;
  switch (parseInt(prod_type,10) | parseInt(extra_model,10)) {
    case 1:
       data_type = "冰箱";
       break;
    case 2:
      data_type = "洗衣機";
      break;
    case 3:
      data_type = "吸塵器";
      break;
    case 4:
      data_type = "電子衣櫥";;
      break;
      case 5:
        data_type = "電視";;
        break;
  }
  if(extra_model == ""){
    extraModel = '----';
  }else{
    extraModel = data_type;
  }
  if(extra_model == ""){
    extraNo = '----';
  }else{
    extraNo = extra_model;
  }
  $('#ResultContainer .tr').empty().append('<div class="td">'+data_type+'</div><div class="td">'+prod_model+'</div><div class="td">'+extraModel+'</div><div class="td">'+extraNo+'</div>');
}

function findImg($gift) {
   var giftImg;
   console.log($gift);
   switch ($gift) {
      case "7-11商品卡 NT2,000元":
         giftImg = "gift_1.jpg";
         break;
   }
   $('#S_2 #GiftName').empty().append('<div class="outerBox" ><div class="inBox" ><img src="img/' + giftImg + '"></div></div>');
}
//======================= Common
function replaceImg($gift){
   $gift = $gift.replace('7-11','<img src="img/gift_711.jpg">');
   return $gift;
}
String.prototype.isOnlyNumber = function(){return /^\d+$/.test(this);}
function isNumeric(n) {
      return !isNaN(parseFloat(n)) && isFinite(n);
}

function checkAlertMsg(msg) {
   if (msg.length>0) {
      msg=msg+"\r\n";
   }
   return msg;
}

function ValidateEmail(mail) {
   var reg =/^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
   if (reg.test(mail)) {
      return (true)
   } else {
      return (false)
   }
}

function ValidateInvoice(num) {
   var reg =/^[A-Z]{2}[0-9]{8}$/;
   if (reg.test(num)) {
      return (true)
   } else {
      return (false)
   }
}

function ValidateName(num){
   var reg =/[^0-9]/;
   if (reg.test(num)) {
      return (true)
   } else {
      return (false)
   }
}

function ValidateProdno(num) {
   var virtual = /^111MKT00(00[3-9]|[1-4]{1}[1-9]{2}|500)$/;
   var reg =/^[0-9]{3}[a-zA-Z]{3,4}[0-9a-zA-Z]{5,6}$/;
   var ok;

   if (reg.test(num)){
      if(virtual.test(num)) {
         ok = 2;
      }else{
         ok = 0;
      }
   } else {
      ok = 1;
   }
   return ok;
}
function GEvent(who) {
    var tmpStr = '【' + eventCode + '】(' + pageName + ') ';
    console.log(tmpStr + who);
    ga('send', 'event', 'button', 'click', tmpStr+who);
}
