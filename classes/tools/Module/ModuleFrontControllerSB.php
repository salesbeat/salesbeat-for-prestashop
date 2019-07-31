<?php
/**
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2012-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class ModuleFrontControllerSB extends ModuleFrontController
{
    public function setTemplate($template, $params = array(), $locale = null)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            SmartySB::registerSmartyFunctions();
            $this->context->smarty->assign(array(
                'template_path' => $this->getTemplatePath('front/'.$template),
                'navigationPipe' => '>',
                'use_taxes' => (int)Configuration::get('PS_TAX')
            ));
            $template = 'module:'.$this->module->name.'/views/templates/front/base_17.tpl';
        }
        $this->context->smarty->assign(
            array(
                'is_17' => version_compare(_PS_VERSION_, '1.7.0.0', '>=')
            )
        );
        parent::setTemplate($template, $params, $locale);
    }
}
