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

class ErrorHandlerSB
{

    public static function setErrorHandler()
    {
        if (!_PS_MODE_DEV_) {
            ini_set('display_errors', 'off');
        }
        restore_error_handler();
        set_error_handler(array(__CLASS__, 'errorHandler'));
        register_shutdown_function(array(__CLASS__, 'shutdown'));
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() === 0) {
            return false;
        }

        if (!defined('E_RECOVERABLE_ERROR')) {
            define('E_RECOVERABLE_ERROR', 4096);
        }

        switch ($errno) {
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
            case E_ERROR:
                throw new Exception('Fatal error: '.$errstr.' in '.$errfile.' on line '.$errline);
            //no break
            case E_USER_WARNING:
            case E_WARNING:
                throw new Exception('Error: '.$errstr.' in '.$errfile.' on line '.$errline);
            //no break
            case E_USER_NOTICE:
            case E_NOTICE:
                if (_PS_MODE_DEV_) {
                    throw new Exception('Notice: '.$errstr.' in '.$errfile.' on line '.$errline);
                }
                return true;
            default:
                throw new Exception('Unknown error: '.$errstr.' in '.$errfile.' on line '.$errline);
        }
    }

    public static function shutdown()
    {
        $l = TransModSB::getInstance();
        if (function_exists('error_get_last')) {
            $error = error_get_last();
            if ($error && $error['type'] === E_ERROR) {
                $message = $error['message'];
                $memory_regex = '/^Allowed memory size of (\d+) bytes exhausted \(tried to allocate (\d+) bytes\)$/u';
                $time_regex = '/^Maximum execution time of (\d+) second exceeded/u';

                if (preg_match($memory_regex, $message, $matches)) {
                    $message = $l->l('Allowed memory size of', __FILE__).' ';
                    $message .= self::convertMemory($matches[1]).' ';
                    $message .= $l->l('exhausted', __FILE__).' (';
                    $message .= $l->l('tried to allocate', __FILE__).' ';
                    $message .= self::convertMemory($matches[2]).' ';
                    $message .= ')';
                    LoggerSB::getInstance()->error($message);
                    LoggerSB::getInstance()->error(
                        $l->l('Your web-server is too slow, not enough RAM.', __FILE__)
                    );
                    LoggerSB::getInstance()->error(
                        $l->l('Try to reduce some of expert\'s settings.', __FILE__)
                    );
                } elseif (preg_match($time_regex, $message, $matches)) {
                    $message = $l->l('Maximum execution time of', __FILE__).' ';
                    $message .= (int)$matches[1].' ';
                    $message .= $l->l('second exceeded', __FILE__);

                    LoggerSB::getInstance()->error($message);
                    LoggerSB::getInstance()->error(
                        $l->l('Your web-server is too slow, increase PHP execution time limit.', __FILE__)
                    );
                    LoggerSB::getInstance()->error(
                        $l->l('Try to reduce some of expert\'s settings.', __FILE__)
                    );
                } else {
                    LoggerSB::getInstance()->error($message);
                }

                die(Tools::jsonEncode(array(
                    'hasError' => LoggerSB::getInstance()->hasError(),
                    'log' => LoggerSB::getInstance()->getMessages()
                )));
            }
        }
        exit;
    }

    public static $memory_units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    /**
     * @param $size
     * @return bool
     */
    public static function convertMemory($size)
    {
        if (!$size) {
            return '0B';
        }

        $i = floor(log($size, 1024));
        $size = round($size / pow(1024, $i), 2);

        return $size.' '.self::$memory_units[(int)$i];
    }
}
