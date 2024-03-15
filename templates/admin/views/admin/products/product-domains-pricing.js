productview.tlds = {};

productview.domains_pricing_load = function (currency = 'default')
{
    $('#product-tab-content').load(
        'index.php?nolog=1&fuse=admin&controller=products&view=pricingtabfordomains&groupid='+productview.groupid+'&productid='+productview.productid+'&currency='+currency,
        productview.postloadactions_domainspricing
    );
};

productview.postloadactions_domainspricing = function ()
{
    productview.tlds.columns = [
        {
            id:        'cb',
            dataIndex: 'id',
            xtype:     'checkbox',
            renderer:  function (text,record,el)
            {
                if (record.period == "none") {
                    el.addClass = "hide-checkbox";
                }
            }
        },{
            id:        'period',
            dataIndex: 'period',
            text:      clientexec.lang('Billing Period(s)'),
            sortable:  false,
            align:     'left',
            flex:      1,
            renderer:  function (text,record)
            {
                var label = record.name;

                if (text ==  "none") {
                    label = "<span style='position: relative;left: -30px;' class='label label-important'>No pricing exists for this tld.  Click to add one.</span>";
                }

                return "<a href='javascript:void(0)' onclick='productview.tlds.showperiodwindow(\""+record.tldraw+"\",\""+text+"\");' class='link'>"+label+"</a>";
            }
        },{
            id:        'price',
            dataIndex: 'price',
            text:      clientexec.lang('Price'),
            sortable:  false,
            width:     '60',
            align:     'right',
            renderer:  function (text,record)
            {
                if (record.period == "none") {
                    return "";
                }

                return accounting.formatMoney(text, productview.currency.symbol, productview.currency.precision, productview.currency.thousandssep, productview.currency.decimalssep, productview.currency.alignment);
            }
        },{
            id:        'transfer',
            dataIndex: 'transfer',
            text:      clientexec.lang('Transfer'),
            sortable:  false,
            width:     '60',
            align:     'right',
            renderer:  function (text,record)
            {
                if (record.period == "none") {
                    return "";
                }

                if (text == '') {
                    return clientexec.lang('N/A')
                }

                return accounting.formatMoney(text, productview.currency.symbol, productview.currency.precision, productview.currency.thousandssep, productview.currency.decimalssep, productview.currency.alignment);
            }
        },{
            id:        'renew',
            dataIndex: 'renew',
            text:      clientexec.lang('Renew'),
            sortable:  false,
            width:     '60',
            align:     'right',
            renderer:  function (text,record)
            {
                if (record.period == "none") {
                    return "";
                }

                return accounting.formatMoney(text, productview.currency.symbol, productview.currency.precision, productview.currency.thousandssep, productview.currency.decimalssep, productview.currency.alignment);
            }
        }
    ];

    productview.tlds.grid = new RichHTML.grid(
        {
            el: 'tld-list',
            url: 'index.php?fuse=admin&controller=products&action=gettldsgrid&productId='+productview.productid+'&currency='+productview.currency.abrv,
            root: 'results',
            startCollapsed: true,
            columns: productview.tlds.columns
        }
    );
    productview.tlds.grid.render();
    //onload of grid do the following

    productview.tlds.periodwindow = new RichHTML.window(
        {
            id: 'groupwindow',
            url: 'index.php?fuse=admin&controller=products&view=tldperiod&currency='+productview.currency.abrv,
            actionUrl: 'index.php?fuse=admin&controller=products&action=savetldperiod&currency='+productview.currency.abrv,
            width: '320',
            height: '150',
            grid: productview.tlds.grid,
            showSubmit: true,
            title: clientexec.lang("TLD Setup Window")
        }
    );

    //let's determine if we want to make the product groups editable
    productview.tlds.showperiodwindow = function (tld,period)
    {
        if (!period) {
            period = "none";
        }

        productview.tlds.periodwindow.show(
            {
                params: {
                    period:    period,
                    tld:       tld,
                    productid: productview.productid,
                    currency:  productview.currency.abrv
                }
            }
        );
    };

    // **** listeners to grid
    $(productview.tlds.grid).bind(
        {
            "rowselect": function (event,data)
            {
                if (data.totalSelected > 0) {
                    $('#btnDelTLDPeriod').removeAttr('disabled');
                } else {
                    $('#btnDelTLDPeriod').attr('disabled','disabled');
                }
            }
        }
    );
    
    $('#taxdomainorders').click(
        function ()
        {
            $.post(
                "index.php?fuse=admin&controller=products&action=updatedomaintax",
                {
                    productid: productview.productid,
                    taxable:   $(this).is(':checked'),
                    currency:  productview.currency.abrv
                },
                function (data)
                {
                    json = ce.parseResponse(data);
                }
            );
        }
    );

    $('#latefee').change(
        function ()
        {
            $.post(
                "index.php?fuse=admin&controller=products&action=updatedomainlatefee",
                {
                    productid: productview.productid,
                    latefee:   $(this).val(),
                    currency:  productview.currency.abrv
                },
                function (data)
                {
                    json = ce.parseResponse(data);
                }
            );
        }
    );

    // **** lets bind our buttons
    $('#btnDelTLDPeriod').click(
        function ()
        {
            if ($(this).attr('disabled')) {
                return false;
            }

            RichHTML.msgBox(
                clientexec.lang('Are you sure you want to delete the selected TLD billing period(s)'),
                {
                    type:"yesno"
                },
                function (result)
                {
                    if(result.btn === clientexec.lang("Yes")) {
                        productview.tlds.grid.disable();
                        $.post(
                            "index.php?fuse=admin&controller=products&action=deleteTldPeriod",
                            {
                                productid: productview.productid,
                                ids:       productview.tlds.grid.getSelectedRowIds(),
                                currency:  productview.currency.abrv
                            },
                            function (data)
                            {
                                productview.tlds.grid.reload(
                                    {
                                        params: {
                                            start:0
                                        }
                                    }
                                );
                            }
                        );
                    }
                }
            );
        }
    );
};