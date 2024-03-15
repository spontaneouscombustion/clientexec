var productview = {};

$(document).ready(
    function ()
    {
        productview.columns = [
            {
                id: 'cb',
                dataIndex: 'id',
                xtype: 'checkbox',
                renderer: function (text,record,el)
                {
                    if (record.id === 0)
                    {
                        el.addClass = "hide-checkbox";
                    }
                }
            },
            {
                id: 'name',
                dataIndex: 'name',
                text: clientexec.lang('Name'),
                sortable: false,
                align:'left',
                renderer: function (text,row) {
                    var name = row.name;

                    if (trim(name) === "") {
                        name = "<i>Not named</i>";
                    }

                    if (admin_edit_packagetypes) {
                        name = '<a href="index.php?fuse=admin&view=product&controller=products&groupid='+row.groupid+'&id='+row.id+'">'+name+'</a>';
                    }

                    return name;
                },
                flex: 1
            },
            {
                text: clientexec.lang("Late Fee"),
                width: 90,
                align: 'center',
                dataIndex: 'latefee',
                renderer: function (text,record)
                {
                    return productview_priceformatter(text, record, 'latefee');
                }
            },
            {
                text: clientexec.lang("Tax"),
                width: 50,
                align: 'center',
                dataIndex: 'taxable',
                renderer: function (text,record)
                {
                    if (record.id === 0) {
                        return "";
                    } else if (text === "1") {
                        return clientexec.lang("Yes");
                    }

                    return clientexec.lang("No");
                }
            },
            {
                text: clientexec.lang("Signup?"),
                width: 65,
                align: 'center',
                dataIndex: 'signup',
                renderer: function (text,record)
                {
                    if (record.id === 0) {
                        return "";
                    } else if (text === "1") {
                        return clientexec.lang("Yes");
                    } else if (text === "0") {
                        return clientexec.lang("Hidden");
                    }

                    return clientexec.lang("No");
                }
            }
        ];

        function productview_priceformatter(value, record, term)
        {
            var include = "";
            var font_class = "";

            if ((term === 'latefee' && value == 'N/A') || (term !== 'latefee' && ((typeof(record[term+"included"]) == "undefined") || (record[term+"included"] == 0)))){
                include = "price-muted";
            }

            if (value.length >= 15) {
                font_class = "xxlong-currency";
            } else if (value.length >= 13) {
                font_class = "xlong-currency";
            } else if (value.length >= 8) {
                font_class = "long-currency";
            }

            return "<span class='"+font_class+" "+include+" '>"+value+"</span>";
        }

        productview.deletegroup = function(groupid)
        {
            RichHTML.msgBox(
                clientexec.lang('Are you sure you want to delete this product group'),
                {
                    type:"yesno"
                },
                function (result)
                {
                    if (result.btn === clientexec.lang("Yes")) {
                        productview.grid.disable();
                        $.post(
                            "index.php?fuse=admin&controller=products&action=deleteproductgroup",
                            {
                                id:groupid
                            },
                            function (data)
                            {
                                ce.parseResponse(data);
                                productview.grid.reload();
                            }
                        );
                    }
                }
            );
        };

        productview.grid = new RichHTML.grid(
            {
                el: 'products-list',
                url: 'index.php?fuse=admin&controller=products&action=getproducts',
                root: 'results',
                groupField: 'group',
                columns: productview.columns
            }
        );

        productview.grid.render();

        productview.groupwindow = new RichHTML.window(
            {
                id: 'groupwindow',
                url: 'index.php?fuse=admin&controller=products&view=productgroup',
                actionUrl: 'index.php?fuse=admin&controller=products&action=saveproductgroup',
                width: '650',
                top: '100',  //This line was added because there seems to be an issue when no 'height' is given initially, and the window starts to low.
                left: '300', //This line was added because it seems it assume a value of 0 when you set a similar parameter like 'top'.
                grid: productview.grid,
                showSubmit: true,
                title: clientexec.lang("Product Group Window")
            }
        );

        //let's determine if we want to make the product groups editable
        productview.showgroupwindow = function(groupid, type)
        {
            height = 510;

            if (type == 3) {
                height = 550;
            }

            productview.groupwindow.setHeight(height);
            productview.groupwindow.show({params:{id:groupid}});
        };

        // **** listeners to grid
        $(productview.grid).bind(
            {
                "load" : function (event,data)
                {
                    $('#btnDelProduct').attr('disabled','disabled');
                    $('#products-list-grid-buttons').show();
                },
                "rowselect": function(event,data)
                {
                    if (data.totalSelected == 0) {
                        $('#btnDelProduct').attr('disabled','disabled');
                        $('#btnCloneProduct').attr('disabled','disabled');
                    } else if (data.totalSelected == 1) {
                        $('#btnDelProduct').removeAttr('disabled');
                        $('#btnCloneProduct').removeAttr('disabled');
                    } else {
                        $('#btnCloneProduct').attr('disabled','disabled');
                        $('#btnDelProduct').removeAttr('disabled');
                    }
                }
            }
        );

        $('#btnCloneProduct').click(
            function()
            {
                if ($(this).attr('disabled')) {
                    return false;
                }

                $.post(
                    "index.php?fuse=admin&controller=products&action=cloneproduct",
                    {
                        id: productview.grid.getSelectedRowIds()
                    },
                    function(data)
                    {
                        data = ce.parseResponse(data);

                        if (data.error) {
                            productview.grid.enable();
                        } else {
                            productview.grid.reload({ params:{start:0} });
                        }
                    }
                );
            }
        );

        $('#btnDelProduct').click(
            function ()
            {
                if ($(this).attr('disabled')) {
                    return false;
                }

                RichHTML.msgBox(
                    clientexec.lang('Are you sure you want to delete the selected product(s)'),
                    {
                        type:"yesno"
                    },
                    function(result)
                    {
                        if(result.btn === clientexec.lang("Yes")) {
                            productview.grid.disable();
                            $.post(
                                "index.php?fuse=admin&controller=products&action=deleteproducts",
                                {
                                    ids: productview.grid.getSelectedRowIds()
                                },
                                function(data)
                                {
                                    data = ce.parseResponse(data);

                                    if (data.error) {
                                        productview.grid.enable();
                                    } else {
                                        productview.grid.reload({ params:{start:0} });
                                    }
                                }
                            );
                        }
                    }
                );
            }
        );
    }
);