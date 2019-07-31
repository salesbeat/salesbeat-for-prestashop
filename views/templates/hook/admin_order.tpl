{*
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
*  @author Goryachev Dmitry <dariusakafest@gmail.com>
*  @copyright  2007-2018 Goryachev Dmitry
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel box">
    <h3>{l s='Delivery information' mod='salesbeat'}</h3>
    {if $delivery_info->salesbeat_order_id}
        <div class="row">
            <label class="control-label col-lg-4">
                {l s='Salesbeat order id' mod='salesbeat'}
            </label>
            <div class="col-lg-8">
                <span class="label label-info">{$delivery_info->salesbeat_order_id|escape:'quotes':'UTF-8'}</span>
            </div>
        </div>
    {/if}
    <div class="row">
        <label class="control-label col-lg-4">
            {l s='City' mod='salesbeat'}
        </label>
        <div class="col-lg-8">
            {*#{$delivery_info->city_code|escape:'quotes':'UTF-8'} -*}
            {$delivery_info->city_name|escape:'quotes':'UTF-8'}
        </div>
    </div>
    <div class="row">
        <label class="control-label col-lg-4">
            {l s='Region name' mod='salesbeat'}
        </label>
        <div class="col-lg-8">
            {$delivery_info->region_name|escape:'quotes':'UTF-8'} ({$delivery_info->short_name|escape:'quotes':'UTF-8'})
        </div>
    </div>
    {if !$delivery_info->pvz_id}
        {if $delivery_info->index}
            <div class="row">
                <label class="control-label col-lg-4">
                    {l s='Index' mod='salesbeat'}
                </label>
                <div class="col-lg-8">
                    {$delivery_info->index|escape:'quotes':'UTF-8'}
                </div>
            </div>
        {/if}
        <div class="row">
            <label class="control-label col-lg-4">
                {l s='Street' mod='salesbeat'}
            </label>
            <div class="col-lg-8">
                {$delivery_info->street|escape:'quotes':'UTF-8'}
            </div>
        </div>
        <div class="row">
            <label class="control-label col-lg-4">
                {l s='House block' mod='salesbeat'}
            </label>
            <div class="col-lg-8">
                {$delivery_info->house_block|escape:'quotes':'UTF-8'}
            </div>
        </div>
        <div class="row">
            <label class="control-label col-lg-4">
                {l s='Flat' mod='salesbeat'}
            </label>
            <div class="col-lg-8">
                {$delivery_info->flat|escape:'quotes':'UTF-8'}
            </div>
        </div>
        <div class="row">
            <label class="control-label col-lg-4">
                {l s='House' mod='salesbeat'}
            </label>
            <div class="col-lg-8">
                {$delivery_info->house|escape:'quotes':'UTF-8'}
            </div>
        </div>
    {/if}
    <div class="row">
        <label class="control-label col-lg-4">
            {l s='Delivery days' mod='salesbeat'}
        </label>
        <div class="col-lg-8">
            {$delivery_info->delivery_days|escape:'quotes':'UTF-8'}
        </div>
    </div>
    <div class="row">
        <label class="control-label col-lg-4">
            {l s='Delivery method' mod='salesbeat'}
        </label>
        <div class="col-lg-8">
            {*#{$delivery_info->delivery_method_id|escape:'quotes':'UTF-8'} - *}
            {$delivery_info->delivery_method_name|escape:'quotes':'UTF-8'}
        </div>
    </div>
    {if $delivery_info->pvz_id}
        <div class="row">
            <label class="control-label col-lg-4">
                {l s='Pvz' mod='salesbeat'}
            </label>
            <div class="col-lg-8">
                {*#{$delivery_info->pvz_id|escape:'quotes':'UTF-8'} -*}
                {$delivery_info->pvz_address|escape:'quotes':'UTF-8'}
            </div>
        </div>
    {/if}
    <div class="row">
        <label class="control-label col-lg-4">
            {l s='Delivery price' mod='salesbeat'}
        </label>
        <div class="col-lg-8">
            {$delivery_info->delivery_price|escape:'quotes':'UTF-8'}
        </div>
    </div>
    {if !$delivery_info->pvz_id}
        <div class="row">
            <label class="control-label col-lg-4">
                {l s='Comment' mod='salesbeat'}
            </label>
            <div class="col-lg-8">
                {$delivery_info->comment|escape:'quotes':'UTF-8'}
            </div>
        </div>
    {/if}
</div>

{if $is_admin}
    <div class="panel">
        <div class="panel-heading">
            {l s='Send order' mod='salesbeat'}
        </div>
        {if !$delivery_info->track_code}
            <div class="form-group clearfix">
                <div class="col-lg-12">
                    <form action="" method="post">
                        <button type="submit" name="sendOrder" class="btn btn-success">
                            {l s='Send order' mod='salesbeat'}
                        </button>
                        <button type="submit" name="sendOrderCashOnDelivery" class="btn btn-success">
                            {l s='Send order(cash on delivery)' mod='salesbeat'}
                        </button>
                    </form>
                </div>
            </div>
        {else}
            <div class="form-group clearfix">
                <div class="col-lg-12">
                    <form action="" method="post">
                        <button type="submit" name="deleteOrder" class="btn btn-danger">
                            {l s='Delete order' mod='salesbeat'}
                        </button>
                    </form>
                </div>
            </div>
        {/if}
    </div>
{/if}