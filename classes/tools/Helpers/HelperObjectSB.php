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

class HelperObjectSB
{
    public static function validateObject($object, $definition_fields = null)
    {
        $errors = array();
        $definition = ObjectModel::getDefinition($object);
        if (is_null($definition_fields)) {
            $definition_fields = $definition['fields'];
        }
        $languages = ToolsModuleSB::getLanguages(true);
        $t = TransModSB::getInstance();

        $empty_field = $t->l('%s is empty', __FILE__);
        $empty_lang_field = $t->l('%s for lang %s is empty', __FILE__);

        $wrong_field = $t->l('%s wrong', __FILE__);
        $wrong_lang_field = $t->l('%s for lang %s wrong', __FILE__);

        $max_length_field = $t->l('%s size more %s', __FILE__);
        $max_length_lang_field = $t->l('%s for lang %s size more %s', __FILE__);

        $fields = array_keys($definition_fields);
        foreach ($fields as $field) {
            $l_field = $t->ld($field);
            if (array_key_exists($field, $definition_fields)) {
                $object_field = $object->{$field};
                if (array_key_exists('lang', $definition_fields[$field]) && $definition_fields[$field]['lang']) {
                    foreach ($languages as $lang) {
                        if (isset($definition_fields[$field]['required']) && $definition_fields[$field]['required']
                            && empty($object_field[$lang['id_lang']])) {
                            $errors[] = sprintf($empty_lang_field, $l_field, $lang['name']);
                        }

                        if (!empty($object_field[$lang['id_lang']])
                            && !forward_static_call_array(
                                array('Validate', $definition_fields[$field]['validate']),
                                array(
                                    $object_field[$lang['id_lang']]
                                )
                            )) {
                            $errors[] = sprintf($wrong_lang_field, $l_field, $lang['name']);
                        }

                        if (!empty($object_field[$lang['id_lang']])
                            && forward_static_call_array(
                                array('Validate', $definition_fields[$field]['validate']),
                                array(
                                    $object_field[$lang['id_lang']]
                                )
                            )
                            && array_key_exists('size', $definition_fields[$field])
                            && Tools::strlen($object_field[$lang['id_lang']]) > $definition_fields[$field]['size']) {
                            $errors[] = sprintf(
                                $max_length_lang_field,
                                $l_field,
                                $lang['name'],
                                $definition_fields[$field]['size']
                            );
                        }
                    }
                } else {
                    if (isset($definition_fields[$field]['required'])
                        && $definition_fields[$field]['required']
                        && empty($object_field)
                        && $definition_fields[$field]['type'] != ObjectModel::TYPE_BOOL) {
                        $errors[] = sprintf($empty_field, $l_field);
                    }

                    if (!empty($object_field)
                        && array_key_exists('validate', $definition_fields[$field])
                        && !forward_static_call_array(
                            array('Validate', $definition_fields[$field]['validate']),
                            array(
                                $object_field
                            )
                        )) {
                        $errors[] = sprintf($wrong_field, $l_field);
                    }

                    if (!empty($object_field)
                        && array_key_exists('validate', $definition_fields[$field])
                        && forward_static_call_array(
                            array('Validate', $definition_fields[$field]['validate']),
                            array(
                                $object_field
                            )
                        )
                        && array_key_exists('size', $definition_fields[$field])
                        && Tools::strlen($object_field) > $definition_fields[$field]['size']) {
                        $errors[] = sprintf($max_length_field, $l_field, $definition_fields[$field]['size']);
                    }
                }
            }
        }
        return $errors;
    }

    public static function copyFromPost(&$object, $post_array = null)
    {
        if (!is_null($post_array)) {
            $post = $post_array;
        } else {
            $post = &${'_POST'};
        }

        $definition = ObjectModel::getDefinition($object);
        $table = $definition['table'];
        /* Classical fields */
        foreach ($post as $key => $value) {
            if (key_exists($key, $object) && $key != 'id_'.$table) {
                /* Do not take care of password field if empty */
                if ($key == 'passwd' && Tools::getValue('id_'.$table) && empty($value)) {
                    continue;
                }
                /* Automatically encrypt password in MD5 */
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::encrypt($value);
                }
                $object->{$key} = $value;
            }
        }

        /* Multilingual fields */
        $rules = call_user_func(array(get_class($object), 'getValidationRules'), get_class($object));
        if (count($rules['validateLang'])) {
            $languages = ToolsModuleSB::getLanguages(false);
            foreach ($languages as $language) {
                foreach (array_keys($rules['validateLang']) as $field) {
                    if (isset($post[$field.'_'.(int)$language['id_lang']])) {
                        $object->{$field}[(int)$language['id_lang']] = $post[$field.'_'.(int)$language['id_lang']];
                    }
                }
            }
        }
    }
}
