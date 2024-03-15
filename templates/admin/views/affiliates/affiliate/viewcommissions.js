$(document).ready(function(){
    commissionsList.baseParams = {
        limit: clientexec.records_per_view,
        sort: 'id',
        dir: 'desc',
        status:  $('#commissionsList-grid-filterbystatus').val(),
    };
    if (commissionsList.affiliateId) {
        commissionsList.baseParams.affiliateId = commissionsList.affiliateId;
    }

    commissionsList.grid = new RichHTML.grid({
        el: 'commissionsList-grid',
        url: 'index.php?fuse=affiliates&action=getcommissions&controller=commission&comma=yes',
        baseParams: commissionsList.baseParams,
        root: 'commissions',
        totalProperty: 'totalcount',
        editable: true,
        columns: [{
            xtype: 'expander',
            renderOnExpand: true,
            renderer: function(value, record, el) {
                var html = '<b>' + clientexec.lang('Date') + ': </b>';
                html += record.date;
                html += '<br/><b>' + clientexec.lang('Package') + ': </b>';
                html += record.user_package;
                return html;
            },
        },{
            id:         "cb",
            dataIndex:  "id",
            xtype:      "checkbox"
        }, {
            id:         "id",
            dataIndex:  "id",
            text:       clientexec.lang("Id"),
            align:      "center",
            sortable:   true,
            width:      40
        },{
            id: 'client',
            text: clientexec.lang("Client"),
            align: "left",
            dataIndex : "client",
            sortable: false,
            hidden:  false,
            renderer: renderClientName,
        },{
            id: 'affiliate',
            text: clientexec.lang("Affiliate"),
            align: "left",
            dataIndex : "affiliate",
            sortable: false,
            hidden:  false,
            renderer: renderAffiliateName,
        },{
            id: 'package',
            text: clientexec.lang("Package"),
            align: "left",
            dataIndex : "user_package",
            sortable: false,
            hidden:  false,
            renderer: renderPackage,
        },{
            id: 'clearing_date',
            text: clientexec.lang("Clearing Date"),
            dataIndex : "clearing_date",
            hidden: false,
            sortable: true,
            width:  100,
            align: 'center'
        },{
            id: 'order_value',
            text: clientexec.lang("Order Value"),
            dataIndex : "order_value",
            align: 'right',
            hidden: false,
            sortable: false,
            width:  100
        },{
            id: 'amount',
            text: clientexec.lang("Commission"),
            dataIndex : "amount",
            align: 'right',
            hidden: false,
            sortable: true,
            width:  100
        },{
            id: 'status',
            text: clientexec.lang("Status"),
            dataIndex : "status",
            renderer: renderStatus,
            hidden: false,
            sortable: true,
            align:"center"
        }]
    });

    commissionsList.grid.render();

    $('#commissionsList-grid-filter').change(function(){
        commissionsList.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $(commissionsList.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('.multi-action-button').removeAttr('disabled');
                $('#approveCommission').removeAttr('disabled');
                $('#declineCommission').removeAttr('disabled');
                $('#markPaidCommission').removeAttr('disabled');
            } else {
                $('.multi-action-button').attr('disabled','disabled');
                $('#approveCommission').attr('disabled','disabled');
                $('#declineCommission').attr('disabled','disabled');
                $('#markPaidCommission').attr('disabled','disabled');
            }
        }
    });

    $('#commissionsList-grid-filterbystatus').change(function(){
        commissionsList.grid.reload({params:{start:0,status:$(this).val()}});
    });

    $('#approveCommission').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(clientexec.lang('Are you sure you want to approve these commissions?'), {
            type:"confirm",
        }, function(result) {
            if (result.btn === clientexec.lang('Cancel') || result.btn === clientexec.lang('No') ) {
                commissionsList.grid.reload({params:{start:0}});
                return;
            }

            $.post('index.php?fuse=affiliates&controller=commission&action=approvecommissions',
                { ids: commissionsList.grid.getSelectedRowIds() }, function(data) {
                ce.parseResponse(data);
                commissionsList.grid.reload({params:{start:0}});
            });
        });
    });

    $('#declineCommission').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(clientexec.lang('Are you sure you want to decline these commissions?'), {
            type:"confirm",
        }, function(result) {
            if (result.btn === clientexec.lang('Cancel') || result.btn === clientexec.lang('No') ) {
                commissionsList.grid.reload({params:{start:0}});
                return;
            }

            $.post('index.php?fuse=affiliates&controller=commission&action=declinecommissions',
                { ids: commissionsList.grid.getSelectedRowIds() }, function(data) {
                ce.parseResponse(data);
                commissionsList.grid.reload({params:{start:0}});
            });
        });
    });

    $('#markPaidCommission').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(clientexec.lang('Are you sure you want to manually mark these commissions as paid out?'), {
            type:"confirm",
        }, function(result) {
            if (result.btn === clientexec.lang('Cancel') || result.btn === clientexec.lang('No') ) {
                commissionsList.grid.reload({params:{start:0}});
                return;
            }

            $.post('index.php?fuse=affiliates&controller=commission&action=markpaid',
                { ids: commissionsList.grid.getSelectedRowIds() }, function(data) {
                ce.parseResponse(data);
                commissionsList.grid.reload({params:{start:0}});
            });
        });
    });
});

function renderStatus(text, row) {
    return String.format("{0}", row.status_name);
}

function renderClientName(text, row) {
    var name = ce.htmlspecialchars(text);

    if (name == '') {
        url = String.format('<b>Bonus Deposit</b>');
    } else {
        url = String.format('<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID={1}">{0}</a></span>', name, row.client_id);
    }

    return url;
}

function renderPackage(text, row) {
    var name = ce.htmlspecialchars(text);

    if (name == '') {
        url = String.format('<b>-</b>');
    } else {
        url = String.format('<a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&selectedtab=groupinfo&id={1}&frmClientID={2}">{0}</a></span>', name, row.user_package_id, row.client_id);
    }

    return url;
}

function renderAffiliateName(text, row){
    var name = ce.htmlspecialchars(text);
    url = String.format('<a href="index.php?fuse=clients&controller=userprofile&view=profileaffiliate&frmClientID={1}">{0}</a></span>', name, row.affiliate_client_id);
    return url;
}