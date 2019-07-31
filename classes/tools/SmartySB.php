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

class SmartySB
{
    protected static $cache_smarty_plugins = array();

    /**
     * @param $type
     * @param $function
     * @param $params
     * @return bool
     */
    protected static function registerFunction($type, $function, $params)
    {
        $cache_key = $type.'-'.$function;
        if (in_array($cache_key, self::$cache_smarty_plugins)) {
            return false;
        }
        $smarty = Context::getContext()->smarty;

        if (array_key_exists($function, $smarty->registered_plugins[$type])) {
            $smarty->unregisterPlugin($type, $function);
        }
        $smarty->registerPlugin($type, $function, $params);
        self::$cache_smarty_plugins[] = $cache_key;
        return true;
    }

    /**
     * @void
     */
    public static function registerSmartyFunctions()
    {
        $smarty = Context::getContext()->smarty;
        self::registerFunction(
            'modifier',
            'no_escape',
            array(__CLASS__, 'noEscape')
        );
        self::registerFunction(
            'function',
            'get_image_langSB',
            array(__CLASS__, 'getImageLang')
        );
        self::registerFunction(
            'function',
            'renderTemplateSB',
            array(__CLASS__, 'renderTemplate')
        );
        self::registerFunction(
            'function',
            'categoryImage',
            array(__CLASS__, 'categoryImage')
        );
        self::registerFunction(
            'function',
            'versionCompare',
            array(__CLASS__, 'versionCompare')
        );
        self::registerFunction(
            'modifier',
            'versionCompare',
            array(__CLASS__, 'versionCompareModifier')
        );
        self::registerFunction(
            'modifier',
            'pluralForm',
            array(__CLASS__, 'pluralForm')
        );
        if (class_exists('TransModSB')) {
            self::registerFunction(
                'modifier',
                'ldSB',
                array(TransModSB::getInstance(), 'ld')
            );
        }
        self::registerFunction(
            'modifier',
            'dt',
            array(__CLASS__, 'smartyDateFormatTranslate')
        );
        if (!array_key_exists('displayPrice', $smarty->registered_plugins['function'])) {
            smartyRegisterFunction(
                $smarty,
                'function',
                'displayPrice',
                array('Tools', 'displayPriceSmarty')
            );
        }
    }

    public static function noEscape($value)
    {
        return $value;
    }

    public static function pluralForm($params, &$smarty)
    {
        $n = $params['n'];
        $form1 = $params['form1'];
        $form2 = $params['form2'];
        $form5 = $params['form5'];

        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) {
            return $form5;
        }
        if ($n1 > 1 && $n1 < 5) {
            return $form2;
        }
        if ($n1 == 1) {
            return $form1;
        }
        unset($smarty);
        return $form5;
    }

    public static function getImageLang($smarty)
    {
        $path = $smarty['path'];
        $module_path = ToolsModuleSB::getModNameForPath(__FILE__).'/views/img/';
        $module_lang_path = $module_path.Context::getContext()->language->iso_code.'/';
        $module_lang_default_path = $module_path.'en/';
        $path_image = false;
        if (file_exists(_PS_MODULE_DIR_.$module_lang_path.$path)) {
            $path_image = _MODULE_DIR_.$module_lang_path.$path;
        } elseif (file_exists(_PS_MODULE_DIR_.$module_lang_default_path.$path)) {
            $path_image = _MODULE_DIR_.$module_lang_default_path.$path;
        }

        $attrs = '';
        if (isset($smarty['attrs'])) {
            foreach ($smarty['attrs'] as $name => $attr) {
                $attrs .= ' '.$name.'="'.$attr.'"';
            }
        }

        if ($path_image) {
            return '<img src="'.$path_image.'" '.$attrs.'>';
        } else {
            return '[can not load image "'.$path.'"]';
        }
    }

    public static function renderTemplate($smarty)
    {
        $file = $smarty['file'];
        return ToolsModuleSB::fetchTemplate($file);
    }

    public static function categoryImage($smarty)
    {
        $id_category = $smarty['id'];
        return (Tools::file_exists_cache(_PS_CAT_IMG_DIR_.(int)$id_category.'.jpg')
            || Tools::file_exists_cache(_PS_CAT_IMG_DIR_.(int)$id_category.'_thumb.jpg'))
            ? (int)$id_category : Language::getIsoById(Context::getContext()->language->id).'-default';
    }

    public static function versionCompare($smarty)
    {
        $version = $smarty['v'];
        $operand = $smarty['op'];
        return version_compare(_PS_VERSION_, $version, $operand);
    }

    public static function versionCompareModifier($value, $version, $operand)
    {
        return (version_compare(_PS_VERSION_, $version, $operand) ? $value : '');
    }

    public static function smartyDateFormatTranslate($date, $format = null)
    {
        return ToolsModuleSB::dateFormatTranslate($date, $format);
    }
}
