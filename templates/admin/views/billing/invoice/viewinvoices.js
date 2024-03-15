var viewinvoices = {};
viewinvoices.pricetypecombovalue = 0;
viewinvoices.AcceptCCNumber = false;

viewinvoices.addinvoicedescription = function(filterid)
{
    filterid = parseInt(filterid);
    var desc = "";
    switch(filterid) {
        case 0:
            desc =  clientexec.lang("Viewing all invoices that are past their due date, but not paid.");
            break;
        case 1:
            desc =  clientexec.lang("Viewing all invoices that have not been paid.");
            break;
        case 2:
            desc =  clientexec.lang("Viewing all invoices.");
            break;
        case 3:
            desc =  clientexec.lang("Viewing invoices for a filtered user.");
            break;
        case 4:
            desc =  clientexec.lang("Viewing pending invoices. Invoices that have been sent to a merchant but not cleared.  i.e. eChecks");
            break;
        case 5:
            desc =  clientexec.lang("Viewing all draft invoices. These invoices are not ready to be processed.");
            break;
        case -2:
            desc = clientexec.lang('Viewing all failed invoices.');
            break;
         case 6:
            desc = clientexec.lang('Viewing all paid invoices.');
            break;
    }

    if (desc != "") $('.filter-description').text(desc);

}


