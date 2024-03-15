if (typeof RedactorPlugins === 'undefined') var RedactorPlugins = {};

RedactorPlugins.clips = {

    init: function()
    {
        var clip_content_id = $($(this)[0]['$el'][0]).attr('data-clips-id');
        var callback = $.proxy(function()
        {
            $('#redactor_modal .redactor_clip_link').each($.proxy(function(i,s)
            {
                $(s).click($.proxy(function()
                {
                    this.insertClip($(s).next().html());

                    return false;

                }, this));

            }, this));


            this.saveSelection();
            this.setBuffer();

        }, this);

        if ($('#'+clip_content_id).length > 0 ) {
            this.addBtn('clips', 'Click to view available tags', function(obj)
            {
                obj.modalInit('Available Tags', '#'+clip_content_id, 500, callback);
            });
        }

    },
    insertClip: function(html)
    {
        this.restoreSelection();
        this.execCommand('inserthtml', html);
        this.modalClose();
    }

};

RedactorPlugins.rtl = {
    init: function()
    {
        var dropdown = {};
        dropdown.ltr = { title: 'Left to Right', exec: 'ltr' }
        dropdown.rtl = { title: 'Right to Left', exec: 'rtl' }
        this.addBtn('rtl', 'RTL-LTR', function() {}, dropdown);
    }
};