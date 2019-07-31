/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Goryachev Dmitry    <dariusakafest@gmail.com>
 * @copyright 2007-2018 Goryachev Dmitry
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

$(function () {
    var delivery_option = null;
    var class_loading = 'sb_loading';
    //SB.init();
    setLoading();
    initSB();

    if (typeof window.refreshDeliveryOptions != 'undefined') {
        window.oldRefreshDeliveryOptionsSB = window.refreshDeliveryOptions;
        window.refreshDeliveryOptions = function () {
            setLoading();
            oldRefreshDeliveryOptionsSB();
            initSB();
        }
    }

    if (typeof window.updateCarriers != 'undefined') {
        window.oldupdateCarriersSB = window.updateCarriers;
        window.updateCarriers = function () {
            setLoading();
            oldupdateCarriersSB();
            initSB();
        }
    }

    if (typeof prestashop != 'undefined') {
        $('#js-delivery input').on('change', function () {
            var id_carrier = parseInt($(this).val());

            if (sb.ids.indexOf(id_carrier) != -1) {
                setLoading();
            }
        });

        prestashop.on('updatedDeliveryForm', function () {
            var id_carrier = getCurrentCarrierId();
            if (sb.ids.indexOf(id_carrier) != -1 && $('#sb-cart-widget').length) {
                return false;
            }
            initSB();
        });
    }

    $('.tab-pane input.delivery_option_radio').live('change', function () {
        var id_carrier = parseInt($(this).val());
        if (sb.ids.indexOf(id_carrier) != -1) {
            setLoading();
            initSB();
        }
    });

    $('#checkout-delivery-step .step-title, #checkout-delivery-step .step-edit').live('click', function () {
        setTimeout(function () {
            initSB();
        }, 300);
    });

    $(document).bind('sbCartWidgetRendered', function (data) {
        setDeliveryPrice(
            getCurrentCarrierId(),
            data.detail.delivery_price
        );

        saveDeliveryData(data.detail);
    });

    $(document).bind('sbDeliveryChanged', function (data) {
        setDeliveryPrice(
            getCurrentCarrierId(),
            data.detail.delivery_price
        );

        saveDeliveryData(data.detail);
    });

    $(document).bind('sbCityChanged', function (data) {
        console.log('sbCityChanged');
        saveDeliveryData(data.detail);
    });

    if (typeof Payment != 'undefined' && Payment.getByCountry != 'undefined') {
        Payment.oldGetByCountrySB = Payment.getByCountry;
        Payment.getByCountry = function (params) {
            initSB();
            Payment.oldGetByCountrySB(params);
        }
    }

    var ajax_init_sb = null;

    function initSB(carrier_id) {

        
        console.log('carrier_id', carrier_id);

        $('[name="confirmDeliveryOption"]').show();

        //console.warn('id carrier not found!');

        if (ajax_init_sb != null) {
            ajax_init_sb.abort();
            ajax_init_sb = null;
        }

        var id_carrier = getCurrentCarrierId();
        console.log('ID перевозчика ',id_carrier);
        if (typeof carrier_id != 'undefined') {
            console.log('ID перевозчика не нашли');
            id_carrier = carrier_id;
        }

        if (!id_carrier) {
            console.warn('id carrier not found!');
        }

        var id_current_carrier = parseInt(getCurrentCarrierId());
        $('.salesbeat_delivery_info, .salesbeat_delivery_row').remove();

        if (sb.ids.indexOf(id_current_carrier) !== -1) {
            $('[name="confirmDeliveryOption"]').hide();

            console.log('Пошла процедура вывода доставки');
            var data = getPlaceForInsert(id_current_carrier);
            delivery_option = data.delivery_option;
            var $place = data.there_insert;

            console.log('После чего добавляем контейнер', $place);

            var $delivery_info = $('<div id="sb-cart-widget" class="salesbeat_delivery_info"></div>');

            if (sb.is_ps_17) {
                $place.after('<div class="col-sm-12 salesbeat_delivery_row"><div></div></div>');
                $place = $place.parent().find('.salesbeat_delivery_row > div');
            }

            //$place.append($delivery_info);

            //$('#onepagecheckoutps_contenedor').append($delivery_info);


           $.fancybox({

                //'width':    1600,
               // 'height':   800,
               'autoSize' : true,
                'type':     'html',
                content:   $('#show-sb-cart-widget').show()
                    }),

            //$.fancybox.close();
        
            ajax_init_sb = $.ajax({
                url: document.location.href.replace('#' + document.location.hash, ''),
                type: 'post',
                dataType: 'json',
                data: {
                    sb_ajax: true,
                    method: 'get_cart_products',
                    id_carrier: id_carrier
                },
                success: function (json) {
                    clearLoading();
                    console.log('sb.carrier_cart[id_carrier]', sb.carrier_cart[id_carrier]);
                    initSBWidgetOnCart(
                        json.data.products,
                        sb.carrier_cart[id_carrier]
                    );
                }
            });
        }
    }

    function getPlaceForInsert(id_carrier) {
        var there_insert = null;
        var delivery_option = null;

        delivery_option = $('.delivery_option_radio[value="'+id_carrier+',"]');
        var delivery_option_elem = delivery_option.closest('tr').find('td');

        if (delivery_option_elem.length) {
            there_insert = delivery_option_elem.eq(2);
        }

        if (!delivery_option_elem.length) {
            console.log('salesbeat: default');
            delivery_option = $('#delivery_option_'+id_carrier);
            delivery_option_elem = delivery_option.closest('.delivery-option')
                .find('label[for="delivery_option_'+id_carrier+'"]');
            delivery_option_elem = delivery_option.closest('.delivery-option');
            //there_insert = delivery_option_elem;
            there_insert = delivery_option_elem;
        }

        //onepagecheckoutps 2.1.6
        if (!delivery_option_elem.length) {
            console.log('salesbeat: onepagecheckoutps 2.1.6');
            if(!testfancybox) {var testfancybox = 1;}
            if (testfancybox == 1) {$.fancybox.close();}
            delivery_option = $('.delivery_option_radio[value="'+id_carrier+',"]');
            delivery_option_elem = delivery_option.closest('.delivery_option');
            there_insert = delivery_option_elem;
        }

        //For quick order module
        if (!delivery_option_elem.length) {
            console.log('salesbeat: For quick order module');
            delivery_option = $('*[name=id_carrier][value=2'+id_carrier+'000]');
            delivery_option_elem = delivery_option.closest('tr').find('.carrier_infos');
            there_insert = delivery_option_elem;
        }

        //For onepagecheckout module
        if (!delivery_option_elem.length) {
            console.log('salesbeat: For onepagecheckout module');
            delivery_option = $('#id_carrier1'+id_carrier+'00');
            delivery_option_elem = delivery_option.closest('tr').find('.carrier_infos');
            there_insert = delivery_option_elem;
        }

        //For supercheckout module
        if (!delivery_option_elem) {
            console.log('salesbeat: For supercheckout module');
            delivery_option = $('.supercheckout_shipping_option[value="'+id_carrier+',"]');
            delivery_option_elem = delivery_option.closest('.highlight').find('.shipping_info');
            there_insert = delivery_option_elem;
        }

        //For 1.5 default theme
        if (!delivery_option_elem) {
            console.log('salesbeat: For 1.5 default theme');
            $('.delivery_option_radio').each(function () {
                if (parseInt($(this).val()) == id_carrier)
                    delivery_option = $(this);
            });
            delivery_option = delivery_option.closest('.delivery_option')
                .find('.resume tr td:nth-child(2)');
            there_insert = delivery_option_elem;
        }

        return {
            there_insert: there_insert,
            delivery_option: delivery_option
        };
    }

    function setLoading() {
        $.each(sb.ids, function (index, id_carrier) {
            var delivery_option = $('.delivery_option_radio[value="'+id_carrier+',"]');
            if (delivery_option.length) {
                delivery_option.closest('.delivery_option').addClass(class_loading);
                delivery_option.closest('.delivery_option');
            }

            if (!delivery_option.length) {
                delivery_option = $('#delivery_option_'+id_carrier);
                if (delivery_option.length) {
                    delivery_option.closest('.delivery-option').addClass(class_loading);
                }
            }
        });
    }

    function getCurrentCarrierId() {
        var delivery_option =  $('.delivery_option_radio[type="radio"]:checked');
        if (delivery_option.length && delivery_option.is(':visible')) {
            return parseInt(delivery_option.val());
        } else {
            var delivery_option = $('[id^="delivery_option_"][type="radio"]:checked:visible');
            if (delivery_option.length && delivery_option.is(':visible')) {
                return parseInt(delivery_option.val());
            }
        }
        return false;
    }

    function clearLoading() {
        $.each(sb.ids, function (index, id_carrier) {
            var delivery_option = $('.delivery_option_radio[value="'+id_carrier+',"]');
            if (delivery_option.length) {
                delivery_option.closest('.delivery_option').removeClass(class_loading);
            }

            if (!delivery_option.length) {
                delivery_option = $('#delivery_option_'+id_carrier);
                if (delivery_option.length) {
                    delivery_option.closest('.delivery-option').removeClass(class_loading);
                }
            }
        });
    }

    function setDeliveryPrice(id_carrier, price, no_format) {
        price = parseFloat(price);
        var price_f = formatCurrency(
            price,
            currencyFormat,
            currencySign,
            currencyBlank
        );
        if (typeof no_format != 'undefined' && no_format) {
            price_f = price;
        }

        $('*[name=id_carrier][value=2' + id_carrier + '000]')
            .closest('tr').find('.price, .carrier_price')
            .text(price_f);
        var data = getPlaceForInsert(id_carrier);
        var delivery_option = data.delivery_option;

        if (delivery_option.closest('.tab-pane').length) {
            delivery_option.closest('td.delivery_option_radio').parent()
                .find('.delivery_option_price, .carrier-price').text(price_f);
        } else {
            delivery_option.closest('.delivery_option, .delivery-option')
                .find('.delivery_option_price, .carrier-price').text(price_f);
        }

        $('#total_shipping').text(price_f);

        //For supercheckout module
        if ($('.supercheckout_shipping_option').length) {
            var id = $('.supercheckout_shipping_option[value="'+id_carrier+',"]')
                .attr('id');
            $('td:not(.shipping_info) > label[for="'+id+'"]').html(price_f);
            var sc_total_price = parseFloat($('#total_price_wfee').val()) + price;
            $('#total_price_without_tax').html(price_f);
        }

        var total_price = parseInt($('#total_product').text().replace(' ','')) + price;
        $('#total_price').text(price_f);
    }

    function initSBWidgetOnCart(products, data) {
        var cart = {
            token: sb.token,
            products: products,
            callback: function(data) {

                setDeliveryPrice(
                    getCurrentCarrierId(),
                    data.delivery_price
                );

                saveDeliveryData(data);

                sb.carrier_cart[getCurrentCarrierId()] = data;


                function paymentClick() {
                    var $payment_step = $('#checkout-payment-step .step-title');
                    if ($payment_step.length) {
                        $payment_step.click();
                    }
                }

                paymentClick();

                setTimeout(function () {
                    if (!$('.payment-options').is(':visible')) {
                        paymentClick();
                    }
                }, 1000);
            }
        };

        if (data.city_code) {
            cart.city_by = 'city_code';
            cart.city_code = data.city_code;
        } else {
            cart.city_by = 'ip';
        }
        //data.city_code = '8f9faad4-ff93-471d-b0c0-c8e5c0162dee';
        console.log('SB.init_cart data', data);
        console.log('SB.init_cart cart', cart);
        console.log('SB init =>', SB);
        if(!data){
            console.log('data пустое');
        }

        SB.init_cart(
            cart,
            data
        )
    }

    function saveDeliveryData(data, id_carrier, update_cart) {
        if (typeof id_carrier == 'undefined') {
            id_carrier = getCurrentCarrierId();
        }

        $.ajax({
            url: document.location.href.replace('#' + document.location.hash, ''),
            type: 'POST',
            dataType: 'json',
            data: {
                sb_ajax: true,
                method: 'save_delivery_data',
                data: data,
                id_carrier: id_carrier,
                update_cart: (typeof update_cart != 'undefined' ? parseInt(update_cart) : 0)
            }
        });
    }

    function getCities(postcode, city, callback) {
        var data = {
            token: sb.token
        };

        if (typeof postcode != 'undefined') {
            data.postcode = postcode;
        }
        if (typeof city != 'undefined') {
            data.city = city;
        }

        $.ajax({
            url: 'https://app.salesbeat.pro/api/v1/get_cities',
            type: 'GET',
            dataType: 'jsonp',
            data: data,
            success: function (json) {
                console.log('getCities json',json);
                callback(json);
            }
        });
    }

    window.sbContext = {
        getCities: getCities,
        saveDeliveryData: saveDeliveryData
    };
});

$(function () {
    var tmp_cities = {};

    $('[name="city"]').autocomplete({
        delay: 500,
        source: function (request, response) {
            window.sbContext.getCities(
                undefined,
                request.term,
                function (json) {
                    if (typeof json.cities != 'undefined') {
                        var cities = [];
                        tmp_cities = {};

                        $.each(json.cities, function (index, city) {
                            cities.push(city.region_name + ', ' + city.name);

                            tmp_cities[city.region_name + ', ' + city.name] = city;
                        });
                        response(cities);
                    }
                }
            );
        },
        select: function (event, ui) {
            var city = tmp_cities[ui.item.value];

            window.sbContext.saveDeliveryData({
                'city_code': city.id,
                'city_name': city.name,
                'region_name': city.region_name,
                'short_name': city.short_name
            }, sb.ids[0], 1);
        }
    });
});