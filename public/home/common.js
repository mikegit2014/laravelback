/**
 * 命名空间
 * @type {Object}
 */
var org = {};
org.Common = {};
org.Online = {};

/**
 * 处理表单提单按钮，显示loading，禁用，启用。
 * @return {[type]} [description]
 */
org.Common.setformSubmitButton = function() {
    //模拟submit
    $(document).on('click', '.sys-btn-submit', function () {
        var isClick = false;
        var sysBtnSubmitObject = $(this);
        var has_Object_Form_Init = sysBtnSubmitObject.data('form-init') || false;
        var has_Object_Body_Init = sysBtnSubmitObject.data('body-init') || false;
        
        var oldText = sysBtnSubmitObject.find('.sys-btn-submit-str').html();

        //处理表单提交
        if( ! has_Object_Form_Init) {
            sysBtnSubmitObject.closest('form').submit(function(){
                var loading = sysBtnSubmitObject.attr('data-loading') || 'loading...';
                sysBtnSubmitObject.find('.sys-btn-submit-str').html(loading);
                sysBtnSubmitObject.attr('disabled', 'disabled');
                isClick = true;
            });
            sysBtnSubmitObject.data('form-init', true);
        }

        //取消按钮锁定
        if( ! has_Object_Body_Init) {
            $("body").on('click', function(){
                if(isClick){
                    sysBtnSubmitObject.removeAttr('disabled');
                    sysBtnSubmitObject.find('.sys-btn-submit-str').html(oldText);
                }
                isClick = false;
            });
           sysBtnSubmitObject.data('body-init', true);
        }

        sysBtnSubmitObject.closest('form').submit();

        return false;
    });
    
}

/**
 * 自定义的confirm确认弹出窗口
 * 
 * @param  string   content  提示的内容
 * @param  function callback 回调函数
 * @return void
 */
org.Common.confirm = function(content, callback) {
    var d = dialog({
        title: '提示',
        content: content,
        okValue: '确定',
        width: 250,
        ok: function () {
            if(typeof callback === 'function') {
                this.title('提交中…');
                callback();
            }
        },
        cancelValue: '取消',
        cancel: function () {}
    });
    d.showModal();
}

/**
 * 自定义的alert提示弹窗
 * 
 * @param  string content 提示的内容
 * @return void
 */
org.Common.alert = function(content) {
    var d = dialog({
        title: '提示',
        content: content,
        okValue: '确定',
        width: 250,
        ok: function () {}
    });
    d.showModal();
}

/*!
 * uuid key
 * @type {String}
 */
org.Online.UuidKey = 'uuid';

/*!
 * set uuid cookie
 */
org.Online.SetUuidCookie = function() {
    var uuid = Math.uuid();
    var uuidCookie = $.cookie(org.Online.UuidKey);
    if(typeof uuidCookie == 'undefined') {
        $.cookie(org.Online.UuidKey, uuid, { path: '/', domain: DOT_DOMAIN });
    }
}

/**
 * websocket 对象
 * @type {Object}
 */
org.Online.Ws = {};

/**
 * 处理当前在线人数
 */
org.Online.InitSocket = function() {
    if (window.WebSocket || window.MozWebSocket) {
        org.Online.Ws = new WebSocket('ws://'+swoole_online_config.onlineLitenIp+':'+swoole_online_config.onlineLitenPort);
        org.Online.SocketListen();
    } else {
        WEB_SOCKET_SWF_LOCATION = "/lib/flash-websocket/WebSocketMain.swf";
        $.getScript("/lib/flash-websocket/swfobject.js", function () {
            $.getScript("/lib/flash-websocket/web_socket.js", function () {
                org.Online.Ws = new WebSocket('ws://'+swoole_online_config.onlineLitenIp+':'+swoole_online_config.onlineLitenPort);
                org.Online.SocketListen();
            });
        });
    }
    //org.Online.SocketListen();
}

/**
 * 侦听socket服务器的回应
 */
org.Online.SocketListen = function() {
    // when open
    org.Online.Ws.onopen = function() {
        var uuid = $.cookie(org.Online.UuidKey);
        msg = new Object();
        msg.controller = 'online';
        msg.action = 'count';
        msg.params = {'uuid': uuid};
        org.Online.Ws.send($.toJSON(msg));
    }

    // when get message
    org.Online.Ws.onmessage = function(e) {
        var message = $.evalJSON(e.data);
        if(message.result == 'success') {
            $('.blog-right-tongji-online').html(message.message);
        }
    }
}

/*!
 * document ready
 */
$(document).ready(function() {
    org.Common.setformSubmitButton();
    org.Online.SetUuidCookie();
    org.Online.InitSocket();
});