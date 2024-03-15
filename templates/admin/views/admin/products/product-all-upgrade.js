productview.all_upgrade_load = function ()
{
    $('#product-tab-content').load(
        'index.php?nolog=1&fuse=admin&controller=products&view=upgradetab&groupid='+productview.groupid+'&productid='+productview.productid,
        productview.postloadactions_upgrade
    );
};

productview.postloadactions_upgrade = function ()
{
    $('.submit-upgrade').click(
        function (e)
        {
            e.preventDefault();
            var fielddata = $('#upgradetab').serializeArray();
            $.ajax(
                {
                    url: 'index.php?fuse=admin&controller=products&action=saveproductupgrade&packageId='+productview.productid+'&groupid='+productview.groupid,
                    type: 'POST',
                    data: fielddata,
                    success : function (xhr)
                    {
                        json = ce.parseResponse(xhr);
                    }
                }
            );
        }
    );
};