jQuery(document).ready(function (){
    DPDConnect.onParcelshopSelected = function (parcelshop) {
        // Store selected parcelshop
        jQuery.post(baseUri + '?fc=module&module=dpdconnect&controller=OneStepParcelshop', {
            'method': 'setParcelShop',
            'parcelId' : parcelshop.parcelShopId,
        });
    };

    jQuery('[id^="delivery_option_"]').each(function () {
        if (jQuery(this).prop("checked")) {
            if (this.value == parcelshopId + ',') {
                initMap();
            }
        }
    });

    //for opc
    if(orderProcess == 'order-opc') {
        jQuery(document).ajaxComplete(function (event, xhr, settings) {
            url = settings.url;
            if (!url.includes('dpdconnect')) {
                jQuery('[id^="delivery_option_"]').each(function () {
                    if (jQuery(this).prop("checked")) {
                        if (this.value == parcelshopId + ',') {
                            initMap();
                        }
                    }
                });
            }
        });
    }

    if(jQuery('.delivery_options').length !== 0 ) {
        if (!saturdaySenderIsAllowed) {
            jQuery('#opc_delivery_methods input.delivery_option_radio').each(function () {
                if (this.value === saturdaySender + ',' || this.value === classicSaturdaySender + ',') {
                    jQuery(this).parent().parent().parent().parent().remove();
                }
            });
        }

        jQuery("#page").on('change', '.delivery_option_radio', function () {
            if (jQuery(this).prop("checked")) {
                if (this.value == parcelshopId + ',') {
                    initMap();
                } else {
                    jQuery('#dpd-connect-container').hide();
                }
            }
        });
    }


}); // document ready

function initDivs() {
    if(jQuery('#dpd-connect-container').length == 0 ){
        jQuery('.delivery_options').append('<div id="dpd-connect-container"></div>');
    }
    if(jQuery('#dpd-connect-map-container').length == 0 ){
        jQuery('#dpd-connect-container').append('<div id="dpd-connect-map-container"></div>');
    }
    if(jQuery('#dpd-connect-selected-container').length == 0 ){
        jQuery('#dpd-connect-container').append('<div id="dpd-connect-selected-container" style="display: none;">' +
            'Geselecteerde parcelshop:<br />\n' +
            '<strong>%%company%%</strong><br />\n' +
            '%%street%% %%houseNo%%<br />\n' +
            '%%zipCode%% %%city%%<br />\n' +
            '<br \>' +
            '<a href="#" onclick="hideSelectedContainer(); showMapContainer();"><strong>Veranderen</strong></a>\n' +
            '</div>');
    }
}

function initMap() {
    if (
        jQuery('#dpd-connect-container').length > 0 &&
        jQuery('#dpd-connect-map-container').length > 0 &&
        jQuery('#dpd-connect-selected-container').length > 0
    ) {
        jQuery('#dpd-connect-container').show();
    } else {
        initDivs();

        if (gmapsKey != '') {
            DPDConnect.show(dpdPublicToken, shippingAddress, shopCountryCode, gmapsKey);
        } else {
            DPDConnect.show(dpdPublicToken, shippingAddress, shopCountryCode);
        }
    }
}

function hideSelectedContainer() {
    DPDConnect.getSelectedContainer().style.display = 'none';
}

function showMapContainer() {
    DPDConnect.getMapContainer().style.display = 'block';
}



