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

class BaseSBMethod
{
    protected $required_params = array();

    protected function __construct()
    {
    }

    public static function create()
    {
        return new static();
    }

    public function getHeaders()
    {
        return array(
            'Content-Type: application/json',
            'Accept: application/json'
        );
    }

    protected $request = array();

    public function getRequest()
    {
        foreach ($this->required_params as $param) {
            if (!array_key_exists($param, $this->request)) {
                throw new PrestaShopException('Set please param: '.$param.' in class: '.get_called_class());
            }
        }
        return $this->request;
    }

    public function getMethodName()
    {
        $class_name = get_called_class();
        $method_name = str_replace('SBMethod', '', $class_name);
        $method_name = str_replace(
            '_',
            '-',
            Tools::toUnderscoreCase($method_name)
        );

        $reflector = new ReflectionClass(get_called_class());
        $fn = $reflector->getFileName();
        $dir_name = Tools::strtolower(basename(dirname($fn)));

        return ($dir_name != 'method' ? $dir_name.'/' : '').$method_name.'/';
    }

    public function getMethod()
    {
        return 'POST';
    }
}
