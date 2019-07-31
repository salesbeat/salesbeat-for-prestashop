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

class SBCarrier extends ObjectModel
{
    const TYPE_DELIVERY_POINT = '1';

    /**
     * @var int
     */
    public $id_reference;

    /**
     * @var string
     */
    public $unique_name;

    public static $definition = array(
        'table' => 'sb_carrier',
        'primary' => 'id_sb_carrier',
        'fields' => array(
            'id_reference' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt'
            ),
            'unique_name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            )
        )
    );

    public static function getReferenceByUniqueName($unique_name)
    {
        return (int)Db::getInstance()->getValue(
            'SELECT `id_reference` FROM '._DB_PREFIX_.self::$definition['table'].'
            WHERE `unique_name` = "'.pSQL($unique_name).'"'
        );
    }

    public static function getCarrierByUniqueName($unique_name)
    {
        $id_reference = (int)Db::getInstance()->getValue(
            'SELECT `id_reference` FROM '._DB_PREFIX_.self::$definition['table'].'
            WHERE `unique_name` = "'.pSQL($unique_name).'"'
        );
        return Carrier::getCarrierByReference($id_reference);
    }

    public static function getUniqueNameByIdCarrier($id_carrier)
    {
        $unique_name = Db::getInstance()->getValue(
            'SELECT bc.`unique_name` FROM '._DB_PREFIX_.'carrier c
            LEFT JOIN '._DB_PREFIX_.self::$definition['table'].' bc ON bc.`id_reference` = c.`id_reference`
            WHERE c.`id_carrier` = '.(int)$id_carrier
        );

        return ($unique_name ? $unique_name : false);
    }

    public static function getIdsCarriers()
    {
        $result = Db::getInstance()->executeS(
            'SELECT bc.`id_reference`, c.`id_carrier` FROM '._DB_PREFIX_.self::$definition['table'].' bc
            LEFT JOIN '._DB_PREFIX_.'carrier c ON  c.`id_reference` = bc.`id_reference` AND deleted = 0
            ORDER BY c.`id_carrier` DESC'
        );

        $ids = array();

        if (is_array($result) && count($result)) {
            foreach ($result as $item) {
                if ($item['id_carrier']) {
                    $ids[] = (int)$item['id_carrier'];
                }
            }
        }
        return $ids;
    }
}
