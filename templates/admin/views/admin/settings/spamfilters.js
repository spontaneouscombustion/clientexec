var spamfilters = {
    dom: {
        buttonAdd: $('#addButton'),
        buttonDelete: $('#deleteButton')
    },
    grid: new RichHTML.grid({
        el: 'div-spamfilters-grid',
        url: 'index.php?fuse=support&controller=spamfilter&action=list',
        totalProperty: 'totalcount',
        baseParams: {
            limit: clientexec.records_per_view
        },
        columns: [{
               id: "cb",
            dataIndex: "id",
            xtype: "checkbox"
        }, {
            id: "value",
            dataIndex: "value",
            text: clientexec.lang("Value"),
            sortable: true,
            flex: 1,
            renderer: function(text, row) {
                    return "<a onclick='spamfilters.window.show({params:{id:"+row.id+"}});'>"+text+"</a>";
                },
        },{
            id: "enabled",
            text:  clientexec.lang("Enabled?"),
            dataIndex: "enabled",
            sortable: true,
            renderer: function (text, row) {
                if (row.enabled == "1") {
                    return clientexec.lang('Yes');
                }
                return clientexec.lang('No');
            },
        }]
    })
};

spamfilters.window = new RichHTML.window({
    escClose: false,
    grid: spamfilters.grid,
    showSubmit: true,
    actionUrl: 'index.php?fuse=support&controller=spamfilter&action=save',
    width: '450',
    title: clientexec.lang("Manage Spam Filter"),
    url: 'index.php?fuse=support&controller=spamfilter&view=edit'
});

$(document).ready(function() {
    $('#spamfilters-grid-filter').change(function(){
        spamfilters.grid.reload({params:{start:0,limit:$(this).val()}});
    });

    $(spamfilters.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                spamfilters.dom.buttonDelete.prop('disabled', false);
            } else {
                spamfilters.dom.buttonDelete.prop('disabled', true);
            }
        }
    });

    spamfilters.grid.render();
    spamfilters.dom.buttonAdd.click(function() {
        spamfilters.window.show();
    });

    spamfilters.dom.buttonDelete.click(function () {
        RichHTML.msgBox(clientexec.lang('Are you sure you want to delete the selected spam filter(s)'),
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn === clientexec.lang("Yes") ) {
                $.post("index.php?fuse=support&controller=spamfilter&action=delete", {
                    ids: spamfilters.grid.getSelectedRowIds()
                },
                function(data){
                    spamfilters.grid.reload({params:{start:0}});
                });
            }
        });
    });
});