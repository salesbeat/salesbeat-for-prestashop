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

class SalesBeatApi
{
    const API_URL = 'https://app.salesbeat.pro/delivery_order';

    protected static $responses = array();

    public static function call(BaseSBMethod $method, $display_errors = true)
    {
        $request = $method->getRequest();
        $t = TransModSB::getInstance();

        $url = self::API_URL.'/'.$method->getMethodName();
        $hash = sha1($url.Tools::jsonEncode($request).$method->getMethod());
        if (!isset(self::$responses[$hash])) {
            $result = self::sendRequest($url, $request, $method->getHeaders(), $method->getMethod());
            self::$responses[$hash] = $result;
        } else {
            $result = self::$responses[$hash];
        }
        $response = Tools::jsonDecode($result, true);

        if (is_array($response)) {
            if ((isset($response['success']))
                && !$response['success']
                && $display_errors) {
                $errors = array($response['error_code'].':'.$response['error_message']);
                if (isset($response['error_list'])) {
                    foreach ($response['error_list'] as $e) {
                        $errors[] = $t->l('Field', __FILE__).': ' . $e['field'].', '
                            .$t->l('message', __FILE__) . ': '.$e['message'];
                    }
                }

                foreach ($errors as $error) {
                    $message = $error.PHP_EOL.'Method: '.$method->getMethodName().PHP_EOL;
                    if (_PS_MODE_DEV_ && $display_errors) {
                        Context::getContext()->controller->errors[] = $message;
                        //throw new PrestaShopException($message);
                    }
                }
                return array();
            }
        } else {
            if ($display_errors && $display_errors) {
                $message = 'Invalid response'.PHP_EOL.'Method: '.$method->getMethodName().PHP_EOL;
                if (_PS_MODE_DEV_) {
                    Context::getContext()->controller->errors[] = $message;
                    //throw new PrestaShopException($message);
                }
                return array();
            }
        }
        return $response;
    }

    protected static function sendRequest($url, $request, $headers, $method = 'POST')
    {
        $params = ($method == 'GET' ? '?' . http_build_query($request) : '');
        if (isset($request['query'])) {
            $params = '/'.$request['query'];
            unset($request['query']);
            if (count($request)) {
                $params .= ($method == 'GET' ? '?' . http_build_query($request) : '');
            }
        }
               
                $p_log_filename = dirname(__FILE__).'/p_sb_log.txt';

                $p_log = "Запрос на сервер: ".$url.$params." request: ".Tools::jsonEncode($request)." | ";
                //записываем текст в файл
                 file_put_contents($p_log_filename, $p_log, FILE_APPEND);


        $ch = curl_init(
            $url.
            $params
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, Tools::jsonEncode($request));
        }

        if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, Tools::jsonEncode($request));
        }

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $headers
        );

        $result = curl_exec($ch);

        $p_log = "Ответ с сервера: ".$result." | ";
        //записываем текст в файл
        file_put_contents($p_log_filename, $p_log, FILE_APPEND);
        
        curl_close($ch);
        return $result;
    }
}
