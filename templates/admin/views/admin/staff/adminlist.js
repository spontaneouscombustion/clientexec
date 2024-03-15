var staffList = {};

$(document).ready(function() {
    staffList.grid = new RichHTML.grid({
        el: 'staff-grid',
        url: 'index.php?fuse=admin&controller=staff&action=stafflist',
        root: 'staff',
        groupField: 'groupname',
	    baseParams: { sort: 'id', dir: 'asc'},
        columns: [{
                id: "name",
                dataIndex: "name",
                text: clientexec.lang("Name"),
                align: "left",
                renderer: function(text, row) {
                    if (row.id == 0) {
                        return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+'<a href="#" data-role-id="'+row.groupid+'" class="label label-important addStaffLink"">'+clientexec.lang('No staff available - click to add your first staff member to this group')+'</a>';
                    } else {
                        return  "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+String.format("<a href='index.php?fuse=admin&view=editaddadminaccount&controller=staff&adminid={1}'>{0}</a>", ce.htmlspecialchars(row.name), row.id);
                    }
                },
                flex : 1
            },{
                id: "email",
                align: "center",
                text: clientexec.lang("Email"),
                dataIndex: "email",
                width: 250
            },{
                id: "staff",
                text: clientexec.lang("Status"),
                dataIndex: "status",
                align: "center",
                width: 70
            }
        ]
    });
    staffList.grid.render();

    staffList.window = new RichHTML.window({
        height: '300',
        width: '285',
        grid: staffList.grid,
        url: 'index.php?fuse=admin&view=addstaffform&controller=staff',
        actionUrl: 'index.php?fuse=admin&action=saveadmin&controller=staff',
        showSubmit: true,
        title: clientexec.lang("Add Staff")
    });

    $(document).on("click", '.addStaffLink', function(event){
        var role_id = $(this).attr("data-role-id");
        staffList.window.show({params:{role_id:role_id}});
    });

    $(document).on("click", '.addStaffRoleButton', function(event){

    });

    $(document).on("click", '.deleteRoleLink', function(event){
        var role_id = $(this).attr("data-role-id");
        RichHTML.msgBox(clientexec.lang('Are you sure you want to delete this role?'),
        {
            type:"yesno"
        }, function(result) {
            if(result.btn === clientexec.lang("Yes")) {
                $.post("index.php?fuse=admin&controller=roles&action=delete", {
                    ids:[role_id]
                },
                function(data){
                    staffList.grid.reload({params:{start:0}});
                });
            }
        });
    });

    $('.addStaffRoleButton').click(function() {
        var content = clientexec.lang("Role Name")+'<br/>'+'<input type="text" name="name" />';
        RichHTML.msgBox('', {
            type: 'prompt',
            content: content,
            buttons: {
                button1: {
                    text: clientexec.lang('Save')
                },
                button2: {
                    text: clientexec.lang('Cancel'),
                    type: 'cancel'
                }
            }
        },function (result) {
            if ( result.btn == clientexec.lang('Save') ) {
                $.post('index.php?fuse=admin&action=save&controller=roles', {
                    groupName: result.elements.name,
                    groupDescription: result.elements.description
                }, function(data) {
                    staffList.grid.reload();
                });
            }
        });
    });


});
