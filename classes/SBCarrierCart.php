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

class SBCarrierCart extends ObjectModel
{
    /**
     * @var int
     */
    public $id_carrier;

    /**
     * @var int
     */
    public $id_cart;

    /**
     * @var string
     */
    public $city_code;

    /**
     * @var string
     */
    public $city_name;

    /**
     * @var string
     */
    public $comment;

    /**
     * @var string
     */
    public $delivery_days;

    /**
     * @var string
     */
    public $delivery_method_id;

    /**
     * @var string
     */
    public $delivery_method_name;

    /**
     * @var string
     */
    public $delivery_price;

    /**
     * @var string
     */
    public $pvz_address;

    /**
     * @var string
     */
    public $pvz_id;
    /**
     * @var string
     */
    public $region_name;
    /**
     * @var string
     */
    public $short_name;

    /**
     * @var string
     */
    public $flat;
    /**
     * @var string
     */
    public $street;
    /**
     * @var string
     */
    public $house;
    /**
     * @var string
     */
    public $house_block;
    /**
     * @var string
     */
    public $index;

    /**
     * @var string
     */
    public $track_code;

    /**
     * @var string
     */
    public $order_id;
    /**
     * @var string
     */
    public $salesbeat_order_id;

    public static $definition = array(
        'table' => 'sb_carrier_cart',
        'primary' => 'id_sb_carrier_cart',
        'fields' => array(
            'id_carrier' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isInt'
            ),
            'id_cart' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isInt'
            ),
            'city_code' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'city_name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'comment' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'delivery_days' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'delivery_method_id' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'delivery_method_name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'delivery_price' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'pvz_address' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'pvz_id' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'region_name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'short_name' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'street' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'flat' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'house' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'house_block' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'index' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'track_code' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ),
            'order_id' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            ) ,
            'salesbeat_order_id' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString'
            )
        )
    );

    /**
     * @param $id_carrier
     * @param $id_cart
     * @return SBCarrierCart
     */
    public static function getInstanceByCarrierAndCart($id_carrier, $id_cart)
    {
        $id = (int)Db::getInstance()->getValue(
            'SELECT `id_sb_carrier_cart` FROM '._DB_PREFIX_.'sb_carrier_cart
            WHERE `id_carrier` = '.(int)$id_carrier.' AND `id_cart` = '.(int)$id_cart
        );
        $object = new self($id);
        $object->id_cart = $id_cart;
        $object->id_carrier = $id_carrier;
        return $object;
    }

    public function getDelivery()
    {
        $data = array();

        foreach (array(
            'city_code',
            'city_name',
            'delivery_days',
            'delivery_method_id',
            'delivery_method_name',
            'delivery_price',
            'region_name',
            'short_name',
            'index'
         ) as $property) {
            $data[$property] = $this->{$property};
        }

        if ($this->pvz_id) {
            foreach (array(
                         'pvz_address',
                         'pvz_id',
                     ) as $property) {
                $data[$property] = $this->{$property};
            }
            $this->comment = '';
            $this->flat = '';
            $this->house = '';
            $this->street = '';
        } else {
            foreach (array(
                         'comment',
                         'house_block',
                         'flat',
                         'house',
                         'street',
                     ) as $property) {
                $data[$property] = $this->{$property};
            }
            $this->pvz_address = '';
            $this->pvz_id = '';
        }

        return $data;
    }
}
