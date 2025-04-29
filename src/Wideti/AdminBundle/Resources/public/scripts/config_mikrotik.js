jQuery("#mikrotik_generate").click(function(){
    var ssid                   = jQuery("#mikrotik_ssid").val();
    var network                = jQuery("#mikrotik_network").val();
    var admin_access_password  = jQuery("#mikrotik_admin_access_password").val();

    if (!admin_access_password) {
        jQuery("#admin_access_password-error").show();
    } else {
        jQuery("#admin_access_password-error").hide();

        $.ajax({
            type: "POST",
            url: Routing.generate('admin_configuration_generate_mikrotik_script'),
            data: {
                ssid: ssid,
                network: network,
                admin_access_password: admin_access_password
            },
            success: function(response)
            {
                jQuery("#mikrotik_script").val(response);
            }
        });
    }
});
