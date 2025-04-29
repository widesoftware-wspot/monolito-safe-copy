$('#wideti_AdminBundle_usuarios_role').val(5);

$('#wideti_AdminBundle_usuarios_status').val(1);

$('#wideti_AdminBundle_panel_usuarios_username').change(function() {
    $.ajax({
        type: "POST",
        url: Routing.generate('panel_user_check_email', {
            mail : $('#wideti_AdminBundle_panel_usuarios_username').val()
        }),
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.value) {

                $('#wideti_AdminBundle_panel_usuarios_username')
                    .val('')
                    .focus();

                $('#message-error')
                    .text('E-mail já cadastrado na base de dados')
                    .addClass('label label-important')
                    .show('slow');
            } else {
                $('#message-error')
                    .text('')
                    .removeClass('label label-important')
                    .css({ 'display' : 'none' });
            }
        }
    });
});