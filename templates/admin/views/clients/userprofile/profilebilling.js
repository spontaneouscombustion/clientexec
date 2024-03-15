$().ready(function(){
    //This code here is to avoid an issue with the Delete button been displayed in a different line, when the div was been displayed by default.
    if ($('.selected_paymenttype').length > 0 && $('.'+$('.selected_paymenttype')[0].value+'BillingProfileID').length > 0) {
        $('.'+$('.selected_paymenttype')[0].value+'BillingProfileID').show();
        $('.DivBillingProfileID').show();
    }
    //This code here is to avoid an issue with the Delete button been displayed in a different line, when the div was been displayed by default.

    $('#btnUpdate').bind('click',function(e){
        if (document.getElementsByName("currency")[0].value != $('.selected_currency')[0].value) {
            RichHTML.msgBox(clientexec.lang("<b>Are you sure you want to change the currency?</b>")+"</br></br>"+clientexec.lang("Please note that changing the currency will not convert the price amount to existing charges.")+"</br></br>"+clientexec.lang("You will need to review the prices on the existing invoices and recurring fees and reconfigure the packages and addons to make sure they are using prices for the new currency."),{type:'yesno'},function(result){
                if(result.btn === clientexec.lang("Yes")) {
                    RichHTML.mask();
                    e.preventDefault();

                    var data = $("#customerdata").serializeArray();
                    $.post("index.php?fuse=clients&controller=userprofile&action=updateprofilebilling", data, function(xhr){
                        json = ce.parseResponse(xhr);
                        if (!json.error) window.location.href = "index.php?fuse=clients&controller=userprofile&view=profilebilling";
                    });
                }
            });
        } else {
            RichHTML.mask();
            e.preventDefault();

            var data = $("#customerdata").serializeArray();
            $.post("index.php?fuse=clients&controller=userprofile&action=updateprofilebilling", data, function(xhr){
                json = ce.parseResponse(xhr);
                if (!json.error) window.location.href = "index.php?fuse=clients&controller=userprofile&view=profilebilling";
            });
        }

        return false;
    });

    if ($('#viewcclink').length > 0) {
        $('#viewcclink').click(function(){
            RichHTML.msgBox(clientexec.lang("Enter your passphrase:"),
            {type:'prompt',password:true},
            function(result){
                if(result.btn === clientexec.lang("OK")) {

                    $.ajax({
                        type: 'POST',
                        url: 'index.php?fuse=billing&controller=creditcard&action=viewccnumber',
                        data: {
                            pp: result.elements.value
                        },
                        success: function(data) {
                            ccWindow = new RichHTML.window({
                                title: clientexec.lang('Credit Card Number'),
                                content: data
                            });
                            ccWindow.show();
                        }
                    });
                }
            });
        });
    }

    if ($('#btnUpdateProfileID').length > 0) {
        $('#btnUpdateProfileID').click(function(e){
            console.log('click');
            e.preventDefault();
            updateProfileIdWindow.show();
        });
    }

    if ($('#btnDeleteProfileID').length > 0) {
        $('#btnDeleteProfileID').click(function(){
            RichHTML.msgBox(clientexec.lang("Are you sure you want to delete the billing profile id on file?"),{type:'yesno'},function(result){
                if(result.btn === clientexec.lang("Yes")) {
                    RichHTML.mask();
                    paymenttype = $('.selected_paymenttype')[0].value;
                    window.location = "index.php?fuse=clients&controller=userprofile&view=profilebilling&deleteProfileID=true&paymenttype="+paymenttype;
                }
            });
        });
    }

    if ($('#btnDeletecc').length > 0) {
        $('#btnDeletecc').click(function(){
            RichHTML.msgBox(clientexec.lang("Are you sure you want to delete the credit card on file?"),{type:'yesno'},function(result){
                if(result.btn === clientexec.lang("Yes")) {
                    RichHTML.mask();
                    window.location = "index.php?fuse=clients&controller=userprofile&view=profilebilling&deleteCCNumber=true";
                }
            });
        });
    }

    if ($('#btnValidatecc').length > 0) {
        $('#btnValidatecc').click(function(){
            RichHTML.msgBox(clientexec.lang("Enter your passphrase:"),
            {type:'prompt',password:true},
            function(result){
                if(result.btn === clientexec.lang("OK")) {
                    RichHTML.mask();
                    var requesturl = "index.php?fuse=billing&controller=creditcard&action=validateccnumber";
                    $.ajax({
                        type: 'POST',
                        url: requesturl,
                        success: function(xhr) {
                            json = ce.parseResponse(xhr);
                            if (json.success) {
                                window.location = "index.php?fuse=clients&controller=userprofile&view=profilebilling";
                            } else {
                                RichHTML.unMask();
                            }
                        },
                        data: {
                            passphrase: result.elements.value
                        }
                    });
                }
            });
        });
    }

    updateProfileIdWindow = new RichHTML.window({
        showSubmit: true,
        actionUrl: 'index.php?fuse=clients&action=updatebillingprofileid&controller=userprofile',
        title: clientexec.lang("Update Billing Profile ID"),
        url: 'index.php?fuse=clients&view=billingprofileid&controller=userprofile',
        onSubmit: function(e, data) {
            response = ce.parseResponse(e);
            if (!response.error) {
                window.location = 'index.php?fuse=clients&controller=userprofile&view=profilebilling';
            }

        }
    });

    addCreditWindow = new RichHTML.window({
        showSubmit: true,
        actionUrl: 'index.php?fuse=clients&action=addcredit&controller=userprofile',
        title: clientexec.lang("Add Credit"),
        url: 'index.php?fuse=clients&view=creditwindow&controller=userprofile',
        onSubmit: function(e, data) {
            response = ce.parseResponse(e);
            if (!response.error) {
                window.location = 'index.php?fuse=clients&controller=userprofile&view=profilebilling';
            }

        }
    });

    removeCreditWindow = new RichHTML.window({
        // id: 'remove-credit-window',
        showSubmit: true,
        actionUrl: 'index.php?fuse=clients&action=removecredit&controller=userprofile',
        title: clientexec.lang("Remove Credit"),
        url: 'index.php?fuse=clients&view=creditwindow&controller=userprofile',
        onSubmit: function(e, data) {
            response = ce.parseResponse(e);
            if (!response.error) {
                window.location = 'index.php?fuse=clients&controller=userprofile&view=profilebilling';
            }

        }
    });


    $('#credit-history-btn').on('click', function(e) {
        e.preventDefault();
        window.location = 'index.php?fuse=clients&controller=userprofile&view=credithistoryview'
    });

    $('#add-credit-btn').on('click', function(e){
        e.preventDefault();
        addCreditWindow.show();
    });

    $('#remove-credit-btn').on('click', function(e){
        e.preventDefault();
        removeCreditWindow.show();
    });

    $('#credit-history-btn').on('click', function(e){
        e.preventDefault();
    });

    $('dd:visible:odd').css("background-color","#F8F8F8 ");
});
