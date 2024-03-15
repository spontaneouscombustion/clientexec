var profileemails = profileemails || {};

$(document).ready(function() {

    function renderExpander(value, record, el) {
        return record.content;
    }

    function renderSender(value, record, el) {
        if (record.fromName == '') {
            return 'N/A';
        }
        return record.fromName + ' (' + record.sender + ')';
    }

    profileemails.grid = new RichHTML.grid({
        el: 'emailsList-grid',
        totalProperty: 'totalcount',
        url: 'index.php?fuse=clients&action=getemaillist&controller=email',
        root: 'data',
        baseParams: {
            limit: clientexec.records_per_view,
            sort: 'id',
            dir: 'desc',
            start: 0,
            customerId: clientexec.customerId
        },
        columns:[{
            text: "",
            xtype: "expander",
            escapeHTML: true,
            renderer:renderExpander,
        },{
            id: 'subject',
            text: clientexec.lang("Subject"),
            dataIndex : "subject",
            sortable: false,
            align:"left",
            flex: 1
        },{
            id: 'date',
            text: clientexec.lang("Date"),
            width: 240,
            dataIndex : "date",
            sortable: true,
            align:"center",
        },{
            id: 'to',
            text: clientexec.lang("To"),
            dataIndex : "to",
            width: 250,
            align:"center",
        },{
            id: 'sender',
            text: clientexec.lang("From"),
            dataIndex : "sender",
            width: 250,
            align:"center",
            renderer: renderSender
        }]
    });

    profileemails.grid.render();

    $('#emails-grid-filter').change(function(){
    	profileemails.grid.reload({params:{start:0,limit:$(this).val()}});
    });
});