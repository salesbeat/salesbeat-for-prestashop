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

require_once(dirname(__FILE__).'/../../classes/tools/config.php');
class AdminSalesBeatSettingsController extends ModuleAdminControllerSB
{
    public function __construct()
    {
        $this->context = Context::getContext();
        $this->table = 'configuration';
        $this->identifier = 'id_configuration';
        $this->className = 'Configuration';
        $this->bootstrap = true;
        $this->display = 'edit';
        parent::__construct();

        $this->fields_options = array(
            'settings' => array(
                'title' =>    $this->l('Salesbeat settings'),
                'fields' =>    array(
                    ConfSB::formatConfName('token') => array(
                        'title' => $this->l('Token'),
                        'validation' => 'isString',
                        'type' => 'text'
                    ),
                    ConfSB::formatConfName('token_order') => array(
                        'title' => $this->l('Token for export orders'),
                        'validation' => 'isString',
                        'type' => 'text'
                    ),
                    ConfSB::formatConfName('width') => array(
                        'title' => $this->l('Width(on default)'),
                        'validation' => 'isFloat',
                        'cast' => 'floatval',
                        'type' => 'text'
                    ),
                    ConfSB::formatConfName('height') => array(
                        'title' => $this->l('Height(on default)'),
                        'validation' => 'isFloat',
                        'cast' => 'floatval',
                        'type' => 'text'
                    ),
                    ConfSB::formatConfName('length') => array(
                        'title' => $this->l('Length(on default)'),
                        'validation' => 'isFloat',
                        'cast' => 'floatval',
                        'type' => 'text'
                    ),
                    ConfSB::formatConfName('weight') => array(
                        'title' => $this->l('Weight(on default)'),
                        'validation' => 'isFloat',
                        'cast' => 'floatval',
                        'type' => 'text'
                    ),
                    ConfSB::formatConfName('weight_unit') => array(
                        'title' => $this->l('Weight(unit)'),
                        'type' => 'select',
                        'list' => array(
                            array('id' => 'kg', 'name' => $this->l('kg')),
                            array('id' => 'g', 'name' => $this->l('g'))
                        ),
                        'identifier' => 'id'
                    ),
                    ConfSB::formatConfName('width_unit') => array(
                        'title' => $this->l('Width(unit)'),
                        'type' => 'select',
                        'list' => array(
                            array('id' => 'sm', 'name' => $this->l('sm')),
                            array('id' => 'm', 'name' => $this->l('m'))
                        ),
                        'identifier' => 'id'
                    ),
                    ConfSB::formatConfName('percent_insurance') => array(
                        'title' => $this->l('Insurance(%)'),
                        'validation' => 'isFloat',
                        'cast' => 'floatval',
                        'type' => 'text'
                    ),
                    ConfSB::formatConfName('cash_on_delivery') => array(
                        'title' => $this->l('Cash on delivery'),
                        'validation' => 'isBool',
                        'type' => 'bool'
                    ),
                    ConfSB::formatConfName('test_mode') => array(
                        'title' => $this->l('Test mode'),
                        'validation' => 'isBool',
                        'type' => 'bool'
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );
    }
}