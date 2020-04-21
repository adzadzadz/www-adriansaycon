jQuery(document).on("change", "select[name=swcfpc_cf_auth_mode]", function() {

    var method = jQuery(this).val();

    if( method == 0 ) { // API Key
        jQuery(".api_token_method").addClass("swcfpc_hide");
        jQuery(".api_key_method").removeClass("swcfpc_hide");
    }
    else { // API Token
        jQuery(".api_token_method").removeClass("swcfpc_hide");
        jQuery(".api_key_method").addClass("swcfpc_hide");
    }

});