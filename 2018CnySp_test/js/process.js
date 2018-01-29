var number;
$(function(){
    ini();
});
function ini() {
  var request_1 = $.ajax({
        url: "insert.php",
        type: "POST",
        data: {func : 'isActivity'},
        dataType: "json"
  });
  request_1.done(function(data) {
      over = parseInt(data.ok,10);
     switch (over) {   //活動期間
        case 1:
        case 2:
        case 3:
           $('#Content #Section #S_1 #Bu_Submit').on('click',function() {
              //GEvent('內容區塊','按鈕',$(this).data('value'));
              check_Status();
           });
            $('#Content #Section #S_1 #Bu_OfficalProcess').on('click',function() {
               window.open('http://www.lg.com/tw/support/product-registration','_blank');
            });
           break;
        case 4:
            alert("敬愛的LG顧客您好：\n因10/13 (四)將進行後台維護作業，\n建議您於10/14(五)再做登錄或查詢，造成不便，敬請見諒。");
          break;

     }
  });
}

function check_Status(){
   var check_format = 1; //格式檢查 0=空值， -1 =有誤
   var alertMsg = "";
   var tel_format = /[0-9]{2,3}\-[0-9]{5,8}/;
   var mobile_format = /09[0-9]{8}/;

   number = $('#p_phone').val();
   if(number.length !=0){
      if(!tel_format.test(number) && !mobile_format.test(number)){
         alertMsg=checkAlertMsg(alertMsg);
         alertMsg="請填寫正確填寫聯絡電話或手機";
         check_format=0;
       }
   }else{
      alertMsg='輸入的資料格式不符，須為「電話」或「手機」！';
      check_format=0;
   }
  if(check_format == 1){
      getStatus();
   }else {
      alert(alertMsg);
      return false;
   }
}

function getStatus(){
   var request =  $.ajax({
               url: "insert.php",
               type: "POST",
               data: {func : 'getStatus', tel:number},
               dataType: "json"
         });
         request.done(function(data) {
            $('#ResultContainer').empty().append('<div class="th"><div class="td">產品類型</div><div class="td">產品型號</div><div class="td">產品機號</div><div class="td">審核狀態</div></div>');
            var re_data = parseInt(data.ok,10);
            if(re_data == -2){
               alert('聯絡電話或手機號碼未填寫!');
            }else if(re_data == -1){
               alert('資料格式錯誤!');
            }else if(re_data == 0){
              alert('查無任何兌換登錄資料！\r\n請檢查所輸入之登錄的電話號碼是否正確.');
            }else if(re_data == 1){
              getDataCount=data.data.length;
               for (var i = 0; i < getDataCount; i++) {
                  var data_type;
                  var data_status;
                  var data_model = data.data[i].prod_model;
                  var data_sn_no = data.data[i].prod_sn_no;

                  switch(parseInt(data.data[i].prod_type)){
                    case 1:
                       data_type = "冰箱";
                       break;
                  }

                  switch(parseInt(data.data[i].check_status)){
                     case 0:
                        data_status = "審查中";
                        break;
                     case 1:
                        data_status = "審查中";
                        break;
                     case 2:
                        data_status = "審核通過";
                        break;
                     case 3:
                        data_status = "審核不通過";
                        break;
                     case 4:
                        data_status = "贈品已寄出";;
                        break;
                  }
                  $('#ResultContainer').append(' <div class="tr"><div class="td">'+data_type+'</div><div class="td">'+data_model+'</div><div class="td">'+data_sn_no+'</div><div class="td">'+data_status+'</div></div>');

                  if (i<getDataCount-1) {
                     $('#ResultContainer').append('<div class="tr"><div class="td"><hr></div><div class="td"><hr></div><div class="td"><hr></div><div class="td"><hr></div></div>');
                  }
                }

                showS2();
            }
         });
}

function showS2() {
   $('#Content #Section > #S_1 input').val('');
   $('#Content #Section > div').hide();
   $('#Content #Section > #S_2').fadeIn('fast');
}

function checkAlertMsg(msg) {
   if (msg.length>0) {
      msg=msg+"\r\n";
   }
   return msg;
}
/*
function GEvent(who) {
    var tmpStr = '【' + eventCode + '】(' + pageName + ') ';
    console.log(tmpStr + who);
    ga('send', 'event', 'button', 'click', tmpStr+who);
}
*/
