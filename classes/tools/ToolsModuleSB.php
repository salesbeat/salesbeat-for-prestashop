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
 * @copyright 2012-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * Class ToolsModuleSB
 */
class ToolsModuleSB
{
    public static $languages = array();
    public static function getLanguages($active = true)
    {
        $cache_id = md5($active);
        if (array_key_exists($cache_id, self::$languages)) {
            return self::$languages[$cache_id];
        }
        $languages = Language::getLanguages($active);
        foreach ($languages as &$l) {
            $l['is_default'] = (Configuration::get('PS_LANG_DEFAULT') == $l['id_lang']);
        }
        self::$languages[$cache_id] = $languages;
        return $languages;
    }

    /**
     * @param $module_name string
     * @param $class_name string
     * @param $parent string
     * @param $name mixed
     * @param $hide
     * @return void
     */
    public static function createTab($module_name, $class_name, $parent = null, $name = 'Tab', $hide = false)
    {
        if (!is_array($name)) {
            $name = array('en' => $name);
        } elseif (is_array($name) && !count($name)) {
            $name = array('en' => $class_name);
        } elseif (is_array($name) && count($name) && !isset($name['en'])) {
            $name['en'] = current($name);
        }

        $tab = new Tab();
        $tab->class_name = $class_name;
        $tab->module = $module_name;
        $tab->id_parent = (!is_null($parent) ? Tab::getIdFromClassName($parent) : 0);
        if (is_null($parent)) {
            self::copyTabIconInRoot($class_name);
        }

        if ($hide) {
            $tab->hide_host_mode = true;
            $tab->active = 0;
        } else {
            $tab->active = 1;
        }

        foreach (self::getLanguages() as $l) {
            $tab->name[$l['id_lang']] = (isset($name[$l['iso_code']]) ? $name[$l['iso_code']] : $name['en']);
        }
        $tab->save();
    }

    public static function copyTabIconInRoot($icon)
    {
        $icon = $icon.'.gif';
        $path = _PS_MODULE_DIR_.basename(dirname(__FILE__)).'/';
        if (!file_exists($path.$icon) && file_exists($path.'views/img/'.$icon)
            && _PS_VERSION_ < 1.6) {
            copy($path.'views/img/'.$icon, $path.$icon);
        }
    }

    /**
     * @param $class_name string
     * @return void
     */
    public static function deleteTab($class_name)
    {
        $tab = Tab::getInstanceFromClassName($class_name);
        if (!Validate::isLoadedObject($tab)) {
            return null;
        }
        $tab->delete();
        self::deleteTab($class_name);
    }

    public static $module_name = null;
    public static function getModNameForPath($path)
    {
        if (!is_null(self::$module_name)) {
            return self::$module_name;
        }
        $path = str_replace(_PS_ROOT_DIR_, '', $path);
        $map_dir = explode(DIRECTORY_SEPARATOR, $path);
        $key_module = array_search('modules', $map_dir);
        self::$module_name = $map_dir[$key_module + 1];
        return self::$module_name;
    }

    public static function getTemplateDir($path)
    {
        if (Tools::file_exists_cache(
            _PS_THEME_DIR_.'modules/'
            .self::getModNameForPath(__FILE__).'/views/templates/'.$path
        )) {
            return _PS_THEME_DIR_.'modules/'.self::getModNameForPath(__FILE__).'/views/templates/'.$path;
        } else {
            return _PS_MODULE_DIR_.self::getModNameForPath(__FILE__).'/views/templates/'.$path;
        }
    }

    /**
     * @param $path
     * @param $variables
     *
     * @return string
     */
    public static function fetchTemplate($path, $variables = array())
    {
        Context::getContext()->smarty->assign($variables);
        return Context::getContext()->smarty->fetch(self::getTemplateDir($path));
    }

    public static function globalAssignVar()
    {
        Context::getContext()->smarty->assign(array(
            'is_15_ps' => self::is15ps()
        ));
    }

    /**
     * @param string $pattern
     * @param int $flags
     * @return array
     */
    public static function globRecursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        if (!$files) {
            $files = array();
        }

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $files = array_merge($files, self::globRecursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }

    public static function is15ps()
    {
        return self::isLower('1.6') && !self::isLower('1.5');
    }

