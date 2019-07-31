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

class FixtureSB
{
    /**
     * @var string
     */
    protected $name;
    protected function __construct($module_name)
    {
        $this->name = $module_name;
    }

    /**
     * @var LoggerSB
     */
    protected static $instance = null;

    public static function getInstance($module_name)
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($module_name);
        }
        return self::$instance;
    }

    /**
     * @var array $primary_entities
     */
    protected $primary_entities = array();

    /**
     * @var array $entities
     */
    protected $entities = array();

    /**
     * @param string $class_name
     * @throws PrestaShopException
     */
    public function importEntity($class_name)
    {
        $relations = array();
        $definition = ObjectModel::getDefinition($class_name);
        if (array_key_exists('fixture', $definition)) {
            $fixture = $definition['fixture'];
            if (array_key_exists('relations', $fixture)) {
                $relations = $fixture['relations'];

                foreach ($relations as $relation) {
                    if (is_array($relation)) {
                        foreach ($relation['variations'] as $variation) {
                            if (strpos($variation, '::') === false && !in_array($variation, $this->entities)) {
                                if ($definition['table'] != $relation) {
                                    $this->importEntity(Tools::toCamelCase($variation, true));
                                }
                            }
                        }
                    } else {
                        if (strpos($relation, '::') === false && !in_array($relation, $this->entities)) {
                            if ($definition['table'] != $relation) {
                                $this->importEntity(Tools::toCamelCase($relation, true));
                            }
                        }
                    }
                }
            }
        }

        $data = array();
        $fixture = _PS_MODULE_DIR_.$this->name.'/fixtures/data/'.$definition['table'].'.json';
        if (file_exists($fixture)) {
            $data = Tools::jsonDecode(Tools::file_get_contents($fixture), true);
        }

        $lang_fixtures = array();

        $languages = ToolsModuleSB::getLanguages(false);
        $fixture_lang_path = _PS_MODULE_DIR_.$this->name.'/fixtures/langs/';

        foreach ($languages as $l) {
            $lang_fixtures[$l['iso_code']] = array();

            $fixture_lang = $fixture_lang_path.$l['iso_code'].'/data/'.$definition['table'].'.json';
            if (file_exists($fixture_lang)) {
                $lang_fixtures[$l['iso_code']] = Tools::jsonDecode(Tools::file_get_contents($fixture_lang), true);
            }
        }

        $this->prepareArray($data, $relations, $definition);

        $current_auto_increment = $this->getCurrentAutoIncrementTable($definition['table']);
        $old_data = $data;
        foreach ($data as &$row) {
            $id = $row['id'];
            unset($row['id']);

            $this->primary_entities[$id] = $current_auto_increment;
            $current_auto_increment++;
        }

        if ($this->checkFixtureImageManager($definition)) {
            foreach ($old_data as $old_row) {
                $this->copyImageEntity($definition, $old_row);
            }
        }

        if (!Db::getInstance()->insert(
            $definition['table'],
            $data,
            false,
            true,
            Db::INSERT_IGNORE
        )) {
            throw new PrestaShopException(
                'An SQL error occurred for entity <i>%1$s</i>: <i>%2$s</i>',
                $definition['table'],
                Db::getInstance()->getMsgError()
            );
        }
        unset($data);

        foreach ($lang_fixtures as $iso_code => &$data_lang) {
            $id_lang = Language::getIdByIso($iso_code);

            foreach ($data_lang as &$row) {
                $id = null;
                if (array_key_exists($row['id'], $this->primary_entities)) {
                    $id = $this->primary_entities[$row['id']];
                }
                unset($row['id']);
                $row['id_lang'] = $id_lang;
                $row[$definition['primary']] = $id;
            }

            $this->prepareArrayLang($data_lang, $definition);
        }

        $merge_data_lang = call_user_func_array('array_merge', array_values($lang_fixtures));
        if (count($merge_data_lang)) {
            if (!Db::getInstance()->insert(
                $definition['table'].'_lang',
                $merge_data_lang,
                false,
                true,
                Db::INSERT_IGNORE
            )) {
                throw new PrestaShopException(
                    'An SQL error occurred for entity <i>%1$s</i>: <i>%2$s</i>',
                    $definition['table'].'_lang',
                    Db::getInstance()->getMsgError()
                );
            }
        }

        $this->entities[] = $definition['table'];
    }

    public function prepareArray(&$data, $relations, $definition)
    {
        foreach ($data as &$row) {
            foreach ($row as $field_name => &$col) {
                if (array_key_exists($field_name, $relations)) {
                    $relation = $relations[$field_name];

                    if (is_array($relation)) {
                        $relation = $relation['variations'][$row[$relation['field']]];
                    }

                    if (strpos($relation, '::') !== false) {
                        list($class_name, $property) = explode('::', $relation);
                        $class_name = Tools::toCamelCase($class_name, true);

                        $id_lang = null;
                        $definition = ObjectModel::getDefinition($class_name);
                        if (array_key_exists('multilang', $definition)
                            && $definition['multilang']) {
                            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
                        }

                        $definition_field = $definition['fields'][$property];
                        if (isset($definition_field['lang']) && $definition_field['lang']) {
                            $sql = 'SELECT `'.pSQL($definition['primary']).'`
                             FROM `'._DB_PREFIX_.pSQL($definition['table']).'_lang`
                             WHERE `'.pSQL($property).'` = "'.pSQl($col).'" AND `id_lang` = '.(int)$id_lang;
                        } else {
                            $sql = 'SELECT `'.pSQL($definition['primary']).'`
                             FROM `'._DB_PREFIX_.$definition['table'].'`
                             WHERE `'.pSQL($property).'` = "'.pSQl($col).'"';
                        }

                        $col = (int)Db::getInstance()->getValue($sql);
                    } else {
                        if (array_key_exists($col, $this->primary_entities)) {
                            $col = $this->primary_entities[$col];
                        }
                    }
                }

                if (array_key_exists($field_name, $definition['fields'])) {
                    $def_field = $definition['fields'][$field_name];

                    if (in_array($def_field['type'], array(ObjectModel::TYPE_HTML, ObjectModel::TYPE_NOTHING))) {
                        $col = str_replace('\'', '\\\'', $col);
                    } else {
                        $col = pSQL($col);
                    }
                }
            }
        }
    }

    public function prepareArrayLang(&$data, $definition)
    {
        foreach ($data as &$row) {
            foreach ($row as $field_name => &$col) {
                if (array_key_exists($field_name, $definition['fields'])) {
                    $def_field = $definition['fields'][$field_name];

                    if (in_array(
                        $def_field['type'],
                        array(ObjectModel::TYPE_HTML, ObjectModel::TYPE_NOTHING)
                    )) {
                        $col = str_replace('\'', '\\\'', $col);
                    } else {
                        $col = pSQL($col);
                    }
                }
            }
        }
    }

    public function getCurrentAutoIncrementTable($table)
    {
        return (int)Db::getInstance()->getValue(
            'SELECT `AUTO_INCREMENT`
            FROM  INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = "'._DB_NAME_.'"
            AND   TABLE_NAME   = "'._DB_PREFIX_.pSQL($table).'";'
        );
    }

    public function copyImageEntity($definition, $row)
    {
        $class_image = Tools::toCamelCase($definition['table'], true);
        $fixture_img_path = _PS_MODULE_DIR_.$this->name.'/fixtures/img/'.$definition['table'].'/';

        $images = glob($fixture_img_path.$row['id'].'*\.jpg');

        if (is_array($images) && count($images)) {
            /**
             * @var WidgetImage $object_image
             */
            $object_image = new $class_image();
            $object_image->id = $this->primary_entities[$row['id']];
            $object_image->type = $row['type'];

            $object_image->createImgFolder();

            foreach ($images as $image) {
                $image_name = basename($image);
                $image_name = str_replace($row['id'], $this->primary_entities[$row['id']], $image_name);
                copy($image, $object_image->getPathAbsolute().$image_name);
            }
        }
    }

    public function checkFixtureImageManager($definition)
    {
        if (array_key_exists('fixture', $definition)) {
            $fixture = $definition['fixture'];

            if (array_key_exists('image_manager', $fixture)) {
                return true;
            }
        }

        return false;
    }
}
