var profilecredithistory = profilecredithistory || {};

$(document).ready(function() {

    profilecredithistory.renderUser = function(text,row){
        text = ce.htmlspecialchars(text);
        return text.replace(/\[color=(\w+)\](.+)/, '<font color="$1">$2</font>');
    }

    profilecredithistory.renderLogAction  = function(text,row){
        var returnString = ce.htmlspecialchars(row.logaction);
        if (row.tpl == 'link') {
            returnString = returnString.replace(/\[link\]/, row.link);
        }

        if(row.tpl=="clickForBody"){
            returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'email');\" href='#'>view</a>)",row.logaction,row.entryid);
        }else if(row.tpl=="clickForStaffNote"){
            returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'note');\" href='#'>view</a>)",row.logaction,row.entryid);
        }else if(row.tpl=="clickForProfileDetails"){
            returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'profile');\" href='#'>view</a>)", row.logaction,row.entryid);
        }else if(row.tpl=="clickForPaypalCallbackDetails"){
            returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'paypal');\" href='#'>view</a>)", row.logaction,row.entryid);
        }else if(row.tpl=="clickFor2CheckoutCallbackDetails"){
            returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'2checkout');\" href='#'>view</a>)", row.logaction,row.entryid);
        }else if(row.tpl=="clickForPackageDetails"){
            returnString = String.format("{0} (<a onclick=\"clientexec.eventdetailwindow({1},'package');\" href='#'>view</a>)", row.logaction,row.entryid);
        }
        return returnString;
    };

    profilecredithistory.grid = new RichHTML.grid({
        el: 'credithistoryList-grid',
        totalProperty: 'totalcount',
        url: 'index.php?fuse=home&action=getcredithistory&controller=events',
        root: 'data',
        baseParams: {
            limit: clientexec.records_per_view,
            sort: 'logdate',
            dir: 'asc',
        },
        columns:[
        {
            id: 'event_id',
            width: 45,
            text: "Id",
            dataIndex : "event_id",
            align:"right"
        },
        {
            id: 'logdate',
            text: "Date",
            width: 100,
            dataIndex : "logdate",
            sortable: true,
            align:"left"
        },{
            id: 'loguser',
            text: "User",
            width: 140,
            dataIndex : "loguser",
            renderer: profilecredithistory.renderUser,
            sortable: true
        },
        {
            id: 'logaction',
            text: "Action",
            dataIndex : "logaction",
            renderer:   profilecredithistory.renderLogAction,
            sortable: true,
            flex: 1
        },{
            id: 'logipaddress',
            text: "IP Address",
            width: 150,
            dataIndex : "logipaddress",
            align:"center",
            sortable: true
        }]
    });


    profilecredithistory.grid.render();

    $('#credithistoryList-grid-filter').change(function(){
        profilecredithistory.grid.reload({params:{start:0,limit:$(this).val()}});
    });

});
