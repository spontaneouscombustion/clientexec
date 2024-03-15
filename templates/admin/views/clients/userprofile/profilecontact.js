var datefields = new Array();

$().ready(function(){

    $('#reset-password').click(function(e){
        e.preventDefault();
        RichHTML.msgBox(clientexec.lang('Are you sure you want to reset the password of this client?'), {
            type:"yesno"
        }, function(result) {
            if (result.btn === clientexec.lang("Yes")) {
                RichHTML.mask();
                $.ajax({
                    url: 'index.php?fuse=clients&controller=user&action=resetpass',
                    type: 'POST',
                    data: { id: $('#resetpass-customer-id').val() },
                    success: function (json) {
                        ce.parseResponse(json);
                        RichHTML.unMask();
                    }
                });
            }
        });
    });

    $('#export-data').click(function(e){
        e.preventDefault();
        RichHTML.msgBox(clientexec.lang('Are you sure you want to export this client data?'), {
            type:"yesno"
        }, function(result) {
            if (result.btn === clientexec.lang("Yes")) {
                window.location = 'index.php?fuse=admin&view=viewexportplugins&plugin=fullclientdata&controller=importexport&exportdata='+$('#exportdata-customer-id').val();
            }
        });
    });

    $('#deleteclient').click(function(){

        RichHTML.msgBox(clientexec.lang('Are you sure you want to delete this client?'), {
            type:"confirm"
        }, function(result) {
            if (result.btn === clientexec.lang("Yes")) {
                RichHTML.msgBox(clientexec.lang("Do you want to delete this client's packages using the respective server plugin(s)?"), {
                    type:'confirm'
                }, function (innerResult) {

                    if ( innerResult.btn === clientexec.lang('Cancel') ) {
                        return;
                    }

                    var contactForm = $("#frmdeleteclient");

                    if ( innerResult.btn === clientexec.lang('Yes') ) {
                        $('#deletewithplugin').val(1)
                    }

                    $.ajax({
                        url: contactForm.attr( 'action' ),
                        type: contactForm.attr( 'method' ),
                        data: contactForm.serialize(),
                        success: function (json){
                            ce.parseResponse(json);
                            if ( json.success == true ) {
                                window.location = "index.php?fuse=clients&controller=user&view=viewusers";
                            }
                        }
                    });
                });
            }
        });
        return false;
    });

    $("#updatecontact").click(function() {

        var contactForm = $("#customerdata");
        $.ajax( {
            url: contactForm.attr( 'action' ),
            type: contactForm.attr( 'method' ),
            data: contactForm.serialize(),
            success: function (json){
                ce.parseResponse(json);
            }
        } );

        return false;
    });

});