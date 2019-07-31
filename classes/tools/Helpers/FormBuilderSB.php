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

class FormBuilderSB
{
    protected $form = array(
        'tinymce' => false,
        'legend' => array(
            'title' => 'Nothing'
        ),
        'input' => array()
    );

    public function __construct($title)
    {
        $this->form['legend'] = array('title' => $title);
        return $this;
    }

    public function addField(
        $label,
        $name,
        $type,
        $required = null,
        $lang = null,
        $desc = null,
        $hint = null,
        $suffix = null,
        $options = null,
        $values = null,
        $is_bool = null,
        $empty_message = null,
        $additional_params = array()
    ) {
        if ($type == 'switch'
        && version_compare(_PS_VERSION_, '1.6.0.0', '<')) {
            $type = 'radio';
            if (!isset($additional_params['class'])) {
                $additional_params['class'] = 't';
            } else {
                $additional_params['class'] .= ' t';
            }
        }

        $field = array(
            'label' => $label,
            'name' => $name,
            'type' => $type
        );

        $t = TransModSB::getInstance();

        foreach (array(
                    'required',
                    'lang',
                    'desc',
                    'hint',
                    'suffix',
                    'options',
                    'values',
                    'is_bool',
                    'empty_message'
                 ) as $arg_func) {
            if (!is_null($$arg_func)) {
                $field[$arg_func] = $$arg_func;
            }
        }

        if (is_null($values)) {
            $values = array(
                array(
                    'id' => $name.'_on',
                    'value' => 1,
                    'label' => $t->l('Enabled', __FILE__)
                ),
                array(
                    'id' => $name.'_off',
                    'value' => 0,
                    'label' => $t->l('Disabled', __FILE__)
                )
            );
        }

        if (in_array($type, array('switch', 'radio', 'checkbox'))) {
            $field['values'] = $values;
        }

        $field = array_merge($field, $additional_params);

        if (array_key_exists('autoload_rte', $field) && $field['autoload_rte']) {
            $this->form['tinymce'] = true;
        }

        $this->form['input'][] = $field;
        #Fix standart Prestashop
        unset($required);
        unset($lang);
        unset($desc);
        unset($hint);
        unset($suffix);
        unset($options);
        unset($values);
        unset($is_bool);
        unset($empty_message);
        #End fix
        return $this;
    }

    public function addSubmit($title)
    {
        $this->form['submit'] = array(
            'title' => $title
        );
        return $this;
    }

    public function getForm()
    {
        return $this->form;
    }
}
