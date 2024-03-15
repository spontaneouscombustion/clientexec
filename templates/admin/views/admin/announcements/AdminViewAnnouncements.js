var announcements = {
    postDateValue:                 '', // assigned in template
    postTimeValue:                 '', // assigned in template
    grid:                          {},
    window:                        {},
    selectAnnouncementsGridFilter: $('#selectAnnouncementsGridFilter'),
    buttonAdd:                     $('#buttonAdd'),
    buttonPublish:                 $('#buttonPublish'),
    buttonUnpublish:               $('#buttonUnpublish'),
    buttonPin:                     $('#buttonPin'),
    buttonUnpin:                   $('#buttonUnpin'),
    buttonDelete:                  $('#buttonDelete'),
    divAnnouncementsGrid:          $('#divAnnouncementsGrid'),
    changeAnnouncementStatus:      function(status) {
        $.post(
            'index.php?fuse=admin&action=publishunpublishannouncements&controller=announcements',
            {
                items:   announcements.grid.getSelectedRowIds(),
                publish: status
            },
            function(data){
                announcements.grid.reload({params:{start:0}});
                ce.parseActionResponse(data);
            }
        );
    },
    changeAnnouncementPinned:      function(status) {
        $.post(
            'index.php?fuse=admin&action=togglepin&controller=announcements',
            {
                items:   announcements.grid.getSelectedRowIds(),
                pinned: status
            },
            function(data){
                announcements.grid.reload({params:{start:0}});
                ce.parseActionResponse(data);
            }
        );
    },

};

$(document).ready(function() {

    announcements.divAnnouncementsGrid.on('click', 'a.announcement-title', function() {
        announcements.window.show({
            params: {
                id: $(this).attr('data-announcement-id')
            }
        });

        // setting this inside the options obj above doesn't work
        $('.window-title').text(clientexec.lang('Edit') + ' - ' + $(this).text());
    });

    announcements.grid = new RichHTML.grid({
        el: 'divAnnouncementsGrid',
        url: 'index.php?fuse=admin&action=getannouncements&controller=announcements',
        root: 'announcements',
        totalProperty: 'totalcount',
        baseParams: { limit: clientexec.records_per_view },
        columns: [
            {
                id:        'cb',
                dataIndex: 'id',
                xtype:     'checkbox'
            }, {
                id:        'title',
                dataIndex: 'title',
                text:      clientexec.lang('Title'),
                sortable:  true,
                renderer:  function(text, row) {
                    return '<a class="announcement-title" data-announcement-id="'+row.id+'">' +
                        ( row.published == '0' ? '<span style="color: gray; font-style: italic;">' + ce.htmlspecialchars(row.title) + '</span>' : ce.htmlspecialchars(row.title) ) +
                        '</a>'
                    ;
                },
                align : 'left',
                flex: 1
            },{
                id:         'date',
                text:       clientexec.lang('Post Date'),
                width:      150,
                dataIndex:  'postdate',
                sortable:   true,
                align:      'center'
            },{
                id:        'published',
                text:      clientexec.lang('Published'),
                dataIndex: 'published',
                sortable:  true,
                width:     75,
                align:     'center',
                renderer:  function(text,row) {
                    if ( row.published == '0' ) {
                        return clientexec.lang('No');
                    } else {
                        return clientexec.lang('Yes');
                    }
                }
            },{
                id:         'pinned',
                text:       clientexec.lang('Pinned'),
                dataIndex:  'pinned',
                width:      75,
                sortable:   true,
                align:      'center',
                renderer:   function(text, row) {
                    if (row.pinned == '1') {
                        return clientexec.lang('Yes');
                    } else {
                        return clientexec.lang('No');
                    }
                },
            }
        ]
    });
    announcements.grid.render();

    announcements.window = new RichHTML.window({
        id:         'announcement-window',
        grid:       announcements.grid,
        height:     '590',
        width:      '760',
        url:        'index.php?fuse=admin&view=announcement&controller=announcements',
        actionUrl:  'index.php?fuse=admin&action=saveannouncement&controller=announcements',
        showSubmit: true,
        title:      clientexec.lang('Add Announcement'),
        onSubmit: function() {
          announcements.window.unMask();
          // do this otherwise the timepicker remains on screen (github issue #741)
          $('.timepicker').timepicker('hideWidget');
        }
    });

    announcements.buttonAdd.click(function(){
        announcements.window.show();
    });


    announcements.selectAnnouncementsGridFilter.change(function(){
        announcements.grid.reload({
            params:{
                start: 0,
                limit: $(this).val()
            }
        });
    });

    $(announcements.grid).bind({
        'rowselect': function(event,data) {
            if (data.totalSelected > 0) {

                var selectedRowData = announcements.grid.getSelectedRowData();
                var arrayLength = selectedRowData.length;
                var showPublish = true;
                var showUnpublish = true;
                var showPin = true;
                var showUnpin = true;
                for (var idx = 0; idx < arrayLength; idx++) {
                    if (selectedRowData[idx].published == '0') {
                        showUnpublish = false;
                    } else {
                        showPublish = false;
                    }

                    if (selectedRowData[idx].pinned == '0') {
                        showUnpin = false;
                    } else {
                        showPin = false;
                    }
                }

                if (showPublish) {
                    announcements.buttonPublish.prop('disabled', false);
                } else {
                    announcements.buttonPublish.prop('disabled', true);
                }

                if (showUnpublish) {
                    announcements.buttonUnpublish.prop('disabled', false);
                } else {
                    announcements.buttonUnpublish.prop('disabled', true);
                }

                if (showPin) {
                    announcements.buttonPin.prop('disabled', false);
                } else {
                    announcements.buttonPin.prop('disabled', true);
                }

                if (showUnpin) {
                    announcements.buttonUnpin.prop('disabled', false);
                } else {
                    announcements.buttonUnpin.prop('disabled', true);
                }

                announcements.buttonDelete.prop('disabled', false);
            } else {
                announcements.buttonPublish.prop('disabled', true);
                announcements.buttonUnpublish.prop('disabled', true);
                announcements.buttonPin.prop('disabled', true);
                announcements.buttonUnpin.prop('disabled', true);
                announcements.buttonDelete.prop('disabled', true);
            }
        }
    });

    announcements.buttonDelete.click(function() {
        RichHTML.msgBox(
            clientexec.lang('Are you sure you want to delete the selected announcement(s)'),
            {
                type: 'confirm'
            }, function(result) {
                if (result.btn === clientexec.lang('Yes')) {
                    $.post(
                        'index.php?fuse=admin&action=deleteannouncements&controller=announcements',
                        {
                            items: announcements.grid.getSelectedRowIds()
                        },
                        function (data) {
                            announcements.grid.reload({params:{start:0}});
                            ce.parseActionResponse(data);
                        }
                    );
                }
            }
        );
    });

    announcements.buttonPublish.click(function () {
        announcements.changeAnnouncementStatus(1);
    });

    announcements.buttonUnpublish.click(function () {
        announcements.changeAnnouncementStatus(0);
    });

    announcements.buttonPin.click(function () {
        announcements.changeAnnouncementPinned(1);
    });

    announcements.buttonUnpin.click(function () {
        announcements.changeAnnouncementPinned(0);
    });

});
