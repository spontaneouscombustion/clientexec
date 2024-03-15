var currencies = {
    dom: {
        buttonSetDefault: $('#button-setDefaultCurrency'),
        buttonDelete: $('#button-deleteCurrency'),
        buttonAdd: $('#button-addCurrency')
    },
    grid: new RichHTML.grid({
        el: 'div-currencies-grid',
        url: 'index.php?fuse=billing&controller=currency&action=get',
        root: 'currencies',
        baseParams: {
            sort: 'name', dir: 'asc'
        },
        columns: [
            {
                xtype: 'checkbox',
                id: 'select',
                dataIndex: 'id'
            },
            {
                id: 'name',
                dataIndex: 'name',
                text: clientexec.lang('Currency Name'),
                renderer: function(text, row) {
                    text = '<a onclick="currencies.window.show({params: { id: ' + row.id + '}});">' + text + '</a>';
                    if (row.isdefault) { text = text + ' <span class="default">('+clientexec.lang('Default')+')' }
                    return text;
                },
                flex: 1
            },
            {
                id: 'count',
                dataIndex: 'count',
                text: clientexec.lang('Count'),
                align: 'center',
                width: 37
            },
            {
                id: 'symbol',
                dataIndex: 'symbol',
                text: clientexec.lang('Symbol'),
                align: 'center',
                width: 44
            },
            {
                id: 'code',
                dataIndex: 'abrv',
                text: clientexec.lang('Code'),
                align: 'center',
                width: 32
            },
            {
                id: 'alignment',
                dataIndex: 'alignment',
                text: clientexec.lang('Alignment'),
                width: 60
            },
            {
                id: 'precision',
                dataIndex: 'precision',
                text: clientexec.lang('Precision'),
                align: 'center',
                width: 53
            },
            {
                id: 'rate',
                dataIndex: 'rate',
                text: clientexec.lang('Rate'),
                align: 'center',
                width: 95
            }
        ]
    })
};

$(document).ready(function(){

    currencies.dom.buttonSetDefault.click(function(){
        if ( currencies.grid.getSelectedRowIds().length > 1 ) {
            RichHTML.error(clientexec.lang('Please select only one currency for the default'));
        } else {
            $.post(
                'index.php?fuse=billing&action=setdefault&controller=currency',
                { id: currencies.grid.getSelectedRowIds() },
                function (response) {
                    currencies.grid.reload();
                }
            );
        }
    });

    currencies.dom.buttonDelete.click(function(){
        RichHTML.msgBox(clientexec.lang('Are you sure you want to delete the selected currencies?'),
        {
            type:"confirm"
        }, function(result) {
            if ( result.btn === clientexec.lang("Yes") ) {
                $.post(
                    'index.php?fuse=billing&action=delete&controller=currency',
                    { ids: currencies.grid.getSelectedRowIds() },
                    function (response) {
                        currencies.grid.reload();
                    }
                );
            }
        });
    });

    currencies.dom.buttonAdd.click(function(){
        currencies.window.show();
    });

    currencies.window = new RichHTML.window({
        height: '210',
        width: '290',
        grid: currencies.grid,
        url: 'index.php?fuse=billing&view=currency&controller=currency',
        actionUrl: 'index.php?fuse=billing&action=save&controller=currency',
        showSubmit: true,
        title: clientexec.lang("Add/Edit Currency")
    });
    currencies.grid.render();
});
