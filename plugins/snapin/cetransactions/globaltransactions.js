transactions = {};

$(document).ready(function() {
    transactions.grid = new RichHTML.grid({
        el: 'transactions-grid',
        url: 'index.php?fuse=admin&action=doplugin&controller=plugin&type=snapin&plugin=cetransactions&pluginaction=getTransactions',
        baseParams: { limit: clientexec.records_per_view, sort: 'id', dir: 'asc'},
        root: 'data',
        columns: [{
            id: 'transactiondate',
            text: clientexec.lang('Date'),
            dataIndex: 'transactiondate',
            width: 175,
            sortable: true
        },{
            id: "invoiceid",
            text: clientexec.lang("Invoice"),
            dataIndex: "invoiceid",
            width: 70,
            align: 'center',
            renderer: function(val, row) {
                var url = "index.php?controller=invoice&fuse=billing&frmClientID="+row.userid+"&view=invoice&invoiceid="+val;
                return "<a href='"+url+"'>#"+val+"</a>";
            },
            sortable: false
        },{
            id: "transactionid",
            text: clientexec.lang("Transaction ID"),
            dataIndex: "transactionid",
            align: 'right',
            width: 250,
            sortable: true
        },{
            id: 'desc',
            text: clientexec.lang('Description'),
            dataIndex: 'response',
            flex: 1
        },{
            id: 'gateway',
            text: clientexec.lang('Gateway'),
            dataIndex: 'pluginused',
            width: 100,
            sortable: true
        },{
            id: 'amount',
            text: clientexec.lang('Amount'),
            dataIndex: 'amount',
            width: 75,
            sortable: true
        }]
    });
    transactions.grid.render();

    $('#transactions-grid-filter').change(function(){
        transactions.grid.reload({params:{start:0,limit:$(this).val()}});
    });

});