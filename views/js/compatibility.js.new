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

if (typeof $.fn.live == 'undefined')
    $.fn.live = $.fn.on;

function formatCurrency(price, currencyFormat, currencySign, currencyBlank)
{
    // if you modified this function, don't forget to modify the PHP function displayPrice (in the Tools.php class)
    var blank = '';
    price = parseFloat(price).toFixed(10);
    price = ps_round(price, priceDisplayPrecision);
    if (currencyBlank > 0)
        blank = ' ';
    if (currencyFormat == 1)
        return currencySign + blank + formatNumber(price, priceDisplayPrecision, ',', '.');
    if (currencyFormat == 2)
        return (formatNumber(price, priceDisplayPrecision, ' ', ',') + blank + currencySign);
    if (currencyFormat == 3)
        return (currencySign + blank + formatNumber(price, priceDisplayPrecision, '.', ','));
    if (currencyFormat == 4)
        return (formatNumber(price, priceDisplayPrecision, ',', '.') + blank + currencySign);
    if (currencyFormat == 5)
        return (currencySign + blank + formatNumber(price, priceDisplayPrecision, '\'', '.'));
    return price;
}

function ps_round(value, places)
{
    if (typeof(roundMode) === 'undefined')
        roundMode = 2;
    if (typeof(places) === 'undefined')
        places = 2;

    var method = roundMode;

    if (method === 0)
        return ceilf(value, places);
    else if (method === 1)
        return floorf(value, places);
    else if (method === 2)
        return ps_round_half_up(value, places);
    else if (method == 3 || method == 4 || method == 5)
    {
        // From PHP Math.c
        var precision_places = 14 - Math.floor(ps_log10(Math.abs(value)));
        var f1 = Math.pow(10, Math.abs(places));

        if (precision_places > places && precision_places - places < 15)
        {
            var f2 = Math.pow(10, Math.abs(precision_places));
            if (precision_places >= 0)
                tmp_value = value * f2;
            else
                tmp_value = value / f2;

            tmp_value = ps_round_helper(tmp_value, roundMode);

            /* now correctly move the decimal point */
            f2 = Math.pow(10, Math.abs(places - precision_places));
            /* because places < precision_places */
            tmp_value /= f2;
        }
        else
        {
            /* adjust the value */
            if (places >= 0)
                tmp_value = value * f1;
            else
                tmp_value = value / f1;

            if (Math.abs(tmp_value) >= 1e15)
                return value;
        }

        tmp_value = ps_round_helper(tmp_value, roundMode);
        if (places > 0)
            tmp_value = tmp_value / f1;
        else
            tmp_value = tmp_value * f1;

        return tmp_value;
    }
}

function ps_round_helper(value, mode)
{
    // From PHP Math.c
    if (value >= 0.0)
    {
        tmp_value = Math.floor(value + 0.5);
        if ((mode == 3 && value == (-0.5 + tmp_value)) ||
            (mode == 4 && value == (0.5 + 2 * Math.floor(tmp_value / 2.0))) ||
            (mode == 5 && value == (0.5 + 2 * Math.floor(tmp_value / 2.0) - 1.0)))
            tmp_value -= 1.0;
    }
    else
    {
        tmp_value = Math.ceil(value - 0.5);
        if ((mode == 3 && value == (0.5 + tmp_value)) ||
            (mode == 4 && value == (-0.5 + 2 * Math.ceil(tmp_value / 2.0))) ||
            (mode == 5 && value == (-0.5 + 2 * Math.ceil(tmp_value / 2.0) + 1.0)))
            tmp_value += 1.0;
    }

    return tmp_value;
}

//return a formatted number
function formatNumber(value, numberOfDecimal, thousenSeparator, virgule)
{
    value = value.toFixed(numberOfDecimal);
    var val_string = value+'';
    var tmp = val_string.split('.');
    var abs_val_string = (tmp.length === 2) ? tmp[0] : val_string;
    var deci_string = ('0.' + (tmp.length === 2 ? tmp[1] : 0)).substr(2);
    var nb = abs_val_string.length;

    for (var i = 1 ; i < 4; i++)
        if (value >= Math.pow(10, (3 * i)))
            abs_val_string = abs_val_string.substring(0, nb - (3 * i)) + thousenSeparator + abs_val_string.substring(nb - (3 * i));

    if (parseInt(numberOfDecimal) === 0)
        return abs_val_string;
    return abs_val_string + virgule + (deci_string > 0 ? deci_string : '00');
}

function ps_round_half_up(value, precision)
{
    var mul = Math.pow(10, precision);
    var val = value * mul;

    var next_digit = Math.floor(val * 10) - 10 * Math.floor(val);
    if (next_digit >= 5)
        val = Math.ceil(val);
    else
        val = Math.floor(val);

    return val / mul;
}