jQuery(document).ready(function(){
    if( $('#user_profile_two_factor_authentication_enabled').val() == 1 ){
        $('#two_factor_authentication_row').show();
        $('#two_factor_authentication_row_separator').show();

    }else{
        $('#two_factor_authentication_row').hide();
        $('#two_factor_authentication_row_separator').hide();
    }
});

jQuery(document).on('change', '#user_profile_two_factor_authentication_enabled', function(){
    if( $('#user_profile_two_factor_authentication_enabled').val() == 1 ){
        $('#two_factor_authentication_row').show();
        $('#two_factor_authentication_row_separator').show();
    }else{
        $('#two_factor_authentication_row').hide();
        $('#two_factor_authentication_row_separator').hide();
    }
});