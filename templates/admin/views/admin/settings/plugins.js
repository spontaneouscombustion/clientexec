$(document).ready(function() {
    $('.gatewaydefault').bind('click',function(){
        $.ajax({
            url: "index.php?fuse=admin&action=MakeGatewayDefault",
            dataType: 'json',
            data: {gateway:$(this).attr('data-gateway')},
            success: function(json) {
                if (json.error) {
                    ce.msg(json.message);
                } else {
                    $('#vtabsBar .vtab.active').append($('#vtabsBar .vtab span.when'));
                    ce.msg(clientexec.lang('Gateway updated properly'));
                    $('.gatewaydefault').html(clientexec.lang('Already selected'));
                    $('.gatewaydefault').removeClass('link').unbind('click');
                }
            }
        });
    });

    $("#deletebillingprofileidsbtn").click(function() {
        $('#deletebillingprofileidsbtn').button('loading');
        $.ajax({
            url: "index.php?fuse=admin&action=DeleteBillingProfileIDs",
            dataType: 'json',
            data: {gateway:$(this).attr('data-gateway')},
            success: function(json) {
                if (json.error) {
                    ce.msg(json.message);
                } else {
                    $('#deletebillingprofileidsbtn').button('reset');
                    ce.msg(clientexec.lang('Billing Profile IDs successfully deleted'));
                }
                $('#deletebillingprofileidsbtn').button('reset');
            }
        });
        return false;
    });
});