<?php
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2012-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class CreateSBMethod extends BaseSBMethod
{
    /**
     * @param Order $order
     * @param $cash_on_delivery
     */
    public function setRequest($order, $cash_on_delivery)
    {
        


        $delivery_info = SBCarrierCart::getInstanceByCarrierAndCart(
            $order->id_carrier,
            $order->id_cart
        );

        $customer = new Customer($order->id_customer);
        $address = new Address($order->id_address_delivery);
        $cart = new Cart($order->id_cart);

        $products_paimon = SalesBeat::formatSalesBeatProducts($cart);
        if($GLOBALS['total_discounts_paimon'] > 0){
            $products_paimon = SalesBeat::formatSalesBeatProductsDiscounts($cart);
        }

        $data_order = array(
            'secret_token' => ConfSB::getConf('token_order'),
            'test_mode' => (bool)ConfSB::getConf('test_mode'),
            'order' => array(
                'delivery_method_code' => $delivery_info->delivery_method_id,
                'id' => (string)$order->id,
                //'delivery_price' => (!$cash_on_delivery ? $delivery_info->delivery_price : 0),
                //'delivery_price' => $delivery_info->delivery_price,
                'delivery_price' =>  ceil(($GLOBALS['COD']==1) ? $delivery_info->delivery_price : 0),
                'delivery_from_shop' => false
            ),
            // 'products' => SalesBeat::formatSalesBeatProducts($cart),
            'products' => $products_paimon,
            'recipient' => array(
                'city_id' => $delivery_info->city_code,
                'full_name' => $customer->firstname.' '.$customer->lastname,
                'email' => $customer->email,
                'phone' => (
                $address->phone ?
                    $address->phone :
                    (
                    $address->phone_mobile ?
                        $address->phone_mobile :
                        '79000000001'
                    )
                )
            )
        );

        if ($delivery_info->pvz_id) {
            $data_order['recipient']['pvz'] = array(
                'id' => $delivery_info->pvz_id
            );
        } else {
            $data_order['recipient']['courier'] = array(
                'street' => $delivery_info->street,
                'house' => $delivery_info->house,
                'flat' => $delivery_info->flat,
                'date' => date('Y-m-d')
            );
        }


        $this->request = $data_order;
        return $this;
    }
}