    /**
     * @param string $version
     * @return bool
     */
    public static function isGreater($version)
    {
        return version_compare(_PS_VERSION_, $version, '>');
    }

    /**
     * @param string $version
     * @return bool
     */
    public static function isLower($version)
    {
        return version_compare(_PS_VERSION_, $version, '<');
    }

    public static function autoloadCSS($uri_path)
    {
        $full_path = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR
            .self::strReplaceFirst(__PS_BASE_URI__, '', $uri_path);
        $context = Context::getContext();
        $files = self::globRecursive($full_path.'*.css');

        if (is_array($files) && count($files)) {
            foreach ($files as $file) {
                $file_path = str_replace($full_path, '', $file);
                $context->controller->addCSS($uri_path.$file_path);
            }
        }
    }

    public static function autoloadJS($uri_path)
    {
        $full_path = _PS_ROOT_DIR_.DIRECTORY_SEPARATOR
            .self::strReplaceFirst(__PS_BASE_URI__, '', $uri_path);
        $context = Context::getContext();
        $files = self::globRecursive($full_path.'*.js');

        if (is_array($files) && count($files)) {
            foreach ($files as $file) {
                $file_path = str_replace($full_path, '', $file);
                $context->controller->addJS($uri_path.$file_path);
            }
        }
    }

    public static function convertJSONRequestToPost()
    {
        $post = &$_POST;
        $params = Tools::jsonDecode(Tools::file_get_contents('php://input'), true);
        if (is_array($params) && count($params)) {
            foreach ($params as $key => $value) {
                $post[$key] = $value;
            }
        }
    }

