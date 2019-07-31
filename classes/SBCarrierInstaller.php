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
 * @author    Goryachev Dmitry    <dariusakafest@gmail.com>
 * @copyright 2007-2018 Goryachev Dmitry
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class SBCarrierInstaller
{
    /**
     * @param string $name
     * @param array $weight_range
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public static function installCarrier($module_name, $name, $weight_range)
    {
        $carrier = new Carrier();
        $carrier->range_behavior = 1;
        $carrier->name = $name;
        $carrier->active = true;
        $carrier->deleted = 0;
        $carrier->shipping_handling = false;
        $delay_str_list = array(
            'ru'=>' ',
            'default'=>'Delivery time depens on distance'
        );
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            if (!isset($delay_str_list[$language['iso_code']])) {
                $carrier->delay[(int)$language['id_lang']] = $delay_str_list['default'];
            } else {
                $carrier->delay[(int)$language['id_lang']] = $delay_str_list[$language['iso_code']];
            }
        }
        $carrier->shipping_external = true;
        $carrier->is_module = true;
        $carrier->external_module_name = $module_name;
        $carrier->need_range = true;
        if ($carrier->add()) {
            $groups = Group::getGroups(true);
            foreach ($groups as $group) {
                Db::getInstance()->insert(
                    'carrier_group',
                    array(
                        'id_carrier' => (int)$carrier->id,
                        'id_group' => (int)$group['id_group']
                    )
                );
            }
            $range_price = new RangePrice();
            $range_price->id_carrier = $carrier->id;
            $range_price->delimiter1 = '0';
            $range_price->delimiter2 = '100500';
            $range_price->add();

            $range_weight = new RangeWeight();
            $range_weight->id_carrier = $carrier->id;
            $range_weight->delimiter1 = $weight_range['min'];
            $range_weight->delimiter2 = $weight_range['max'];
            $range_weight->add();

            $zones = Zone::getZones(true);
            foreach ($zones as $z) {
                Db::getInstance()->insert(
                    'carrier_zone',
                    array(
                        'id_carrier' => (int)$carrier->id,
                        'id_zone' => (int)$z['id_zone']
                    )
                );
                Db::getInstance()->insert(
                    'delivery',
                    array(
                        'id_carrier' => (int)$carrier->id,
                        'id_range_price' => (int)$range_price->id,
                        'id_range_weight' => null,
                        'id_zone' => (int)$z['id_zone'],
                        'price' => '0'
                    ),
                    true,
                    0
                );
                Db::getInstance()->insert(
                    'delivery',
                    array(
                        'id_carrier' => (int)$carrier->id,
                        'id_range_price' => null,
                        'id_range_weight' => (int)$range_weight->id,
                        'id_zone' => (int)$z['id_zone'],
                        'price' => '0'
                    ),
                    true,
                    0
                );
            }
            $path_img = _PS_MODULE_DIR_.ToolsModuleSB::getModNameForPath(__FILE__).'/views/img/carrier.jpg';
            if (file_exists($path_img)) {
                copy(
                    $path_img,
                    _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg'
                );
            }
            return $carrier->id;
        }

        return false;
    }

    public static function uninstallCarrier($id_reference)
    {
        $carrier = Carrier::getCarrierByReference($id_reference);
        if (Validate::isLoadedObject($carrier)) {
            $lang_default = (int)Configuration::get('PS_LANG_DEFAULT');
            $carriers = Carrier::getCarriers(
                $lang_default,
                true,
                false,
                false,
                null,
                PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
            );
            if (Configuration::get('PS_CARRIER_DEFAULT') == $carrier->id) {
                foreach ($carriers as $c) {
                    if ($c['active'] && !$c['deleted'] && ($c['name'] != $carrier->name)) {
                        Configuration::updateValue('PS_CARRIER_DEFAULT', $c['id_carrier']);
                    }
                }
            }

            if (!$carrier->deleted) {
                $carrier->deleted = 1;
                if (!$carrier->update()) {
                    return false;
                }
            }
        }
        return true;
    }
}
