
$(document).ready(function () {

    affiliateField = {
        hidden: true,
        dataIndex: 'affiliate',
    };
    if (accounts.affiliateSystem) {
        affiliateField = {
            id: 'affiliate',
            text: 'Affiliate',
            dataIndex: 'affiliate',
            width: 130,
            align: 'center',
            sortable: false,
            renderer: function (text, row) {
                if (row.affiliate_user_id == 0) {
                    return clientexec.lang('None');
                }
                return String.format('<a href="index.php?fuse=clients&controller=userprofile&view=profileaffiliate&frmClientID={0}">{1}</a>', row.affiliate_user_id, row.affiliate);
            },
        };
    }

    accounts.grid = new RichHTML.grid({
        el: 'Accounts-grid',
        root: 'data',
        totalProperty: 'totalcount',
        baseParams: {
            limit: clientexec.records_per_view,
            sort: 'dateActivated',
            dir: 'asc',
            filter: -1
        },
        url: 'index.php?fuse=clients&action=getpendingorderslist',
        columns: [
        {
            dataIndex: 'pendingpackageid',
            xtype: 'checkbox',
        }, {
            id: 'date',
            text:  'Date',
            width: 100,
            sortable: true,
            dataIndex: 'dateActivated',
            renderer: function (text, row) {
                var date = text.split(' ');
                return date[0] + "<br/><span style='font-size:smaller;'>" + date[1] + '</span>';
            },
            align: 'right',
        }, {
            id: 'customername',
            text: 'Name',
            renderer:   function (text, row) {
                return String.format('<a href="index.php?fuse=clients&controller=userprofile&view=profilecontact&frmClientID={0}">{1}</a><br/><span style="font-size:smaller;"><a href="index.php?fuse=clients&controller=userprofile&view=profileproduct&groupinfo&id={3}&frmClientID={0}">{2}</a></span>{4}', row.pendingpackageuserid, text, row.pendingpackagereference, row.pendingpackageid, row.notallocated);
            },
            hidden: false,
            flex: 1,
            dataIndex: 'pendingpackagecustomername',
        }, {
            id: 'name',
            text: 'Pkg Name',
            width: 150,
            dataIndex: 'pendingpackagename',
            align: 'center',
        }, {
            id: 'status',
            text: 'Status',
            dataIndex: 'packagepaidstatus',
            width: 130,
            align: 'center',
            sortable: false
        }].concat(affiliateField)
    });

    accounts.grid.render();

    $(accounts.grid).bind({
        rowselect: function (event, data) {
            if (data.totalSelected > 0) {
                $('#activateAccount').removeAttr('disabled');
                $('#delAccount').removeAttr('disabled');
                $('#cancelAccount').removeAttr('disabled');
            } else {
                $('#activateAccount').attr('disabled', 'disabled');
                $('#delAccount').attr('disabled', 'disabled');
                $('#cancelAccount').attr('disabled', 'disabled');
            }
        }
    });


    $('#activateAccount').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }

        var html = clientexec.lang('Are you sure you want to activate the selected packages(s)');
        html += "<br/><div style='padding-top:8px;'><input type='checkbox' name='useplugin' id='useplugin' checked='checked'/> <span style='border-bottom: 1px solid #DFDFDF;cursor:help;' title='Activate the selected accounts then trigger the create action from their respective plugin.  Warning: This will also register any domains you are activating.'>Use their respective plugins?</span></div>";
        RichHTML.msgBox(html, {
            type:'yesno',
        }, function (result) {
            var usepackageplugin = 0;
            var useregistrarplugin = 0;
            if (typeof (result.elements.useplugin) !== 'undefined') {
                usepackageplugin = 1;
                useregistrarplugin = 1;
            }

            if (result.btn === clientexec.lang('Yes')) {
                RichHTML.mask();
                $.post('index.php?controller=packages&action=activateclientpackages&fuse=clients', {
                    domainids: accounts.grid.getSelectedRowIds(),
                    usepackageplugin: usepackageplugin,
                    useregistrarplugin: useregistrarplugin,
                }, function (data) {
                    if (data.error == true) {
                        RichHTML.msgBox(data.message, {type: 'error'});
                    }
                    accounts.grid.reload({params:{start:0}});
                    RichHTML.unMask();
                }, 'json');
            }
        });
    });

    $('#pending-grid-filter').change(function(){
        accounts.grid.reload({params:{start:0, limit:$(this).val()}});
    });

    $('#pending-grid-status-filter').change(function(){
        accounts.grid.reload({params:{start:0, filter:$(this).val()}});
    });

    $('#delAccount').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }

        var html = clientexec.lang('Are you sure you want to delete the selected packages(s)');
        RichHTML.msgBox(html, {
            type:'yesno',
        }, function (result) {
            if (result.btn === clientexec.lang('Yes')) {
                RichHTML.msgBox(clientexec.lang('Do you want to delete this client if they have no packages?'), {
                    type:'yesno',
                }, function (innerResult) {
                    deleteCustomer = 0;
                    if (innerResult.btn === clientexec.lang('Yes')) {
                        deleteCustomer = 1;
                    }
                    RichHTML.mask();
                    $.post('index.php?action=deleteclientpackages&controller=packages&fuse=clients', {
                        domainids: accounts.grid.getSelectedRowIds(),
                        usedeletecustomer: deleteCustomer,
                    }, function (data) {
                        if (data.error == true) {
                            RichHTML.msgBox(data.message, {type: 'error'});
                        }
                        accounts.grid.reload({params:{start:0}});
                        RichHTML.unMask();
                    }, 'json');
                });
            }
        });
    });

    $('#cancelAccount').bind('click',function(){
        if ($(this).attr('disabled')) {
            return false;
        }

        RichHTML.msgBox(clientexec.lang('Are you sure you want to cancel the selected package(s)?'), {
            type:"yesno"
        }, function(result) {
            if (result.btn === clientexec.lang("Yes")) {
                RichHTML.mask();
                $.post("index.php?fuse=clients&controller=packages&action=cancelpackages", {
                    ids:accounts.grid.getSelectedRowIds(),
                    useplugin: 0
                },
                function(data){
                    ce.parseResponse(data);
                    accounts.grid.reload({params:{ start:0 }});
                    RichHTML.unMask();
                });
            }
        });
    });
});
