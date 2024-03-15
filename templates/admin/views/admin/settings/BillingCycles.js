var billingCycles = {};

$(document).ready(function() {
    billingCycles.grid = new RichHTML.grid({
        el: 'billingCycles-grid',
        url: 'index.php?fuse=billing&action=get&controller=billingcycle',
        root: 'cycles',
        totalProperty: 'totalcount',
        baseParams: {
            limit: clientexec.records_per_view,
            sort: 'order_value',
            dir: 'asc'
        },
        columns: [{
            id: "cb",
            dataIndex: "id",
            xtype: "checkbox"
        }, {
            id: "name",
            dataIndex: "name",
            align:"left",
            text: clientexec.lang("Name"),
            sortable: false,
            renderer: function(text, row) {
                return "<a onclick='billingCycles.window.show({params:{id:"+row.id+"}});'>"+text+"</a>";
            }
        },{
            id: "time_unit",
            align:"left",
            text:  clientexec.lang("Time Unit"),
            dataIndex: "time_unit",
            sortable: false,
            align: 'center',
        },{
            id: "amount_of_units",
            text:  clientexec.lang("Amount of Units"),
            dataIndex: "amount_of_units",
            sortable: false,
            align: 'center'
        },{
            id: "countrecurringfees",
            text:  clientexec.lang("Recurring Fees"),
            dataIndex: "countrecurringfees",
            align:"center",
            sortable: false
        }]
    });
    billingCycles.grid.render();

    $('#billingCycles-grid-filter').change(function(){
        billingCycles.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $(billingCycles.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#deleteCyclesButton').removeAttr('disabled');
            } else {
                $('#deleteCyclesButton').attr('disabled','disabled');
            }
        }
    });

     $('#deleteCyclesButton').click(function () {
        RichHTML.msgBox(clientexec.lang('Are you sure you want to delete the selected billing cycle(s)'),
        {
            type:"yesno"
        }, function(result) {
            if ( result.btn === clientexec.lang("Yes") ) {
                $.post("index.php?fuse=billing&action=delete&controller=billingcycle", { ids: billingCycles.grid.getSelectedRowIds() },
                function(data){
                    jsonData = ce.parseResponse(data);
                    //if ( jsonData.success == true ) {
                        billingCycles.grid.reload({params:{start:0}});
                    //}
                });
            }
        });
    });

    billingCycles.window = new RichHTML.window({
        height: '200',
        width: '200',
        grid: billingCycles.grid,
        url: 'index.php?fuse=billing&view=billingcycle&controller=billingcycle',
        actionUrl: 'index.php?action=save&controller=billingcycle&fuse=billing',
        showSubmit: true,
        title: clientexec.lang("Add/Edit Billing Cycle")
    });

    $('#addCycleButton').click(function(){
        billingCycles.window.show();
    });
});