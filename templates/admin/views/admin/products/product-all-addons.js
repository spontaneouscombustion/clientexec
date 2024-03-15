productview.addons = {};

productview.all_addons_load = function() {
    $('#product-tab-content').load('index.php?nolog=1&fuse=admin&controller=products&view=addonstab&groupid='+productview.groupid+'&productid='+productview.productid, productview.postloadactions_addons);
};

productview.postloadactions_addons = function(e)
{
    clientexec.postpageload('#product-tab-content');

    productview.addons.columns= [
        {
            id:        'cb',
            dataIndex: 'id',
            xtype:     'checkbox'
        },{
            id: 'drag',
            xtype: 'drag'
        },{
            id:        'name',
            dataIndex: 'name',
            text:      clientexec.lang('Name'),
            sortable:  false,
            align:'left',
            renderer: function (text,record) {
                record.description = record.description.replace(/\"/g, "&quot;")
                return '<span class="tip-target" data-toggle="tooltip" data-html="true" data-placement="right" title="'+record.description+'" style="word-wrap:break-word;overflow:hidden;z-index:100">'+text+"</span>";
            }
    },{
        id:        'plugin_var',
        dataIndex: 'plugin_var',
        text:      clientexec.lang('Plugin Variable'),
        sortable:  false,
        width: '490',
        align:'right'
    },{
        id:        'type',
        dataIndex: 'type',
        text:      clientexec.lang('Display Type'),
        sortable:  false,
        align:'right',
        renderer: function (type,record) {
            var typetest = '';

            switch(type) {
                case '0':
                    typetest = clientexec.lang("Drop-down menus");
                    break;
                case '1':
                    typetest = clientexec.lang("Radio buttons");
                    break;
                case '2':
                    typetest = clientexec.lang("Quantity");
                    break;
            }
            return typetest;
        }
    },{
        id: 'id',
        dataIndex:'id',
        hidden:true
    }
    ];

    productview.addons.grid = new RichHTML.grid({
        el: 'addons-list',
        url: 'index.php?fuse=admin&controller=products&action=getaddonsforproduct&productid='+productview.productid,
        root: 'addon',
        columns: productview.addons.columns
    });
    productview.addons.grid.render();

    // **** listeners to grid
    $(productview.addons.grid).bind({
        "rowselect": function(event,data) {
            if (data.totalSelected > 0) {
                $('#btnDelAddon').removeAttr('disabled');
            } else {
                $('#btnDelAddon').attr('disabled','disabled');
            }
        },
        "drop": function(event,data){
            $.ajax({
                url: 'index.php?fuse=admin&controller=products&action=saveproductaddonorder&productid='+productview.productid,
                dataType: 'json',
                type: 'POST',
                data: {sessionHash: gHash,ids:productview.addons.grid.getRowValues('id')},
                success: function(data) {
                    data = ce.parseResponse(data);
                }
            });
        }
    });

    $('#product-addons-toadd').on('change',function(e){
        if (e.val == -1) {
            $('#btnAddAddon').attr('disabled','disabled');
        } else {
            $('#btnAddAddon').removeAttr('disabled');
        }
    });

    $('#btnAddAddon').bind('click',function(e){
        e.preventDefault();
        RichHTML.msgBox('<b>'+clientexec.lang('WARNING!')+'</b>'+'<br><br><ul><li>'+clientexec.lang("If the addon is been added, existing clients will be applied the default value: 'None' option if available, or the first option if not.")+'</li><li>'+clientexec.lang("If the addon is been updated to quantity type, existing clients will use a quantity of 1 by default.")+'</li><li>'+clientexec.lang("If the addon is been updated from quantity type to a different type, existing clients will change their quantity to 1.")+'</li></ul>'+clientexec.lang('Are you sure you want to add/update the selected addon?'),
            {type:"yesno"}, function(result) {
                if(result.btn === clientexec.lang("Yes")) {
                    productview.addons.grid.disable();
                    $.post("index.php?fuse=admin&controller=products&action=addaddontoproduct", {
                        productid:productview.productid,
                        id:$('#product-addons-toadd').val(),
                        type:$('#product-addons-type').val()
                    },
                    function(data){
                        data = ce.parseResponse(data);
                        productview.addons.grid.reload({ params:{start:0} });
                    });
                    $("#product-addons-toadd").select2("val", "-1");
                }
            });
    });

    $('#btnDelAddon').bind('click',function(e){
        if ($(this).attr('disabled')) { return false; }
        e.preventDefault();
        RichHTML.msgBox('<b>'+clientexec.lang('NOTICE:')+'</b>'+'<br><br>'+clientexec.lang('Do you want existing clients to continue being charged this addon?'),
            {type:"confirm"}, function(result) {
                if(result.btn === clientexec.lang("Yes")) {
                    productview.addons.grid.disable();
                    $.post("index.php?fuse=admin&controller=products&action=deleteaddonsfromproduct", {
                        keeprecurringfees:1,
                        productid:productview.productid,
                        ids:productview.addons.grid.getSelectedRowIds()
                    },
                    function(data){
                        productview.addons.grid.reload({ params:{start:0} });
                    });
                }else if(result.btn === clientexec.lang("No")) {
                    productview.addons.grid.disable();
                    $.post("index.php?fuse=admin&controller=products&action=deleteaddonsfromproduct", {
                        keeprecurringfees:0,
                        productid:productview.productid,
                        ids:productview.addons.grid.getSelectedRowIds()
                    },
                    function(data){
                        productview.addons.grid.reload({ params:{start:0} });
                    });
                }
            });
    });

};
