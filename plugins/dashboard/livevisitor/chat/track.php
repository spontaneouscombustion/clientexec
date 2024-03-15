<?php
    require_once '../../../../config.php';
    require_once '../../../../library/constants.php';
    require_once '../../../../library/CE/Lib.php';


    $sessionName = (SESSION_NAME == 'CLIENTEXEC') ? md5(realpath(dirname(__FILE__)."/../../../../library/")) : SESSION_NAME;

    session_name($sessionName);
    session_start();

    header("Content-type: text/javascript");
    $hostname = $_SERVER['HTTP_HOST'];
    $dirName = dirname($_SERVER['SCRIPT_NAME']);
?>

var clientexec = clientexec || {};
clientexec.whoisonline = clientexec.whoisonline || {options:{}};
clientexec.httpstring = clientexec.protocol+'//<?php echo $hostname.$dirName;?>';
clientexec.sessionHash = '<?php echo CE_Lib::getSessionHash(); ?>';

window.session = {
    options: {
        hide_chat:true,
        use_html5_location: false,
        gapi_location: true,
        session_timeout: 5
    }
};

document.write("<script data-cfasync='false' type='text/javascript' src='//<?php echo $hostname.$dirName;?>/../lib/session.js'></script>");

clientexec.js_output = "<script data-cfasync='false' type='text/javascript'>\n$(document).ready(function() { \n \
    window.session.options = $.extend({},window.session.options,clientexec.whoisonline.options);\n \
    clientexec.whoisonline.hide_chat = window.session.options.hide_chat;\n \
    $.ajax({ \n \
          type: 'GET',\n \
          url:clientexec.httpstring+'/../../../../api/accounts/users/availableforchat/',\n \
          success: function(a) {\n \
              clientexec.whoisonline.start(clientexec.httpstring);\n \
              var popurl = clientexec.httpstring+'/../../../../index.php?fuse=admin&controller=plugin&view=doplugin&pluginaction=showchatpopup&plugin=livevisitor'; \n \
              if ( a.available && !clientexec.whoisonline.hide_chat) {\n \
                  $(document.body).append('<div id=\"livevisitor-compact-container\" style=\"position: fixed; bottom: 0px; right: 15px; width: 250px; height: 53px; overflow: hidden; visibility: visible; z-index: 2147483639; background-color: transparent; border: 0px; opacity: 1; background-position: initial initial; background-repeat: initial initial;\"><iframe name=\"livevisitor-compact-view\" id=\"livevisitor-compact-view\" style=\"position: relative;top: 20px;left: 0;width: 100%;border: 0;padding: 0;margin: 0;float: none;background: none\" scrolling=\"no\" frameborder=\"0\" allowtransparency=\"true\"></iframe></div>');\n \
                  $(document.body).append('<div id=\"livevisitor-full-container\" style=\"display:none;position: fixed; bottom: 0px; right: 0px; width: 400px; max-width: 100%; height: 450px; overflow: hidden; visibility: visible; z-index: 3000000; background-color: transparent; border: 0px; opacity: 1; background-position: initial initial; background-repeat: initial initial;\"><iframe src=\"'+popurl+'\" id=\"livevisitor-full-view\" name=\"livevisitor-full-view\" scrolling=\"no\" frameborder=\"0\" allowtransparency=\"true\" style=\"position: absolute; top: 0px; right: 0px; bottom: 0px; left: 0px; width: 100%; height: 100%; border: 0px; padding: 0px; margin: 0px; float: none; background-image: none; background-position: initial initial; background-repeat: initial initial;\"></iframe></div>');\n \
              }\n \
              chat_ui = {};\n \
          }\n \
         });\n \
    });\n</script>";

document.write(clientexec.js_output);

clientexec.whoisonline.start = function(httpstring)
{
    var chatterid = "";
    if ($.cookie('chatid') !== null) {
        chatterid = $.cookie('chatid');
    }
    //url:httpstring+
    $.ajax({
        type: "GET",
        url: clientexec.httpstring + '/../../../../index.php?fuse=admin&controller=plugin&action=doplugin&pluginaction=track&plugin=livevisitor&chatterid='+chatterid+'&sessionHash=' + clientexec.sessionHash + '&callback=?',
        data:window.session,
        dataType:"jsonp"
    });
}
