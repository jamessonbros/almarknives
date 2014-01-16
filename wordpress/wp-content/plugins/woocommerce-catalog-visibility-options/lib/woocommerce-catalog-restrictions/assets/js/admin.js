jQuery(document).ready(function($) {
    jQuery("select.wc_restrictions_chosen_select").chosen();
    
    
    $('#_wc_restrictions').change(function() {
        if ($(this).val() == 'restricted') {
            $('#wc_catalog_restrictions_roles_container').show();
        } else {
            $('#wc_catalog_restrictions_roles_container').hide();
        }
    });
    
    $('#_wc_restrictions_purchase').change(function() {
        if ($(this).val() == 'restricted') {
            $('#wc_catalog_restrictions_purchase_roles_container').show();
        } else {
            $('#wc_catalog_restrictions_purchase_roles_container').hide();
        }
    });
    
     $('#_wc_restrictions_price').change(function() {
        if ($(this).val() == 'restricted') {
            $('#wc_catalog_restrictions_prices_roles_container').show();
        } else {
            $('#wc_catalog_restrictions_prices_roles_container').hide();
        }
    });
    
    $('#_wc_restrictions_location').change(function() {
        if ($(this).val() == 'restricted') {
            $('#wc_catalog_restrictions_locations_container').show();
        } else {
            $('#wc_catalog_restrictions_locations_container').hide();
        }
    });
   
});