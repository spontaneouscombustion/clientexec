var notifications = {
    grid:                          {},
    window:                        {},
    selectNotificationsGridFilter: $('#selectNotificationsGridFilter'),
    buttonAdd:                     $('#buttonAdd'),
    buttonEnable:                  $('#buttonEnable'),
    buttonDisable:                 $('#buttonDisable'),
    buttonReset:                   $('#buttonReset'),
    buttonDelete:                  $('#buttonDelete'),
    divNotificationsGrid:          $('#divNotificationsGrid'),
    changeNotificationStatus:      function(status) {
        $.post(
            'index.php?fuse=admin&action=enabledisablenotifications&controller=notifications',
            {
                items:   notifications.grid.getSelectedRowIds(),
                enabled: status
            },
            function(data){
                notifications.grid.reload({params:{start:0}});
                ce.parseActionResponse(data);
            }
        );
    }
};

$(document).ready(function() {

    notifications.divNotificationsGrid.on('click', 'a.notification-name', function() {
        notifications.window.show({
            params: {
                id: $(this).attr('data-notification-id')
            }
        });
    });

    notifications.grid = new RichHTML.grid({
        el: 'divNotificationsGrid',
        url: 'index.php?fuse=admin&action=getnotifications&controller=notifications',
        root: 'notifications',
        baseParams: { limit: clientexec.records_per_view },
        columns: [
            {
                text: "",
                xtype: "expander",
                dataIndex: "appliesto",
                escapeHTML: true,
                renderOnExpand: true,
                renderer: function(text, row) {
                    notifications.grid.disable();
                    var html = "<div class='notificationdetails'>";

                    $.ajax({
                        url: 'index.php?fuse=admin&action=notificationdetails&controller=notifications',
                        dataType: 'json',
                        async: false,
                        data: {
                            id : row.id
                        },
                        success: function(json) {
                            html += "<b>"+json.appliesto+"</b><br/>";
                            html += json.appliestodetails;
                            html += "</div>";
                        }
                    });

                    notifications.grid.enable();
                    return html;

                }
            },{
                id:        'cb',
                dataIndex: 'id',
                xtype:     'checkbox'
            }, {
                id:        'name',
                dataIndex: 'name',
                align: 'left',
                text:      clientexec.lang('Event Name'),
                sortable:  true,
                renderer:  function(text, row) {
                    return '<a class="notification-name" data-notification-id="'+row.id+'">' +
                        ( row.enabled == 0 ? '<span style="color: gray; font-style: italic;">' + row.name + '</span>' : row.name ) +
                        '</a>'
                    ;
                },
                flex: 1
            },{
                id: 'fromName',
                text: clientexec.lang('From Name'),
                dataIndex: 'fromName',
                sortable: false,
                align: 'center',
            },{
                id: 'fromEmail',
                text: clientexec.lang('From Email'),
                dataIndex: 'fromEmail',
                sortable: false,
                align: 'center',
            },{
                id: 'count',
                text: '# '+clientexec.lang('Notified'),
                dataIndex: 'count',
                sortable: false,
                align: 'center',
                width: 70
            }, {
                id: 'datenotified',
                text: clientexec.lang('Last Notified'),
                dataIndex: 'datenotified',
                sortable: false,
                align: 'center',
                width: 140
            },{
                id:        'enabled',
                text:      clientexec.lang('Enabled'),
                dataIndex: 'enabled',
                sortable:  true,
                align:     'center',
                width:     70,
                renderer:  function(text,row) {
                    if ( row.enabled == 0 ) {
                        return clientexec.lang('No')+"&nbsp;&nbsp;";
                    } else {
                        return clientexec.lang('Yes')+"&nbsp;&nbsp;";
                    }
                }
            }
        ]
    });
    notifications.grid.render();

    notifications.window = new RichHTML.window({
        id:         'notification-window',
        grid:       notifications.grid,
        height:     '520',
        width:      '815',
        url:        'index.php?fuse=admin&view=notification&controller=notifications',
        actionUrl:  'index.php?fuse=admin&action=savenotification&controller=notifications',
        showSubmit: true,
        title:      clientexec.lang('Notification Window')
    });

    notifications.buttonAdd.click(function(){
        notifications.window.show();
    });


    notifications.selectNotificationsGridFilter.change(function(){
        notifications.grid.reload({
            params:{
                start: 0,
                limit: $(this).val()
            }
        });
    });

    $(notifications.grid).bind({
        'rowselect': function(event,data) {
            if (data.totalSelected > 0) {
                var selectedRowData = notifications.grid.getSelectedRowData();
                var arrayLength = selectedRowData.length;
                var showEnable = true;
                var showDisable = true;
                for (var idx = 0; idx < arrayLength; idx++) {
                    if(selectedRowData[idx].enabled == 0){
                        showDisable = false;
                    }else{
                        showEnable = false;
                    }
                }

                if(showEnable){
                    notifications.buttonEnable.prop('disabled', false);
                }else{
                    notifications.buttonEnable.prop('disabled', true);
                }

                if(showDisable){
                    notifications.buttonDisable.prop('disabled', false);
                    notifications.buttonReset.prop('disabled', false);
                }else{
                    notifications.buttonDisable.prop('disabled', true);
                    notifications.buttonReset.prop('disabled', true);
                }

                notifications.buttonDelete.prop('disabled', false);
            } else {
                notifications.buttonEnable.prop('disabled', true);
                notifications.buttonDisable.prop('disabled', true);
                notifications.buttonReset.prop('disabled', true);
                notifications.buttonDelete.prop('disabled', true);
            }
        }
    });

    notifications.buttonReset.click(function() {
        RichHTML.msgBox(
            clientexec.lang('Are you sure you want to reset the selected notification(s)?<br>Doing so will allow the notification(s) to email again the previously ignored clients.'),
            {
                type: 'yesno'
            }, function(result) {
                if (result.btn === clientexec.lang('Yes')) {
                    $.post(
                        'index.php?fuse=admin&action=resetnotifications&controller=notifications',
                        {
                            items: notifications.grid.getSelectedRowIds()
                        },
                        function (data) {
                            notifications.grid.reload({params:{start:0}});
                            ce.parseActionResponse(data);
                        }
                    );
                }
            }
        );
    });

    notifications.buttonDelete.click(function() {
        RichHTML.msgBox(
            clientexec.lang('Are you sure you want to delete the selected notification(s)?'),
            {
                type: 'yesno'
            }, function(result) {
                if (result.btn === clientexec.lang('Yes')) {
                    $.post(
                        'index.php?fuse=admin&action=deletenotifications&controller=notifications',
                        {
                            items: notifications.grid.getSelectedRowIds()
                        },
                        function (data) {
                            notifications.grid.reload({params:{start:0}});
                            ce.parseActionResponse(data);
                        }
                    );
                }
            }
        );
    });

    notifications.buttonEnable.click(function () {
        notifications.changeNotificationStatus(1);
    });

    notifications.buttonDisable.click(function () {
        notifications.changeNotificationStatus(0);
    });

});