$(document).ready(function() {
    RichHTML.debugLvl = 0;

    richgrid = new RichHTML.grid({
        el: gridEl,
        width: "100%",
        editable : true,
        url: 'index.php?fuse=billing&controller=invoice&action=getinvoices',
        baseParams: {
            customerid: $('#invoice-userid').val(),
            sort: 'id',
            dir : 'desc', // or 'desc'
            limit: clientexec.records_per_view,
            invoicefilter: ($('#invoice-userid').val() == "") ? invoicelist_filter : 2,
            moduleview: "billing invoice list",
            filterbydate: $('#invoicelist-grid-filterbydate').val(),
            startdate: $('span.periodStart').attr('data-date'),
            enddate: $('span.periodEnd').attr('data-date')
        },
        root: 'invoices',
        totalProperty : 'totalcount',
        columns: [{
            text: "",
            xtype: "expander",
            escapeHTML: true,
            renderer:renderExpander,
            renderOnExpand: true
        },{
            text:     	"",
            dataIndex:  "id",
            xtype:      "checkbox"
        },{
            text:         clientexec.lang("Invoice")+" #",
            dataIndex:  "id",
            align:      "center",
            width:      85,
            sortable: true,
            renderer: function(text,row, el) {
                if ( viewinvoices.viewingFromProfile === true ) {
                    desc = "<a href='index.php?controller=invoice&fuse=billing&frmClientID="+row.customerid+"&view=invoice&invoiceid="+row.id+"&profile=1'>#"+text+"</a>";
                } else {
                    desc = "<a href='index.php?controller=invoice&fuse=billing&frmClientID="+row.customerid+"&view=invoice&invoiceid="+row.id+"'>#"+text+"</a>";
                }
                return desc + "  <span class='invoicepdflink'><a href='index.php?sessionHash="+gHash+"&fuse=billing&controller=invoice&action=generatepdfinvoice&invoiceid=" + row.invoiceid + "' target='_blank'><img class='pdfimage' src='../templates/admin/images/document-pdf-text.png' border='0' data-toggle='tooltip' title='"+ clientexec.lang('View PDF Invoice') +"' /></a></span>";
            }
        },{
            text:      clientexec.lang("Created"),
            dataIndex: "datecreated",
            align:     "right",
            width:     85,
            sortable:  true,
            hidden:    true,
            renderer:  ce.dateRenderer
        },{
            text:      clientexec.lang("Due"),
            dataIndex: "billdate",
            align:     "right",
            width:     85,
            flex :     1,
            sortable:  true,
            renderer:  ce.dateRenderer
        },{
            text:       clientexec.lang("Client Name"),
            dataIndex:  "customername",
            hidden: (gView == "profileinvoices") ? true : false,
            align: 		"left",
            flex : 1,
            renderer: function(value,record) {
                value = ce.htmlspecialchars(value);
                if ( viewinvoices.viewingFromProfile === true ) {
                    // we're viewing from the users profile, so only return their name
                    return value;
                }
                var filter = "";
                if ($('#invoice-userid').val() == "") {
                    filter = '&nbsp;&nbsp;<span class="filter-invoice-user link ico-small" data-userid="'+record.customerid+'" data-icon="F"></span>';
                }
                return '<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID=' + record.customerid + '">' + value + '</a>' + filter;
            }
        },
        {
            text:   clientexec.lang("Gateway"),
            width:	   (gView == "profileinvoices") ? "" : "145",
            dataIndex: "paymenttype",
            sortable: true,
            hidden:true,
            align: "center",
            renderer: function(value,record) {
                var tSubscriptionId = '';
                if ( record.subscriptionid != null && record.subscriptionid != "" ) {
                   value = record.paymenttype+'<br/> <span style="font-size:9px;">'+record.subscriptionid+'</span>';
                } else {
                    value = record.paymenttype;
                }

                return value;
            }
        },
        {
            text:    clientexec.lang("Failed Reason"),
            width: 210,
            dataIndex: "failedreason",
            hidden: ($('#invoice-userid').val() == "" && invoicelist_filter == -2) ? false : true,
            align: "center",
            escapeHTML: false
        },
        {
            text:   clientexec.lang("Pmt Reference"),
            width:	   "100",
            dataIndex: "billpmtref",
            sortable: true,
            hidden:true,
            align: "center",
            renderer: function(value,record) {
                value = record.billpmtref;
                return value;
            }
        },{
            text: clientexec.lang("Balance Due"),
            width: 90,
            dataIndex: 'balancedue',
            sortable: true,
            hidden: (gView == "profileinvoices") ? false : true,
            align:"right",
            renderer : function (val, row) {
                var font_class = "";
                var due = row.simplebalancedue;
                if (due.length >= 18) {
                    font_class = "xxlong-currency";
                } else if (due.length >= 15) {
                    font_class = "xlong-currency";
                } else if (due.length >= 13) {
                    font_class = "long-currency";
                }
                return "<span class='"+font_class+"'>"+val+"</span>";
            }
        },{
            text: clientexec.lang("Total"),
            width: 90,
            dataIndex: 'amount',
            sortable: true,
            align:"right",
            renderer : function (val, row) {
                var font_class = "";
                var due = row.simplebalancedue;
                if (due.length >= 18) {
                    font_class = "xxlong-currency";
                } else if (due.length >= 15) {
                    font_class = "xlong-currency";
                } else if (due.length >= 13) {
                    font_class = "long-currency";
                }
                return "<span class='"+font_class+"'>"+val+"</span>";
            }
        },
        {
            text:    clientexec.lang("Status"),
            width: 70,
            dataIndex: "billstatus",
            align: "center",
            escapeHTML: false,
            renderer: renderStatus
        }
        ]
    });

    richgrid.render();

    function renderExpander(value,record,el){
        $.ajax({
            url: 'index.php?fuse=billing&controller=invoice&action=getstyledinvoicetransactions',
            dataType: 'json',
            async: false,
            data: {
                invoiceid:record.invoiceid
            },
            success: function(data) {
                html = data.invoicetransactions;
            }
        });
        return html;
    }

    function renderStatus(value,record, el){
        if(record.statusenum == -2){
            el.addClass = "invoiceoverdue";
        }else if (record.statusenum === 0){
        }else if(record.statusenum === 1){
            el.addClass = "invoicepaid";
        }else if (record.statusenum === -1){
            el.addClass = "invoicevoidrefund";
        }

        return value;
        //return String.format("{0}",value);
    }

    //GLOBAL BINDS FOR OUR INSTALLATION
    $(richgrid).bind({
        "load" : function(event,data) {
            viewinvoices.disableButtons();
            $('span.filter-invoice-user').click(function() {
                var userid = $(this).attr('data-userid');
                $('input#invoice-userid').val(userid);
                viewinvoices.postactionsfilterbyuserid(userid);
            });
            if(data.jsonData.outstandingamount != ''){
                $('.filter-totals').html(clientexec.lang('Outstanding Amount') + ':&nbsp;&nbsp;' + data.jsonData.outstandingamount.replace('<BR/>', '&nbsp;&nbsp;&nbsp;&nbsp;'));
            }else{
                $('.filter-totals').html('');
            }
        },
        "rowselect": function(event,data) {
            viewinvoices.disableButtons();
            if (data.totalSelected > 0) {
                viewinvoices.enableButtons();
            } else {
                viewinvoices.disableButtons();
            }
        }
    });

    $('#invoicelist-grid-filter').change(function(){
        richgrid.reload({
            params:{
                "start":0,
                "limit":$(this).val()
            }
        });
    });

    $('#invoice-userid').keydown(function(e){
        if (e.keyCode === 13) {
            if (trim($(this).val()) === ""){
                RichHTML.msgBox(clientexec.lang('User id field may not be left blank'), {type:"error"});
                return false;
            } else {
                viewinvoices.addinvoicedescription(3);
                viewinvoices.postactionsfilterbyuserid($(this).val());
            }
        }
        return true;
    });

    $('#invoicelist-grid-filterbydate').change(function(){
        richgrid.reload({
            params:{
                "start":0,
                "filterbydate":$(this).val()
            }
        });
    });

    var datePickerOpts = {
        format: clientexec.dateFormat == 'm/d/Y'? 'mm/dd/yyyy' : 'dd/mm/yyyy'
    };

    var changeDate = function(ev, callback) {
        var y = ev.date.getFullYear(),
        _m = ev.date.getMonth() + 1,
        m = (_m > 9 ? _m : '0'+_m),
        _d = ev.date.getDate(),
        d = (_d > 9 ? _d : '0'+_d);

        var formattedDate = clientexec.dateFormat == 'm/d/Y'? m + '/' + d + '/' + y : d + '/' + m + '/' + y;
        callback(formattedDate);
    };

    $('span.periodStart').datepicker({
        todayHighlight: true,
        todayBtn: "linked",
        autoclose: true,
        clearBtn: true
    });

    $('span.periodStart').datepicker().on('clearDate', function() {
        $('#periodStart-display').text(clientexec.lang("Start Date"));
        richgrid.reload({
            params: {
                "start": 0,
                "startdate": ''
            }
        });
    });

    $('span.periodStart').datepicker(datePickerOpts).on('changeDate', function(ev) {
        changeDate(ev, function(formattedDate) {
            $('span.periodStart').attr('data-date', formattedDate).datepicker('hide');
            $('#periodStart-display').text(formattedDate);
            richgrid.reload({
                params: {
                    "start": 0,
                    "startdate": formattedDate
                }
            });
        });
    });

    $('span.periodEnd').datepicker({
        todayHighlight: true,
        todayBtn: "linked",
        autoclose: true,
        clearBtn: true
    });

    $('span.periodEnd').datepicker().on('clearDate', function() {
        $('#periodEnd-display').text(clientexec.lang("End Date"));
        richgrid.reload({
            params: {
                "start": 0,
                "enddate": ''
            }
        });
    });

    $('span.periodEnd').datepicker(datePickerOpts).on('changeDate', function(ev) {
        changeDate(ev, function(formattedDate) {
            $('span.periodEnd').attr('data-date', formattedDate).datepicker('hide');
            $('#periodEnd-display').text(formattedDate);
            richgrid.reload({
                params: {
                    "start": 0,
                    "enddate": formattedDate
                }
            });
        });
    });

    $('#invoicelist-grid-filterbystatus').change(function(){

        if ($(this).val() == "3") {
            $('#invoice-userid').val('');
            $('#td-for-userid').show();
        } else {
            viewinvoices.addinvoicedescription($(this).val());
            $('#td-for-userid').hide();
            $('#viewing-invoices-text').text(clientexec.lang("Viewing Invoices"));
            richgrid.reload({
                params:{
                    "start":0,
                    "customerid": $('#invoice-userid').val(),
                    "invoicefilter":$(this).val()
                }
            });
        }
    });

    $('div#invoicelist-grid-buttons a.btn:not(.dropdown-toggle), div#invoicelist-grid-buttons ul.dropdown-menu li').click(function(button){
        if ( $(this).attr('disabled') ) {
            return;
        }

        richgrid.disable();
        $('span.btn-group').removeClass('open');

        var id = $(this).attr('data-actionname');

        if (id == 'inv-cancelsub') {
            RichHTML.msgBox(clientexec.lang("Are sure you want to cancel the subscription tied to this invoice?"),{type:'yesno'},function(ret) {
                if (ret.btn == clientexec.lang("No")) {
                    richgrid.enable();
                    return;
                }
                viewinvoices.performaction(id);
            });

        } else if (id == "inv-markpaid"){
            RichHTML.msgBox(clientexec.lang("Do you want to send a receipt?"),{type:'confirm'},
                function(ret) {
                    var sendReceipt = false;
                    if (ret.btn == clientexec.lang("Yes")) {
                        sendReceipt = true;
                    } else if(ret.btn == clientexec.lang("Cancel")) {
                        richgrid.enable();
                        return;
                    }
                    viewinvoices.performaction(id,{sendreceipt:sendReceipt});
                    return;
                }
            );
        } else if(id == "inv-deleteinvoices"){
            RichHTML.msgBox(clientexec.lang("Are sure you want to delete the selected invoice(s)."),{type:'yesno'},
                function(ret) {
                    if(ret.btn == clientexec.lang("No")) {
                        richgrid.enable();
                        return;
                    }
                    viewinvoices.performaction(id);
                });

        } else if(id == "inv-varpayment"){

            var balancedue = richgrid.getSelectedRowData()[0].simplebalancedue;
            var rawbalancedue = richgrid.getSelectedRowData()[0].rawbalancedue;
            RichHTML.msgBox('',
                {
                    type:'prompt',
                    content: 'Balance Due: '+balancedue+'<br/>'
                        +'<input type="text" id="paymentamount" name="paymentamount" class="required float" placeholder="'+clientexec.lang("Amount")+'" /><br/><br/>'
                        +'<a href="#" id="addOptionalLink">'+clientexec.lang("Add optional information")+'</a>'
                        +'<fieldset class="editOptionalPopup" style="display:none">'
                        +'<a href="#"><i class="icon-remove-sign icon-large"></i>&nbsp&nbsp'+clientexec.lang("Remove Optional Information")+'</a>'
                        +'<div class="row-fluid">'
                        +'<input type="text" name="checknum" id="checknum" placeholder="'+clientexec.lang("Payment Reference (Optional)")+'" /><br/>'
                        +'<input class="datepicker" style="width: 206px" type="text" name="paymentdate" id="paymentdate" placeholder="'+clientexec.lang("Payment Date (Optional)")+'"/><br/>'
                        +'<input class="timepicker" style="width: 206px" type="text" name="paymenttime" id="paymenttime" placeholder="'+clientexec.lang("Payment Time (Optional)")+'"/><br/>'
                        +'<input type="text" name="paymentprocessor" id="paymentprocessor" placeholder="'+clientexec.lang("Payment Processor (Optional)")+'" />'
                        +'</div>'
                        +'</fieldset>'
                },
                function(ret){
                    if (ret.btn == clientexec.lang("Cancel")) {
                        richgrid.enable();
                        return;
                    } else {
                        var priceformatted2 = ret.elements.paymentamount;
                        priceformatted2 = priceformatted2.toString();
                        var price = accounting.unformat(priceformatted2.replace(richgrid.getSelectedRowData()[0].currency.symbol, ""), richgrid.getSelectedRowData()[0].currency.decimalssep);
                        ret.elements.paymentamount = parseFloat(price);

                        if (ret.elements.paymentamount >= parseFloat(rawbalancedue)) {
                            RichHTML.msgBox(clientexec.lang("Do you want to send a receipt?"),{type:'confirm'},
                                function(ret2) {
                                    if (ret2.btn == clientexec.lang("Yes")) {
                                        var data = {sendreceipt: true};
                                        var args = ret.elements;
                                        $.extend(data, args);
                                        viewinvoices.performaction(id, data);
                                    } else if (ret2.btn == clientexec.lang("No")) {
                                        var data = {sendreceipt: false};
                                        var args = ret.elements;
                                        $.extend(data, args);
                                        viewinvoices.performaction(id, data);
                                    } else if(ret2.btn == clientexec.lang("Cancel")) {
                                        richgrid.enable();
                                        return;
                                    }
                                }
                            );
                        } else {
                            var data = {sendreceipt: false};
                            var args = ret.elements;
                            $.extend(data, args);
                            viewinvoices.performaction(id, data);
                        }
                    }
                }
            );
            clientexec.postpageload();

            $('#paymentamount').unbind('keypress');
            $('#paymentamount').unbind('blur');
            $('#paymentamount').bind('keypress blur',function(event){
                //validate this field as allowing only float
                if ( ( event.which == 13 ) || (event.type === "blur") ) {
                    //lets check to see if the price was updated
                    var priceformatted2 = $(this).val();
                    priceformatted2 = priceformatted2.toString();
                    var price = accounting.unformat(priceformatted2.replace(richgrid.getSelectedRowData()[0].currency.symbol, ""), richgrid.getSelectedRowData()[0].currency.decimalssep);

                    if( richgrid.getSelectedRowData()[0].currency.decimalssep === '&nbsp;') {
                        decimalssep = ' ';
                    } else {
                        decimalssep = richgrid.getSelectedRowData()[0].currency.decimalssep;
                    }

                    if (richgrid.getSelectedRowData()[0].currency.thousandssep === '&nbsp;') {
                        thousandssep = ' ';
                    } else {
                        thousandssep = richgrid.getSelectedRowData()[0].currency.thousandssep;
                    }

                    $(this).val(accounting.formatMoney(price, "", richgrid.getSelectedRowData()[0].currency.precision, thousandssep, decimalssep, richgrid.getSelectedRowData()[0].currency.alignment));
                }
            });

            $('#addOptionalLink').click(function() {
              $(this).hide();
              $(this).next().show();
            });

            $('.editOptionalPopup > a').click(function() {
                $('.editOptionalPopup').hide();
                $('#checknum').val('');
                $('#paymentdate').val('');
                $('#paymentprocessor').val('');
                $('#addOptionalLink').show();
            });

        } else if(id == "inv-process"){
            var selectedRowData = richgrid.getSelectedRowData();
            var arrayLength = selectedRowData.length;
            var askAboutCharge = false;
            for (var idx = 0; idx < arrayLength; idx++) {
                if(selectedRowData[idx].canbechargedtoday == 0){
                    askAboutCharge = true;
                    break;
                }
            }

            if(askAboutCharge){
                RichHTML.msgBox(clientexec.lang("Some invoices are not due. Are you sure you want to proceed?"),
                    {type:'yesno'},function(result){
                       if(result.btn === clientexec.lang("Yes")) {
                            //viewinvoices.AcceptCCNumber = false;
                            if (viewinvoices.AcceptCCNumber) {

                                RichHTML.msgBox(clientexec.lang('Enter your passphrase:'),
                                    {type:'prompt',password:true},
                                    function(result){
                                        if(result.btn === clientexec.lang("OK")) {
                                            viewinvoices.performaction(id,{passphrase:result.elements.value,acceptccnumber:viewinvoices.AcceptCCNumber});
                                        } else {
                                            richgrid.enable();
                                        }
                                    }
                                );
                            } else {
                                viewinvoices.performaction(id,{acceptccnumber:viewinvoices.AcceptCCNumber});
                            }
                        } else {
                            richgrid.enable();
                        }
                });
            } else {
                //viewinvoices.AcceptCCNumber = false;
                if (viewinvoices.AcceptCCNumber) {

                    RichHTML.msgBox(clientexec.lang('Enter your passphrase:'),
                        {type:'prompt',password:true},
                        function(result){
                            if(result.btn === clientexec.lang("OK")) {
                                viewinvoices.performaction(id,{passphrase:result.elements.value,acceptccnumber:viewinvoices.AcceptCCNumber});
                            } else {
                                richgrid.enable();
                            }
                        }
                    );
                } else {
                    RichHTML.msgBox(clientexec.lang("Are you sure you want to process the selected account(s)?"),
                        {type:'yesno'},function(result){
                           if(result.btn === clientexec.lang("Yes")) {
                                viewinvoices.performaction(id,{acceptccnumber:viewinvoices.AcceptCCNumber});
                            } else {
                                richgrid.enable();
                            }
                    });
                }
            }
        } else {
            //all other actions do not need confirmations or prompts
            viewinvoices.performaction(id);
        }

    });

    viewinvoices.performaction = function(id,args) {

        var data = {
                items:          richgrid.getSelectedRowIds(),
                itemstype:      'invoices',
                actionbutton:   id
            };

        $.extend(data,args);

        $.ajax({
            url: "index.php?fuse=billing&controller=invoice&action=actoninvoice",
            type: 'POST',
            data:  data,
            success:  function(xhr){
                richgrid.reload();
                ce.parseResponse(xhr);
                if (typeof profile !== "undefined") {
                    setTimeout(function() {
                        profile.get_counts();
                    },1000);
                }
            }
        });
    };

    viewinvoices.enableButtons = function() {

        $.ajax({
           url: "index.php?fuse=billing&controller=invoice&action=getinvoicebuttons",
           data: {invoices: richgrid.getSelectedRowIds()},
           success: function(data) {

               viewinvoices.AcceptCCNumber = data.buttons.acceptccnumber;

               $.each(data.buttons,function(name,val){
                   if (val) {
                       $('div#invoicelist-grid-buttons li[data-actionname="inv-'+name+'"]').show();
                       $('div#invoicelist-grid-buttons a[data-actionname="inv-'+name+'"]:not(.btn-group a)').show();
                   } else {
                       $('div#invoicelist-grid-buttons li[data-actionname="inv-'+name+'"]').hide();
                       $('div#invoicelist-grid-buttons a[data-actionname="inv-'+name+'"]:not(.btn-group a)').hide();
                   }
               });

               //if no options are available for the btngroup then hide it
               //this code hides all group buttons that do not have child elements
               //then sets the name and action of the btn to that of the top most option
               $('div#invoicelist-grid-buttons span.btn-group').each(function(k,v) {
                   var li_filter = $(this).find('ul.dropdown-menu li[data-actionname]').filter(function() { return $(this).css("display") != "none"; });
                   if (li_filter.length == 0) {
                       $(this).hide();
                   } else {
                       $(this).show();
                   }
                   //lets make the top option the main option
                   $(this).find('a.btn:not(.dropdown-toggle)').attr('data-actionname',li_filter.first().attr('data-actionname'));
                   $(this).find('a.btn:not(.dropdown-toggle)').text(li_filter.first().text());
               });

               $('#invoicelist-grid-buttons .btn').removeAttr('disabled');

           }
        });
    };

    viewinvoices.disableButtons = function() {
        $('#invoicelist-grid-buttons').show();
        $('#invoicelist-grid-buttons .btn').attr('disabled','disabled');
    };

    viewinvoices.postactionsfilterbyuserid = function(userid) {

        if ( typeof viewinvoices.viewingFromProfile === 'undefined' ) {
            History.pushState({}, document.title, "index.php?fuse=billing&controller=invoice&view=invoices&customerid="+userid);
        } else {
            History.pushState({}, document.title, "index.php?fuse=clients&controller=userprofile&view=profileinvoices&frmClientID="+userid);
        }
        richgrid.reload({params:{start:0,invoicefilter:"3",customerid:userid}});

        viewinvoices.addinvoicedescription(3);

        $('#invoicelist-grid-filterbystatus').select2("val",3);
        $('#td-for-userid').show();
        $('#viewing-invoices-text').text(clientexec.lang("Viewing Invoices for User Id "+$('#invoice-userid').val()));
    };

    viewinvoices.paymentReferenceWindow = new RichHTML.window({
        height: '75',
        width: '260',
        grid: richgrid,
        url:       'index.php?fuse=billing&controller=invoice&view=paymentreference',
        actionUrl: 'index.php?fuse=billing&controller=invoice&action=savepaymentreference',
        showSubmit: true,
        title: clientexec.lang("Add Payment Reference")
    });

    if ($('#invoice-userid').val() != "") {
        viewinvoices.postactionsfilterbyuserid($('#invoice-userid').val());
    }

});
