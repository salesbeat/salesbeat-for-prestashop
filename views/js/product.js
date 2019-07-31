$(function () {
    function initSBWidgetOnProduct() {
        if (!$('#salesbeat_product').length) {
            return;
        }

        SB.init({
            token: sb.token,
            price_to_pay: getPriceToPay(),
            price_insurance: getPriceInsurance(),
            weight: getWeight(),
            x: getWidth(),
            y: getHeight(),
            z: getLength(),
            quantity: getQantity(),
            city_by: 'ip',
            params_by: 'params',
            main_div_id: 'salesbeat_product',
            callback: function(){
                //console.log('Salesbeat is ready!');
            }
        });
    }

    function getCombination() {
        var id = null;
        if ($('#product-details').length) {
            var data = $('#product-details').data('product');
            id = data.id_product_attribute;
        }
        if ($('#idCombination').length) {
            id = $('#idCombination').val();
        }
        return parseInt(id);
    }

    function getQantity() {
        var qty = sb.product.quantity;

        if (getCombination()) {
            qty = sb.combinations[getCombination()].quantity;
        }

        if ($('#quantity_wanted').length) {
            qty = $('#quantity_wanted').val();
        }
        return qty;
    }

    function getWidth() {
        var width = sb.product.width;

        if (getCombination()) {
            width = sb.combinations[getCombination()].width;
        }

        return width;
    }

    function getHeight() {
        var height = sb.product.height;

        if (getCombination()) {
            height = sb.combinations[getCombination()].height;
        }

        return height;
    }

    function getLength() {
        var length = sb.product.length;

        if (getCombination()) {
            length = sb.combinations[getCombination()].length;
        }

        return length;
    }

    function getWeight() {
        var weight = sb.product.weight;

        if (getCombination()) {
            weight = sb.combinations[getCombination()].weight;
        }

        return weight;
    }

    function getPriceToPay() {
        if (sb.cash_on_delivery) {
            return 0;
        }

        var price = sb.product.price;
        if (getCombination()) {
            price = sb.combinations[getCombination()].price;
        }

        return price * getQantity();
    }

    function getPriceInsurance() {
        var price = sb.product.price;
        if (getCombination()) {
            price = sb.combinations[getCombination()].price;
        }

        return price * getQantity();
    }

    initSBWidgetOnProduct();

    if (typeof prestashop != 'undefined') {
        prestashop.on('updatedProduct', function (e) {
            //e.id_product_attribute
            initSBWidgetOnProduct();
        });
    }
    window.oldFindCombinationSB = window.findCombination;

    window.findCombination = function(firstTime)
    {
        oldFindCombinationSB(firstTime);
        initSBWidgetOnProduct();
    };
});