$(document).ready(function() {
    $('#registerAffiliateBtn').click(function(e){
        e.preventDefault();
        RichHTML.msgBox(clientexec.lang('Are you sure you want to register this client as an affiliate?'), {
            type:"yesno"
        }, function(result) {
            if (result.btn === clientexec.lang("Yes")) {
                RichHTML.mask();
                $.ajax({
                    url: 'index.php?fuse=affiliates&controller=affiliate&action=registeraffiliate',
                    type: 'POST',
                    data: { id: customerid },
                    success: function (json) {
                        response = ce.parseResponse(json);
                        if (response.success == true) {
                            location.reload();
                        }
                        RichHTML.unMask();
                    }
                });
            }
        });
    });

    $("#updateAffiliate").click(function() {

        var contactForm = $("#affiliatedata");
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