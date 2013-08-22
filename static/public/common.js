"use strict";

(function($){
  var loadPage_status = 0;

  $.fn.extend({
    /**
     * Load page
     * 
     * @param object params
     * {
     * 	tpl: template url, // prefix is static/templates/, suffix is .html
     * 	data_url: data url,
     * 	type: 'replace/append', // default replace
     * 	data: {},
     * 	callback: callback function
     * }
     * @return void
     */
    loadPage: function(params) {
                if (loadPage_status == 1) return;
                if (!params || !params instanceof Object || params instanceof Array) {
                  return null;
                }

                params.tpl = 'static/templates/' + params.tpl + '.html';
                //document.title = params.tpl;
                var $this = $(this);

                //CORE.loading();

                if (LANG._length === 0) {
                  LANG._load('public');
                }

                $.xhrPool.abortAll();

                // load template
                $.ajax({
                  url: params.tpl,
                  type: 'get',
                  cache: true,
                  error: function(){
                    loadPage_status == 0;
                    //CORE.removeLoading();
                    return null;
                  },
                  success: function(tpl) {
                             if (params.data && params.data instanceof Object && !params.data instanceof Array) {
                               params.data = {};
                             }
                             tpl = tpl.split('<#script#').join('<script').split('<#/script#>').join('</script>');
                             if (!params.data_url) {
                               if (!params.type instanceof String || params.type != 'append') {
                                 $this.empty();
                               }
                               $.template( 'tpl', tpl);
                               $.tmpl('tpl', params.data).appendTo($this);
                               if (params.callback instanceof Function) {
                                 params.callback();
                               }
                               loadPage_status == 0;
                               //CORE.removeLoading();
                               return true;
                             }

                             // load data
                             $.ajax({
                               url: params.data_url,
                               type: 'get',
                               dataType: 'json',
                               error: function(){
                                 //CORE.removeLoading();
                                 return null;
                               },
                               success: function(response){
                                          loadPage_status == 0;
                                          //CORE.removeLoading();
                                          if (!response || !response instanceof Object) {
                                            return null;
                                          }
                                          if (!response.status) {
                                            return response.msg;
                                          }
                                          var data = response.data;
                                          if (!data || !data instanceof Object) {
                                            return null;
                                          }
                                          if (!params.type instanceof String || params.type != 'append') {
                                            $this.empty();
                                          }
                                          $.template( 'tpl', tpl);
                                          response = $.extend(params.data, response);
                                          $.tmpl('tpl', response).appendTo($this);	
                                          if (params.callback instanceof Function) {
                                            params.callback();
                                          }
                                          return true;
                                        }
                             });
                           }
                })	
              },
      /**
       * Load image
       * 
       * @param string onErrorIMG
       * @param integer width
       * @param integer height
       * @return void
       */
      loadImg: function(onErrorIMG, width, height){
                 // fix opera bug
                 if (!$.browser.opera && this.complete) return;
                 var alt = $(this).attr('alt');
                 var src = $(this).attr('src');
                 // set width/height
                 if (typeof width == 'number' && typeof height == 'number') {
                   $(this).css({
                     'width': width,
                     'height': height
                   });
                 }
                 $(this).attr({
                   'alt':'',
                   'src':'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='
                 });
                 $(this).css('background', 'url(static/public/loading_50x50.gif) center no-repeat');
                 $(this).attr('source', src);
                 var img = document.createElement('img');
                 $(img).one('load', function(){
                   $(this).css({
                     'background':'',
                     'opacity':0
                   }).attr({
                     'alt':alt,
                   'src':src
                   }).animate({
                     'opacity':1
                   },'fast').removeAttr('source');
                 }).bind('error abort', function(){
                   src = onErrorIMG;
                   $(this).trigger('load');
                 });
                 img.src = src;
                 if (img.complete) $(img).trigger('load');	
               },
      placeHolder: function() {
                     if (window.navigator.appName == 'Microsoft Internet Explorer') {
                       $(this).each(function () {
                         var obj = $(this);

                         if (obj.attr('placeholder') != '') {
                           obj.addClass('IePlaceHolder');

                           if ($.trim(obj.val()) == '' && obj.attr('type') != 'password') {
                             obj.val(obj.attr('placeholder'));
                           }
                         }
                       });

                       $('.IePlaceHolder').focus(function () {
                         var obj = $(this);
                         if (obj.val() == obj.attr('placeholder')) {
                           obj.val('');
                         }
                       });

                       $('.IePlaceHolder').blur(function () {
                         var obj = $(this);
                         if ($.trim(obj.val()) == '') {
                           obj.val(obj.attr('placeholder'));
                         }
                       });
                     }
                   }
  });

  $.xhrPool = [];
  $.xhrPool_extend = [];
  $.xhrPool.abortAll = function() {
    $(this).each(function(idx, jqXHR) {
      if (!$.xhrPool_extend[idx].unabort) {
        jqXHR.abort();
        $.xhrPool.splice(idx, 1);
        $.xhrPool_extend.splice(idx, 1);
      }
    });
    //$.xhrPool.length = 0;
    //$.xhrPool_extend = [];
  };

  $.ajaxSetup({
    beforeSend: function(jqXHR) {
                  CORE.loading();
                  $.xhrPool.push(jqXHR);
                  $.xhrPool_extend.push({url:this.url, unabort:this.unabort||false});
                },
    error: function(jqXHR) {
             var index = $.xhrPool.indexOf(jqXHR);
             if (index > -1) {
               $.xhrPool.splice(index, 1);
               $.xhrPool_extend.splice(index, 1);
             }
             if ($.xhrPool.length == 0) {
               CORE.removeLoading();
             }
           },
    complete: function(jqXHR) {
                if (typeof jqXHR != 'undefined' && typeof jqXHR.responseText != 'undefined'
                  && jqXHR.responseText.indexOf(CORE.UNLOGGEDIN_WORD) > -1) {
                  location = CORE.SSO_URL + '/user/login?referer='+encodeURI(location.href);
                }
                var index = $.xhrPool.indexOf(jqXHR);
                if(index > -1) {
                  $.xhrPool.splice(index, 1);
                  $.xhrPool_extend.splice(index, 1);
                }
                if ($.xhrPool.length == 0) {
                  CORE.removeLoading();
                }
              }
  });


  // Functions
  $.extend({
  });
})(jQuery);

