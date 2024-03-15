$(document).ready(function() {

    $('#importTLDButton').on('click', function (e) {
        e.preventDefault();
        RichHTML.mask();

        if ($(this).attr('disabled')) {
            return false;
        }

        $.post('index.php?fuse=clients&action=importtlds&controller=index', {
            registrar: $('#registrar').val(),
            group: $('#group').val(),
            margin: $('#margin').val(),
            override: $('#override').prop('checked')
        },function(data){
            RichHTML.unMask();
            json = ce.parseResponse(data);
            var message = json.data;
            $("#tld-import-result").append('<li>' + message + '</li>');
            $('#import-results-div').removeClass('hidden');
        });
    });
});