    public static function strReplaceFirst($search, $replace, $subject)
    {
        $pos = call_user_func('strpos', $subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, Tools::strlen($search));
        }
        return $subject;
    }

    public static function buildSQLSearchWhereFromQuery($query, $detailed_search, $field)
    {
        if (!$query || !$field) {
            return '1';
        }

        if ((int)$detailed_search) {
            return $field.' LIKE "%'.pSQL($query).'%"';
        } else {
            $sql_where = array();
            $words = explode(' ', $query);
            foreach ($words as $word) {
                $sql_where[] = $field.' LIKE "%'.pSQL($word).'%"';
            }
            return implode(' AND ', $sql_where);
        }
    }

    public static function getIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public static function checkImage($tmp_name, $type = array(IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG))
    {
        return in_array(exif_imagetype($tmp_name), $type);
    }

    public static function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function fileForceContents($dir, $contents)
    {
        if (!file_exists(dirname($dir))) {
            mkdir(dirname($dir), 0777, true);
        }
        if (file_exists($dir)) {
            unlink($dir);
        }
        file_put_contents($dir, $contents);
    }

    public static function checkItemArray($item_name, $array)
    {
        return (is_array($array) && count($array) && array_key_exists($item_name, $array));
    }

    public static function getCookieKey($name, $default_value = '')
    {
        if (isset(Context::getContext()->cookie->{$name})) {
            return Context::getContext()->cookie->{$name};
        }
        return $default_value;
    }

    public static function setCookieKey($name, $value)
    {
        Context::getContext()->cookie->{$name} = $value;
    }

    /**
     * @param $string
     * @return array
     */
    public static function stringToCss($string)
    {
        $css_features = array();

        if (!$string) {
            return $css_features;
        }
        $features = explode(';', $string);

        if (is_array($features) && count($features)) {
            foreach ($features as $feature) {
                list($property, $value) = explode(':', $feature);
                $css_features[trim($property)] = trim($value);
            }
        }

        return $css_features;
    }

    /**
     * @return array
     */
    public static function getPaymentMethods()
    {
        if (!function_exists('pq')) {
            return array();
        }
        if (!class_exists('phpQuery')) {
            return array();
        }

        $t = TransModSB::getInstance();
        $default_payment_icon = _MODULE_DIR_.ToolsModuleSB::getModNameForPath(__FILE__)
            .'/views/img/default_payment_icon.png';
        $payment_methods_hook = Hook::exec('displayPayment');
        $q_payments = phpQuery::newDocument($payment_methods_hook);
        $payments = array();
        if ($q_payments->find('.payment_module')->size()) {
            foreach ($q_payments->find('.payment_module') as $q_payment) {
                $payment = array();

                if (pq($q_payment)->find('img')->size()) {
                    $payment['img'] = pq($q_payment)->find('img')->attr('src');
                } elseif (pq($q_payment)->find('a')->size()) {
                    if (pq($q_payment)->find('a')->attr('style')) {
                        $css = self::stringToCss(pq($q_payment)->find('a')->attr('style'));
                        if (array_key_exists('background-image', $css)) {
                            if (strpos($css['background-image'], 'url(') !== false) {
                                $img = preg_replace('/^url\((\'|\")/', '', $css['background-image']);
                                $img = preg_replace('/(\'|\")\)$/', '', $img);
                                $payment['img'] = $img;
                            }
                        } elseif (array_key_exists('background', $css)) {
                            if (strpos($css['background'], 'url(') !== false) {
                                $img = $default_payment_icon;
                                preg_match('/url\((\'|\")(.+)(\'|\")\)/', $css['background'], $matches);
                                if (array_key_exists(2, $matches)) {
                                    $img = $matches[2];
                                }
                                $payment['img'] = $img;
                            }
                        } else {
                            $payment['img'] = $default_payment_icon;
                        }
                    } else {
                        $payment['img'] = $default_payment_icon;
                    }
                } else {
                    $payment['img'] = $default_payment_icon;
                }

                if (pq($q_payment)->find('a')->size()) {
                    $payment['name'] = trim(pq($q_payment)->find('a')->text());
                } else {
                    $payment['name'] = $t->l('Unknown payment method', __FILE__);
                }

                $payment['html'] = pq($q_payment)->htmlOuter();
                $payments[] = $payment;
            }
        }
        return $payments;
    }

    public static function simpleArrayToInt(&$var)
    {
        if (!is_array($var)) {
            return false;
        }
        foreach ($var as &$item) {
            $item = (int)$item;
        }
    }

    public static function isSerialized($value, &$result = null)
    {
        if (!is_string($value)) {
            return false;
        }

        if ($value === 'b:0;') {
            $result = false;
            return true;
        }

        $length = Tools::strlen($value);
        $end = '';
        switch ($value[0]) {
            case 's':
                if ($value[$length - 2] !== '"') {
                    return false;
                }
                //no break
            case 'b':
            case 'i':
            case 'd':
                $end .= ';';
                //no break
            case 'a':
            case 'O':
                $end .= '}';
                if ($value[1] !== ':') {
                    return false;
                }

                switch ($value[2]) {
                    case 0:
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                    case 9:
                        break;
                    default:
                        return false;
                }
                //no break
            case 'N':
                $end .= ';';
                if ($value[$length - 1] !== $end[0]) {
                    return false;
                }

                break;
            default:
                return false;
        }

        if (($result = @unserialize($value)) === false) {
            $result = null;
            return false;
        }

        return true;
    }

    public static function formatValue($value, $type)
    {
        switch ($type) {
            case ObjectModel::TYPE_INT:
                $value = (int)$value;
                break;
            case ObjectModel::TYPE_STRING:
                $value = (string)$value;
                break;
            case ObjectModel::TYPE_FLOAT:
                $value = (float)$value;
                break;
        }

        return $value;
    }

    public static function dateFormatTranslate($date, $format = null)
    {
        if (is_null($format)) {
            $format = 'H:i:s d-m-Y';
        }
        $l = TransModSB::getInstance();
        $months = explode(
            '|',
            'January|February|March|April|May|June|July|August|September|October|November|December'
        );
        $mons = explode('|', 'Jan|Feb|Mar|Apr|May|June|July|Aug|Sept|Oct|Nov|Dec');
        $weekdays = explode('|', 'Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday');
        $weeks = explode('|', 'Mon|Tue|Wed|Thu|Fri|Sat|Sun');
        $date_data = array_merge($months, $mons, $weekdays, $weeks);

        $date = date($format, strtotime($date));
        $date = str_replace($date_data, array_map(array($l, 'ld'), $date_data), $date);
        return $date;
    }

    public static function arrayInsert(&$array, $position, $insert)
    {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos   = array_search($position, array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos),
                $insert,
                array_slice($array, $pos)
            );
        }
    }

    public function properParseStr(
        $query_string,
        $arg_separator = '&',
        $dec_type = PHP_QUERY_RFC1738
    ) {
        $result = array();
        $parts = explode($arg_separator, $query_string);

        foreach ($parts as $part) {
            list($param_name, $param_value) = explode('=', $part, 2);

            switch ($dec_type) {
                case PHP_QUERY_RFC3986:
                    $param_name = rawurldecode($param_name);
                    $param_value = rawurldecode($param_value);
                    break;

                case PHP_QUERY_RFC1738:
                default:
                    $param_name = urldecode($param_name);
                    $param_value = urldecode($param_value);
                    break;
            }


            if (preg_match_all('/\[([^\]]*)\]/m', $param_name, $matches)) {
                $param_name = Tools::substr($param_name, 0, Tools::strpos($param_name, '['));
                $keys = array_merge(array($param_name), $matches[1]);
            } else {
                $keys = array($param_name);
            }

            $target = &$result;

            foreach ($keys as $index) {
                if ($index === '') {
                    if (isset($target)) {
                        if (is_array($target)) {
                            $int_keys = array_filter(array_keys($target), 'is_int');
                            $index = count($int_keys) ? max($int_keys)+1 : 0;
                        } else {
                            $target = array($target);
                            $index  = 1;
                        }
                    } else {
                        $target = array();
                        $index = 0;
                    }
                } elseif (isset($target[$index]) && !is_array($target[$index])) {
                    $target[$index] = array($target[$index]);
                }

                $target = &$target[$index];
            }

            if (is_array($target)) {
                $target[] = $param_value;
            } else {
                $target = $param_value;
            }
        }

        return $result;
    }

    protected static function clearCacheCart()
    {
        Cache::clean('getPackageShippingCost_*');
        Cache::clean('Cart::getDiscountsCustomer_*');
        Cache::clean('getContextualValue_*');
        Cache::clean('Cart::getCartRules_*');
        Cache::clean('Carrier::getMaxDeliveryPriceByPrice_*');
    }

    public static function getTotalOrderInRub($cart_option = null)
    {
        $context = Context::getContext();
        $cart = $context->cart;
        if (!is_null($cart_option)) {
            $cart = $cart_option;
        }

        self::clearCacheCart();
        $id_currency = Currency::getIdByIsoCode('RUB');
        if ($context->currency->iso_code != 'RUB'
            && $id_currency == Configuration::get('PS_CURRENCY_DEFAULT')) {
            $old_currency = $context->currency;
            $context->currency = new Currency($id_currency);
            $context->cookie->id_currency = $id_currency;
            $context->cookie->write();
            $cart->id_currency = $id_currency;
            $cart->getDeliveryOptionList(null, true);

            $total_order = $cart->getOrderTotal(
                Configuration::get('PS_TAX'),
                Cart::BOTH,
                null,
                $cart->id_carrier,
                false
            );

            $context->currency = $old_currency;
            $context->cookie->id_currency = $old_currency->id;
            $context->cookie->write();
            $cart->id_currency = $old_currency->id;
            self::clearCacheCart();
            $cart->getDeliveryOptionList(null, true);
        } elseif ($context->currency->iso_code != 'RUB'
            && $id_currency != Configuration::get('PS_CURRENCY_DEFAULT')) {
            $total_order = Tools::convertPrice(
                $cart->getOrderTotal(
                    Configuration::get('PS_TAX'),
                    Cart::BOTH,
                    null,
                    $cart->id_carrier,
                    false
                ),
                $id_currency
            );
        } else {
            $total_order = $cart->getOrderTotal(
                Configuration::get('PS_TAX'),
                Cart::BOTH,
                null,
                $cart->id_carrier,
                false
            );
        }
        return $total_order;
    }

    public static function decodeCode($code)
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $code);
    }

    public static function checkRussianMobilePhone($phone)
    {
        return preg_match('/^((\+7|8)+([0-9]){10})$/', $phone);
    }

    public static function convertPriceToRub($amount, $id_current_currency)
    {
        $def_currency = Currency::getDefaultCurrency();
        if ($def_currency->iso_code == 'RUB') {
            Tools::convertPrice($amount, $id_current_currency, false);
        }

        if ($def_currency->iso_code != 'RUB') {
            $amount = Tools::convertPrice($amount, $id_current_currency, false);
            $id_currency = Currency::getIdByIsoCode('RUB');
            if ($id_currency) {
                $amount = Tools::convertPrice($amount, $id_currency);
            }
        }
        return $amount;
    }
}