var matched, browser;
jQuery.uaMatch = function( ua ) {
  ua = ua.toLowerCase();

  var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
    /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
    /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
    /(msie) ([\w.]+)/.exec( ua ) ||
    ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
    [];

  return {
    browser: match[ 1 ] || "",
      version: match[ 2 ] || "0"
  };
};
if ( !jQuery.browser ) {
  matched = jQuery.uaMatch( navigator.userAgent );
  browser = {};

  if ( matched.browser ) {
    browser[ matched.browser ] = true;
    browser.version = matched.version;
  }

  // Chrome is Webkit, but Webkit is also Safari.
  if ( browser.chrome ) {
    browser.webkit = true;
  } else if ( browser.webkit ) {
    browser.safari = true;
  }

  jQuery.browser = browser;
}

/*!
 * jQuery Cookie Plugin v1.3.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2013 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as anonymous module.
    define(['jquery'], factory);
  } else {
    // Browser globals.
    factory(jQuery);
  }
}(function ($) {

  var pluses = /\+/g;

  function raw(s) {
    return s;
  }

  function decoded(s) {
    return decodeURIComponent(s.replace(pluses, ' '));
  }

  function converted(s) {
    if (s.indexOf('"') === 0) {
      // This is a quoted cookie as according to RFC2068, unescape
      s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
    }
    try {
      return config.json ? JSON.parse(s) : s;
    } catch(er) {}
  }

  var config = $.cookie = function (key, value, options) {

    // write
    if (value !== undefined) {
      options = $.extend({}, config.defaults, options);

      if (typeof options.expires === 'number') {
        var days = options.expires, t = options.expires = new Date();
        t.setDate(t.getDate() + days);
      }

      value = config.json ? JSON.stringify(value) : String(value);

      return (document.cookie = [
          config.raw ? key : encodeURIComponent(key),
          '=',
          config.raw ? value : encodeURIComponent(value),
          options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
          options.path    ? '; path=' + options.path : '',
          options.domain  ? '; domain=' + options.domain : '',
          options.secure  ? '; secure' : ''
          ].join(''));
    }

    // read
    var decode = config.raw ? raw : decoded;
    var cookies = document.cookie.split('; ');
    var result = key ? undefined : {};
    for (var i = 0, l = cookies.length; i < l; i++) {
      var parts = cookies[i].split('=');
      var name = decode(parts.shift());
      var cookie = decode(parts.join('='));

      if (key && key === name) {
        result = converted(cookie);
        break;
      }

      if (!key) {
        result[name] = converted(cookie);
      }
    }

    return result;
  };

  config.defaults = {};

  $.removeCookie = function (key, options) {
    if ($.cookie(key) !== undefined) {
      $.cookie(key, '', $.extend(options, {
        expires: -1
      }));
      return true;
    }
    return false;
  };

}));

var CORE = {}, LANG = {
  _length: 0,
  _type: $.cookie('_l_') || 'zh-cn',
  _module: [],
  _load: function(lang) {
    if (-1 != $.inArray(lang, LANG.module)) return;
    $.ajax({
      url: 'lang/'+LANG._type+'/'+lang+'.json',
      type: 'get',
      async: false,
      cache: true,
      dataType: 'json',
      success: function(json) {
        $.extend(LANG, json);
        LANG._length += (function(obj){
          var i = 0;
          for(var j in obj){
            i++;
          }
          return i;
        })(json);
        LANG._module.push(lang);
      }
    });
  }
};

(function(C, $){
  C.loading = function() {
    $('#loading').show();
  }
  C.removeLoading = function() {
    $('#loading').fadeOut();
  }
  C.checkLogged = function() {
    $.ajax({
      url: '/?action=user&trick=checkLogged',
      type: 'GET',
      success: function(response){  
        var tpl = 'index';
        var type = 'append';
        if (!response.status) {
          location = C.SSO_URL + '?referer=' + encodeURIComponent(location.href);
          return;
        }

        C.is_parent = response.data.is_parent;
        $('body').loadPage({
          tpl: tpl,
          type: type,
          callback:function(){
            $("#inputAccount").trigger("focus");
            $("#newpwd").trigger("focus");
          }

        });
      }
    });
    return false;
  }


  C.isEmail = function(str) {
    return /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i.test(str);
  }

  C.search = function(type, keyword) {
    var TYPE = type.toUpperCase();
    if (typeof window[type] == 'undefined') {
      $.getScript('static/index/js/'+type+'.js').done(function(){
        window[TYPE][type+'_list']({
          keyword:keyword
        });
      });
    } else {
      window[TYPE][type+'_list']({
        keyword:keyword
      });
    }
  }

  C.tableFixedHF = function (table, subHeight) {
    $('#'+table+'>tbody>tr>td').css('vertical-align','middle');
    $('#'+table).css({
      'margin': 0, 
      'border-width': '0 1px 1px 1px',
      'border-radius': 0
    });

    /*clone to head*/
    $('#'+table).parent().before('<table></table>');
    $('#'+table).parent().prev('table').attr({
      'id': table+'-header', 
      'class': $('#'+table).attr('class'), 
      'style': 'margin-bottom:0;border-bottom-left-radius:0;border-bottom-right-radius: 0;'
    }); 

    $('#'+table+'>thead').clone().appendTo('#'+table+'-header');
    $('#'+table+'-header>thead>tr>th:first').css('border-bottom-left-radius', 0);
    $('#'+table+'-header>thead>tr>th:last').css('border-bottom-right-radius', 0);
    $('#'+table+'>thead').remove();

    /*clone to foot*/
    $('#'+table).parent().after('<table></table>');
    $('#'+table).parent().next('table').attr({
      'id': table+'-footer', 
      'class': $('#'+table).attr('class'), 
      'style': 'margin:0;border-top:0;border-top-left-radius:0;border-top-right-radius: 0;'
    }); 
    if ($('#'+table+'>tfoot').length > 0) {
      $('#'+table+'>tfoot').clone().appendTo('#'+table+'-footer');
      $('#'+table+'-footer>tfoot>tr>td').css('border-top',0);
      $('#'+table+'-footer>tfoot>tr>td:first').css('border-top-left-radius', 0);
      $('#'+table+'-footer>tfoot>tr>td:last').css('border-top-right-radius', 0);
      $('#'+table+'>tfoot').remove();
    } else {
      $('#'+table+'-footer').css('border', 0);	
    }

    $('#'+table+'>tbody>tr:eq(0)').children('td').eq(0).css('border-top-left-radius', 0);
    $('#'+table+'>tbody>tr:eq(0)').children('td').eq(':last').css('border-top-right-radius', 0);
    $('#'+table+'>tbody>tr:last').children('td').eq(0).css('border-bottom-left-radius', 0);
    $('#'+table+'>tbody>tr:last').children('td').eq(':last').css('border-bottom-right-radius', 0);

    $(window).bind('resize', function(){
      var $parent = $('#'+table).parent();
      subHeight = subHeight||0;
      $parent.css('max-height', $('#frame-body').height()-subHeight);
      $('#'+table).css('border-right-width', $parent.height()>=$('#'+table).height()? '1px': 0);
      var ths = $('#'+table+'-header>thead>tr:eq(0)').children('th'); 
      var tds = $('#'+table+'>tbody>tr:eq(0)').children('td').not(':last');
      var len = tds.length;
      for (var i = 0; i < len; i++){
        $(tds[i]).width($(ths[i]).width());
      }
    }).trigger('resize');
  }

  C.lastSelectedVal = function(className) {
    var	$selects = $('.'+className);
    var len = $selects.length;
    var val = 0;
    for	(var i = len-1; i >= 0; i--) {
      var curval = $($selects[i]).val();
      if (curval && curval != "0") {
        val = curval;
        break;
      }
    }
    return val;
  }

  C.htmlspecialchars = function(str) {
    if (typeof(str) == "string") {
      str = str.replace(/&/g, "&amp;");
      str = str.replace(/"/g, "&quot;");
      str = str.replace(/'/g, "&#039;");
      str = str.replace(/</g, "&lt;");
      str = str.replace(/>/g, "&gt;");
    }
    return str;
  }

  C.htmlspecialchars_decode = function(str) {
    if (typeof(str) == "string") {
      str = str.replace(/&gt;/ig, ">");
      str = str.replace(/&lt;/ig, "<");
      str = str.replace(/&#039;/g, "'");
      str = str.replace(/&quot;/ig, '"');
      str = str.replace(/&amp;/ig, '&');
    }
    return str;
  }


  C.ucfirst = function(str) {
    if (typeof str != 'string') return '';
    str = str.toLowerCase();
    str = str.replace(/\b\w+\b/, function(word){
      return word.substring(0,1).toUpperCase()+word.substring(1);
    });
    return str; 
  }

  C.is_parent = false;
})(CORE, jQuery);
