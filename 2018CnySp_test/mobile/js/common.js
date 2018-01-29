var RuleTabNumber=6;
var siteName = location.href.split('/').filter(function(el) {
    return el.trim().length > 0;
}).pop(); //活動目錄名稱
var pageName = (window.location.pathname.split("/").pop() === '') ? 'index' : window.location.pathname.split("/").pop().split(".").shift();
var windowWidth = 0,
    windowHeight = 0;
var isMobile = (navigator.userAgent.match(/Android|iPhone|iPad/i) === null) ? false : true;
var reHttp = /^http/i;
var eventCode= "";
var home,rule,login,processes,product,winner,faq,extra1,extra2,rule_title;
var i, j;

$(function() {
    if (!isMobile) {
        window.location.href = "../";
    } else {
        //RedirNonHttps();
        getWindowSize();

        //Common
        $('body').attr('page', pageName);
        viewPort(1200);

        //Get Event Info
        $.post('../func.php', {
            func: 'getEvent'
        }, function(data) {
          if (data.event.length === 1) { //有找到活動
              $('#Mask').hide();
               eventCode = data.eventcode;
               $('#Header #Menu').empty().append('<ul>');
               for(var k = 0; k < data.event[0].menu.length;k++){
                if(data.event[0].menu[k].show === "1"){
                   if(data.event[0].menu[k].title === "product" || data.event[0].menu[k].title === "extra2"){
                    $('#Header .container #Menu ul').append('<li data-window="'+data.event[0].menu[k].window+'" data-link="'+data.event[0].menu[k].url+'"><span>'+data.event[0].menu[k].name+'</span></li>');
                   }else{
                     $('#Header .container #Menu ul').append('<li data-link="'+data.event[0].menu[k].title+'"><span>'+data.event[0].menu[k].name+'</span></li>');
                   }
                   switch (data.event[0].menu[k].main) {
                     case 'home':
                      home = k;
                     break;
                     case 'rule':
                      rule = k;
                     break;
                     case 'login':
                      login = k;
                     break;
                     case 'process':
                      processes = k;
                     break;
                     case 'product':
                      product = k;
                     break;
                     case 'winner':
                      winner = k;
                     break;
                     case 'faq':
                      faq = k;
                     break;
                     case 'extra1':
                      extra1 = k;
                     break;
                     case 'extra2':
                      extra2 = k;
                     break;
                   }
                 }
               }
               $('#Header .container #Menu ul').append('<li data-link="https://www.facebook.com/LGTaiwan/"><span>LG粉絲團</span></li>');
                //Menu
                $('#Header #Menu li').on('click', function() {
                    if ($(this).is(":last-child")) { //FB
                        GEvent('Menu-FB');
                        window.open($(this).data('link'), '_blank');
                    } else {
                        GEvent('Menu-' + $(this).text());
                        if ($(this).data('link').match(reHttp)) { //外部連結
                          if($(this).data('window') == "1"){
                            window.open($(this).data('link'), '_blank');
                          }else{
                              window.open($(this).data('link'), '_self');
                          }
                        } else {
                            switch ($(this).data('link')) {
                                case 'login': //===> 呼叫子選單
                                    if ($('#Header .container #Menu li:nth-child(3) .subMenu').hasClass('show')) {
                                       $('#Header .container #Menu li:nth-child(3) .subMenu').removeClass('show').stop().fadeOut(100);
                                    } else {
                                       $('#Header .container #Menu li:nth-child(3) .subMenu').addClass('show').stop().fadeIn(800);
                                    }
                                    break;
                                case 'process':
                                    //alert('Commin soon');
                                    window.location.href = $(this).data('link') + '.html';
                                    break;
                                case 'winner':
                                    if (toNumber(data.event[0].menu[winner].show) === 1) {
                                      if(!(data.event[0].menu[winner].msg==="")){
                                          alert(data.event[0].menu[winner].msg);
                                      }else{
                                        window.location.href = $(this).data('link') + '.html';
                                      }
                                    }
                                    break;
                                default:
                                    if ($(this).data('link').length > 0) {
                                        window.location.href = $(this).data('link') + '.html';
                                    }
                                    break;
                            }
                        }
                    }
                });

                if (pageName === 'index') {
                    $('#Content .container #EventImg').empty();
                    $('#Content .container #EventImg').append('<img src="../../../img/' + data.event[0].mobile_img + '" />');
                    $('#Content .container #BU_Rule ').on('click', function() {
                       GEvent('Index:rule');
                       window.location.href="rule.html";
                    });
                }

                if (pageName === 'rule' || pageName === 'winner') {
                    $.post('../func.php', {
                        func: 'getPage',
                        type: pageName
                    }, function(data) {
                        var objTabs = data.info;

                        $('#Content .container #SubMenu').empty();
                        $.each(objTabs, function(key, value) {
                          if(pageName === 'rule'){
                            switch(value.title){
                              case '新品':
                                rule_title = "new";
                              break;
                              case '電視':
                                rule_title = "tv";
                              break;
                              case '冰箱':
                                rule_title = "ref";
                              break;
                              case '洗衣機 / 其它':
                                rule_title = "other";
                              break;
                              case '買就送':
                                rule_title = "gift";
                              break;
                              case '買再登錄抽':
                                rule_title = "lottery";
                              break;
                            }
                              $('#Content .container #SubMenu').append('<li class="bu unselect" style="background-color:' + value.color + '" data-title="'+rule_title+'" data-img="'+value.mobile_img+'"><p>' + value.title + '</p></li>');
                          }else{
                            $('#Content .container #SubMenu').append('<li class="bu unselect" style="background-color:' + value.color + '" data-img="'+value.mobile_img+'"><p>' + value.title + '</p></li>');
                          }
                        });

                        $('#Content .container #SubMenu li, #Content .container #SubMenu li p').css({
                            'width': Math.floor($('.container').width() / ((objTabs.length > RuleTabNumber) ? RuleTabNumber : objTabs.length) - 1)
                        });

                        $('#Content .container #SubMenu li:last-child').css({
                            'margin-right': '0',
                            'width': ($('#Content .container #SubMenu li:last-child').width() + 1) + 'px'
                        });

                        if (objTabs.length > RuleTabNumber) {  //斷行
                           for (i=1;i<Math.ceil(objTabs.length/RuleTabNumber);i++) {
                             $("<br/>").insertAfter('#Content .container #SubMenu li:nth-child(' + (objTabs.length - RuleTabNumber*i) + ')');
                           }
                        } else {
                            $('#Content .container #SubMenu li').css('margin-bottom', '-2px');
                        }

                        //default rule-1
                        $('#Content .container #SubMenu li:first-child').removeClass('unselect').addClass('select');
                        $('#Content .container #Img').empty();
                        $('#Content .container #Img').append('<img src="../../../img/' + $('#Content .container #SubMenu li:first-child').data('img') + '"/>');
                        if (toNumber($('#Content .container #Link').data('page'))===5) {  //defautl rule-1 page
                          $('#Content .container #Link').addClass('show');
                          $("#Content .container #Link #Link_4").on('click',function(){
                            $('#remind').show();
                            remindShow();
                          });
                        }

                        //submenu event Handler
                        $('#Content .container #SubMenu li').on('mouseenter mouseleave click ', function(e) {
                            switch (e.type) {
                                case 'mouseenter':
                                    if (!$(this).hasClass('select')) {
                                        $(this).removeClass('unselect');
                                    }
                                    break;
                                case 'mouseleave':
                                    if (!$(this).hasClass('select')) {
                                        $(this).addClass('unselect');
                                    }
                                    break;
                                case 'click':
                                    $('#Content .container #SubMenu li').removeClass('select').addClass('unselect');                                    
                                    $(this).removeClass('unselect').addClass('select');


                                    //活動辦法下拉選單
                                    if($(this).hasClass('sub')){
                                      $('.sss').stop().slideUp(300);
                                      $(this).removeClass('sub');
                                    }else{
                                      $('.sss').stop().slideUp(300);
                                      $('#Content .container #SubMenu li').removeClass('sub');
                                      $(this).children('.sss').stop().slideDown(300);
                                      $(this).addClass('sub');
                                    }

                                    $('#Content .container #Img').empty();
                                    $('#Content .container #Img').append('<img src="../../../img/' + $(this).data('img') + '"/>');

                                    //Link
                                    $('#Content .container #Link').removeClass('show');
                                    $('#Content .container #Link[data-page='+$(this).index()+']').addClass('show');
                                    GEvent('Tab-' + $(this).text());
                                    break;
                            }
                        });

                        if(pageName === 'rule'){
                          $('#Content .container #SubMenu li:nth-child(3)').append('<div class="sss"><span class="clasic-refrigerator">一般<br>冰箱</span><span class="slim-refrigerator">窄版<br>冰箱</span></div>');
                          $('#Content .container #SubMenu li:nth-child(4)').append('<div class="sss"><span class="clasic-wash">一般<br>洗衣機</span><span class="twin-wash">TwinWash<br>雙能洗</span></div>');
                          $('#Content .container #SubMenu li:last-child').append('<div class="sss"><span>電子<br>衣櫥</span><span class="air">空氣<br>清淨機</span><span class="dry">變頻<br>除濕機</span></div>');
                        }



                        if(getUrlParam('spec')){
                          var $para = getUrlParam('spec');
                          $('#Content .container #SubMenu li').removeClass('select').addClass('unselect');
                          $('#Content .container #SubMenu li[data-title="'+$para+'"]').removeClass('unselect').addClass('select');

                          $('#Content .container #Img').empty();
                          $('#Content .container #Img').append('<img src="../../../img/'+$('#Content .container #SubMenu li[data-title="'+$para+'"]').data('img')+'"/>');
                          //Link
                          if (toNumber($('#Content .container #Link').data('page'))===toNumber($('#Content .container #SubMenu li[data-title="'+$para+'"]').index())) {
                             $('#Content .container #Link').addClass('show');
                          } else {
                             $('#Content .container #Link').removeClass('show');
                          }
                        }


                        $('.clasic-refrigerator').click(function(e) {
                          e.preventDefault();
                          $('html,body').animate({
                            scrollTop: $('#clasic-refrigerator').offset().top - 50
                          }, 800);                        
                        });

                        $('.slim-refrigerator').click(function(e) {
                          e.preventDefault();
                          $('html,body').animate({
                            scrollTop: $('#slim-refrigerator').offset().top - 50
                          }, 800);                        
                        });

                        $('.clasic-wash').click(function(e) {
                          e.preventDefault();
                          $('html,body').animate({
                            scrollTop: $('#clasic-wash').offset().top - 50
                          }, 800);                        
                        });

                        $('.twin-wash').click(function(e) {
                          e.preventDefault();
                          $('html,body').animate({
                            scrollTop: $('#twin-wash').offset().top - 50
                          }, 800);                        
                        });

                        $('.air').click(function(e) {
                          e.preventDefault();
                          $('html,body').animate({
                            scrollTop: $('#air').offset().top - 50
                          }, 800);                        
                        });

                        $('.dry').click(function(e) {
                          e.preventDefault();
                          $('html,body').animate({
                            scrollTop: $('#dry').offset().top - 50
                          }, 800);                        
                        });

                    }, 'json');
                }


                
                if (pageName === 'faq' || pageName === 'fbcheckin') {
                    $.post('../func.php', {
                        func: 'getPage',
                        type: pageName
                    }, function(data) {
                        $('#Content .container #Img').empty();
                        $('#Content .container #Img').append('<img src="../../../img/' + data.info[0].mobile_img + '"/>');

                        if(pageName === 'fbcheckin'){
                            $('#Content .container #BU_Login_rule').on('click', function() {
                                  window.open("https://www.facebook.com/LGTaiwan/posts/1252733401406491");
                            });
                        }
                    }, 'json');
                }

                //SubMenu
                $('#Header .container #Menu li:nth-child(3)').append('<div class="subMenu"><span>官網登錄</span><span>窄版冰箱組合送登錄</span></div>');
                $('#Header .container #Menu li:nth-child(3) .subMenu span').on('click', function() {
                   if ($(this).index()===0) {
                     GEvent('Submenu:www.lg.com/tw/support/product-registration')
                     window.open('http://www.lg.com/tw/support/product-registration','_blank');
                   } else {
                     GEvent('Menu:Submenu:login')
                     //alert('目前無法進行兌換登入！');
                     window.location.href="login.html";;
                  }
                });
            } else { //沒有這個活動 ==> 導回 Landing pageName
                window.location.href = "/";
            }
        }, 'json');

        $('#Header #Menu li[data-link=' + pageName + ']').addClass('select');

        $('#Header #Logo').on('click', function() {
            GEvent('Logo');
            window.open('http://www.lg.com/tw', '_blank');
        });

        $('#Header #NavIcon').on('click', function() {
            if ($('#Header').hasClass('open')) { //CLOSE
                $('html,body').css('overflow','auto');
                $('#Header').removeClass('open');
            } else { //OPEN
                $('html,body').css('overflow','hidden');
                $('#Header').addClass('open');
            }
        });

        //Adjust the menu ==> if menu overheight browser height
        if (($(window).height() - $('#Header').height()) > $('#Menu ul').actual('height')) {
            $('#Header #Menu ul li span').css('height', '6rem');
        }
    }


});

