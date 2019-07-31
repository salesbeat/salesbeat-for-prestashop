<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 19.12.18
 * Time: 15:36
 */

/**
 * @param Module $module
 * @return bool
 */
function upgrade_module_1_0_1($module)
{
    $module->registerHook('actionValidateOrder');
    return true;
}
