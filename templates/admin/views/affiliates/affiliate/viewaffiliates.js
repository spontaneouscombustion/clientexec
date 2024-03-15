affiliatesList = {};

$(document).ready(function(){
    affiliatesList.baseParams = {
        limit: clientexec.records_per_view,
        sort: 'id',
        dir: 'desc',
        status:  $('#affiliatesList-grid-filterbystatus').val(),
    };

    affiliatesList.grid = new RichHTML.grid({
        el: 'affiliatesList-grid',
        url: 'index.php?fuse=affiliates&action=getaffiliates&controller=affiliate&comma=yes',
        baseParams: affiliatesList.baseParams,
        root: 'affiliates',
        totalProperty: 'totalcount',
        editable: true,
        columns: [{
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
            id: 'name',
            text: clientexec.lang("Name"),
            renderer: renderName,
            align: "left",
            dataIndex : "name",
            sortable: true,
            hidden:  false
        },{
            id: 'visitors',
            text: clientexec.lang("Visitors"),
            dataIndex : "visitors",
            hidden: false,
            sortable: false,
            width:  135,
            align: 'center'
        },{
            id: 'sales',
            text: clientexec.lang("Sales"),
            dataIndex : "sales",
            hidden: false,
            sortable: false,
            width:  135,
            align: 'center'
        },{
            id: 'balance',
            text: clientexec.lang("Unpaid Balance"),
            dataIndex : "balance",
            align: 'right',
            hidden: false,
            sortable: false,
            width:  135
        },{
            id: 'paid',
            text: clientexec.lang("Total Paid"),
            dataIndex : "paid",
            align: 'right',
            hidden: false,
            sortable: false,
            width:  135
        },{
            id: 'status',
            text: clientexec.lang("Status"),
            dataIndex : "status",
            hidden: false,
            sortable: false,
            width:  135,
            align:"center"
        }]
    });

    affiliatesList.grid.render();

    // **** start click binding
    $('#affiliatesList-grid-filter').change(function(){
        affiliatesList.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $(affiliatesList.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('.multi-action-button').removeAttr('disabled');
                $('#activateAff').removeAttr('disabled');
                $('#activateAff').removeAttr('disabled');
            } else {
                $('.multi-action-button').attr('disabled','disabled');
                $('#activateAff').attr('disabled','disabled');
                $('#activateAff').attr('disabled','disabled');
            }
        }
    });

    $('#affiliatesList-grid-filterbystatus').change(function(){
        affiliatesList.grid.reload({params:{start:0,status:$(this).val()}});
    });

    $('#activateAff').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(clientexec.lang('Are you sure you want to activate the follow clients as affiliates?'), {
            type:"confirm",
        }, function(result) {
            if (result.btn === clientexec.lang('Cancel') || result.btn === clientexec.lang('No') ) {
                affiliatesList.grid.reload({params:{start:0}});
                return;
            }

            $.post('index.php?fuse=affiliates&controller=affiliate&action=activateaffiliates',
                { ids: affiliatesList.grid.getSelectedRowIds() }, function(data) {
                ce.parseResponse(data);
                affiliatesList.grid.reload({params:{start:0}});
            });
        });
    });

    $('#declineAff').click(function () {
        if ($(this).attr('disabled')) {
            return false;
        }
        RichHTML.msgBox(clientexec.lang('Are you sure you want to decline the follow clients as affiliates?'), {
            type:"confirm",
        }, function(result) {
            if (result.btn === clientexec.lang('Cancel') || result.btn === clientexec.lang('No') ) {
                affiliatesList.grid.reload({params:{start:0}});
                return;
            }

            $.post('index.php?fuse=affiliates&controller=affiliate&action=declineaffiliates',
                { ids: affiliatesList.grid.getSelectedRowIds() }, function(data) {
                ce.parseResponse(data);
                affiliatesList.grid.reload({params:{start:0}});
            });
        });
    });

});

function renderName(text, row){

    var name = ce.htmlspecialchars(row.fullname);
    if ( row.isOrganization == 1 ) {
        name = name + ' - ' + ce.htmlspecialchars(row.firstname) +
            " " + ce.htmlspecialchars(row.lastname);
    }
    if($.trim(name) == "") name = "<i style='color:#888;'>"+clientexec.lang("No name entered")+"</i>";
    name = "<span>"+name+"</span>";
    url = String.format('<a href="index.php?fuse=clients&controller=userprofile&view=profileaffiliate&frmClientID={1}">{0}</a>', name, row.user_id);

    return url;
}