function viewPort($targetWidth) {
	var deviceWidth = screen.width;
	var ratio = deviceWidth / $targetWidth;
	var viewport = document.querySelector('meta[name="viewport"]');
	if (ratio > 1.4)
		viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, user-scalable=yes');
	else if(ratio > 1)
		viewport.setAttribute('content', 'width=' + $targetWidth + ', initial-scale=' + ratio + ', minimum-scale=' + ratio + ', maximum-scale=' + ratio + ', user-scalable=yes');
	else
		viewport.setAttribute('content', 'width=device-width, initial-scale=' + ratio + ', minimum-scale=' + ratio + ', maximum-scale=' + ratio + ', user-scalable=yes');
}

function getWindowSize() {
    windowWidth = $(window).width();
    windowHeight = $(window).height();
}
function remindShow(){
  $('#remind #popup #scollInfo').jScrollPane();
  $('#remind').show();
  $("#remind #popup #scollInfo #scroll_Close").on('click',function(){
    $('#remind').hide();
  });
}
function toNumber(strNumber) {
    return +strNumber;
}

function RedirNonHttps() {
    if (location.href.indexOf("https://") == -1) {
        location.href = location.href.replace("http://", "https://");
    }
}
function getUrlParam(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg); //匹配目标参数
    if (r !== null) return unescape(r[2]);
    return null; //返回参数值
}
function GEvent(who) {
    var tmpStr = '【' + eventCode + '】(' + pageName + ') ';
    console.log(tmpStr + who);
    ga('send', 'event', 'button', 'click', tmpStr+who);
}
