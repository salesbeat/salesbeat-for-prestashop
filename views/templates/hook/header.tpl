{*
* 2007-2017 PrestaShop
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
* @copyright 2007-2017 Goryachev Dmitry
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<script type="text/javascript">
    var sb = {$sb|json_encode nofilter};
    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}
        {if !isset($priceDisplayPrecision)}
            {assign var='priceDisplayPrecision' value=2}
        {/if}
        var priceDisplayPrecision = {$priceDisplayPrecision|intval};
        var currencyBlank = {$sb.def_currency->blank|intval};
        var currencyFormat = {if !$sb.def_currency->format|intval}2{else}{$def_currency->format|intval}{/if};
        var currencyRate = {$sb.def_currency->conversion_rate|floatval};
        var currencySign = "{$sb.def_currency->sign|escape:'quotes':'UTF-8'}";
    {/if}
</script>
