<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 26.10.18
 * Time: 12:39
 */

require_once(dirname(__FILE__).'/classes/tools/config.php');
class SalesBeat extends Module
{
    public $module_container;

    public function __construct()
    {
        
        if (!isset($GLOBALS['COD'])) {
            $GLOBALS['COD'] = 0;
        }

        $this->name = 'salesbeat';
        $this->tab = 'shipping_logistics';
        $this->author = 'PrestaInfo';
        $this->version = '1.0.2';
        $this->bootstrap = true;
        $this->need_instance = 0;

        $this->module_container = ModuleContainerSB::getInstance($this);
        $this->module_container->setTabs(array(
            array(
                'tab' => 'AdminSalesBeatSettings',
                'parent' => 'AdminParentShipping',
                'name' => array(
                    'ru' => 'Salesbeat',
                    'en' => 'Salesbeat'
                )
            )
        ))->setConfig(array(
            'token' => '',
            'token_order' => '',
            'width' => 1,
            'height' => 1,
            'length' => 1,
            'weight' => 1,
            'weight_unit' => 'kg',
            'width_unit' => 'sm',
            'percent_insurance' => 0,
            'test_mode' => 0,
            'cash_on_delivery' => false,
        ))->setHooks(array(
            'displayHeader',
            'actionValidateOrder',
            'displayProductButtons',
            'displayRightColumnProduct',
            'displayOrderDetail',
            'displayAdminOrder',
            'actionAdminControllerSetMedia'
        ))->setClasses(array(
            'SBCarrierCart',
            'SBCarrier'
        ));

        $this->tabs = $this->module_container->getTabs();

        parent::__construct();
        $this->displayName = $this->l('Salesbeat delivery service');
        $this->description = $this->l('Show the exact cost and delivery time to the product page. Difficult table "About delivery" - in the past');
    }

    public function install()
    {
        return parent::install()
            && $this->module_container->install()
            && $this->installCarriers();
    }

    public function uninstall()
    {
        $this->uninstallCarriers();
        return $this->module_container->uninstall()
            && parent::uninstall();
    }

    public function installCarriers()
    {
        foreach ($this->getCarriers() as $c) {
            $id_carrier = SBCarrierInstaller::installCarrier(
                $this->name,
                $c['name'],
                $c['range']
            );

            if ($id_carrier) {
                if (Configuration::get('PS_CARRIER_DEFAULT') < 0) {
                    Configuration::updateValue('PS_CARRIER_DEFAULT', $id_carrier);
                }

                $carrier = new Carrier($id_carrier);
                $salesbeat_carrier = new SBCarrier();
                $salesbeat_carrier->id_reference = $carrier->id_reference;
                $salesbeat_carrier->unique_name = $c['id'];
                $salesbeat_carrier->save();
            }
        }
        return true;
    }

    public function uninstallCarriers()
    {
        try {
            foreach ($this->getCarriers() as $carrier) {
                $id_reference = SBCarrier::getReferenceByUniqueName($carrier['id']);
                if ($id_reference) {
                    SBCarrierInstaller::uninstallCarrier($id_reference);
                }
            }
        } catch (Exception $e) {
            unset($e);
        };
        return true;
    }

    public function getCarriers()
    {
        return array(
            array(
                'id' => '1',
                'name' => $this->l('Salesbeat: select delivery option'),
                'range' => array(
                    'min' => 0,
                    'max' => 100500
                )
            )
        );
    }

    public function getTemplateVars()
    {
        $ids = SBCarrier::getIdsCarriers();
        $carrier_cart = array();
        foreach ($ids as $id) {
            $cc = SBCarrierCart::getInstanceByCarrierAndCart(
                $id,
                $this->context->cart->id
            );
            $carrier_cart[$id] = $cc->getDelivery();
        }

        $vars = array(
            'token' => ConfSB::getConf('token'),
            'ids' => $ids,
            'def_currency' => $this->context->currency,
            'is_ps_17' => (version_compare(_PS_VERSION_, '1.7.0.0', '>=') ? 1 : 0),
            'carrier_cart' => $carrier_cart,
            'cash_on_delivery' => ConfSB::getConf('cash_on_delivery')
        );

        $id_product = Tools::getValue('id_product');
        if ($id_product) {
            $vars = array_merge($vars, $this->getProductInformation($id_product));
        }

        return $vars;
    }

    public function getProductInformation($id_product)
    {
        $product = new Product(
            $id_product,
            true,
            $this->context->language->id
        );

        $unit_length = 1;
        if (ConfSB::getConf('width_unit') == 'kg') {
            $unit_length = 100;
        }

        $unit_weight = 1;
        if (ConfSB::getConf('weight_unit') == 'kg') {
            $unit_weight = 1000;
        }

        $product_data = array(
            'product' => array(
                'width' => ($product->width > 0 ? $product->width * $unit_length : ConfSB::getConf('width')),
                'height' => ($product->height > 0 ? $product->height * $unit_length : ConfSB::getConf('height')),
                'length' => ($product->depth > 0 ? $product->depth * $unit_length : ConfSB::getConf('length')),
                'weight' => ($product->weight > 0 ? $product->weight * $unit_weight : ConfSB::getConf('weight')),
                'quantity' => $product->minimal_quantity,
                'price' => $product->getPrice()
            ),
            'combinations' => array()
        );

        $combinations = $product->getAttributeCombinations($this->context->language->id);
        foreach ($combinations as $combination) {
            $weight = (float)$product->weight + (float)$combination['weight'];
            $product_data['combinations'][$combination['id_product_attribute']] = array(
                'width' => ($product->width > 0 ? $product->width * $unit_length : ConfSB::getConf('width')),
                'height' => ($product->height > 0 ? $product->height * $unit_length : ConfSB::getConf('height')),
                'length' => ($product->depth > 0 ? $product->depth * $unit_length : ConfSB::getConf('length')),
                'weight' => ($weight > 0 ? $weight * $unit_weight : ConfSB::getConf('weight')),
                'quantity' => $combination['minimal_quantity'],
                'price' => $product->getPrice(true, $combination['id_product_attribute'])
            );
        }

        return $product_data;
    }

    public function displayAjaxGetCartProducts()
    {
        return array(
            'products' => self::formatSalesBeatProducts($this->context->cart)
        );
    }

    /**
     * @param $cart
     * @return array
     */
    public static function formatSalesBeatProducts($cart)
    {
        $cart_products = $cart->getProducts();

        $products = array();
        foreach ($cart_products as $product) {
            $price = $product['price'] * $product['cart_quantity'];
            $weight = (
            $product['id_product_attribute'] ?
                $product['weight_attribute'] :
                $product['weight']
            );

            $unit_weight = 1;
            if (ConfSB::getConf('weight_unit') == 'kg') {
                $unit_weight = 1000;
            }

            if ($weight == 0) {
                $weight = ConfSB::getConf('weight');
                $weight = $weight * $unit_weight;
            } else {
                $weight = $weight * $unit_weight;
            }

            if ($product['depth'] == 0) {
                $product_depth = ConfSB::getConf('length');
            }
            else{
                 $product_depth = $product['depth'];
            }

            if ($product['height'] == 0) {
                $product_height = ConfSB::getConf('height');
            }
            else{
                 $product_height = $product['height'];
            }

            if ($product['width'] == 0) {
                $product_width= ConfSB::getConf('width');
            }
            else{
                 $product_width = $product['width'];
            }




            $products[] = array(
                'id' => $product['id_product'].(
                    $product['id_product_attribute']
                    ? '-'.$product['id_product_attribute']
                    : ''
                ),
                'name' => $product['name'],
                //'price_to_pay' => ceil(!ConfSB::getConf('cash_on_delivery') ? $price : 0),
                'price_to_pay' => ceil(($GLOBALS['COD']==1) ? $price : 0),
                'x' => $product_depth, // длина, в см depth
                'y' => $product_height, // высота, в см height
                'z' => $product_width, // ширина, в см width
                'price_insurance' => ceil($price),
                'weight' => $weight,
                'quantity' => (string)$product['cart_quantity']
            );
        }
        return $products;
    }



    public static function formatSalesBeatProductsDiscounts($cart)
    {
        $cart_products = $cart->getProducts();
        $count_cart_products = count($cart_products);
        $single_item_discount = ($GLOBALS['total_discounts_paimon']/$count_cart_products);



        $products = array();
        foreach ($cart_products as $product) {
            //$price = $product['price'] * $product['cart_quantity'];

            if ((string)$product['cart_quantity'] > 1) {
                # code...
                $single_item_discount = $single_item_discount / (string)$product['cart_quantity'];
            }

            $price = $product['price'];
            $weight = (
            $product['id_product_attribute'] ?
                $product['weight_attribute'] :
                $product['weight']
            );

            $width = $product['width'];
            $height = $product['height'];
            $depth = $product['depth'];

            $unit_weight = 1;
            if (ConfSB::getConf('weight_unit') == 'kg') {
                $unit_weight = 1000;
            }

            if ($weight == 0) {
                $weight = ConfSB::getConf('weight');
            } else {
                $weight = $weight * $unit_weight;
            }

            $products[] = array(
                'id' => $product['id_product'].(
                    $product['id_product_attribute']
                    ? '-'.$product['id_product_attribute']
                    : ''
                ),
                'name' => $product['name'],
                'price_to_pay' => ceil(!ConfSB::getConf('cash_on_delivery') ? $price - $single_item_discount : 0),
                'price_insurance' => ceil($price - $single_item_discount),
                'weight' => $weight,
                //paimon start
                'depth' =>  $depth, //x: Number, длина, в см
                'height' =>  $height, //y: Number, высота, в см
                'width' =>  $width, //z: Number, ширина, в см
                //paimon stop
                'quantity' => (string)$product['cart_quantity']
            );



        }
        return $products;
    }


    public function displayAjaxSaveDeliveryData()
    {
        $data = Tools::getValue('data');
        $update_cart = (int)Tools::getValue('update_cart');
        $carrier_cart = SBCarrierCart::getInstanceByCarrierAndCart(
            Tools::getValue('id_carrier'),
            $this->context->cart->id
        );
        $carrier_cart->pvz_id = '';
        HelperObjectSB::copyFromPost($carrier_cart, $data);

        if ($update_cart) {
            $carrier_cart->delivery_method_id = '';
            $carrier_cart->pvz_id = '';
            $carrier_cart->delivery_method_name = '';
        }

        if ($carrier_cart->pvz_id) {
            $carrier_cart->comment = '';
            $carrier_cart->flat = '';
            $carrier_cart->house = '';
            $carrier_cart->street = '';
        } else {
            $carrier_cart->pvz_address = '';
            $carrier_cart->pvz_id = '';
        }

        $carrier_cart->save();
        return array();
    }

    public static $delay_carriers = array();
    public static $cache_order_shipping_cost = array();
    public function getOrderShippingCost($params, $shipping_cost)
    {
        $cart = $params;
        if (!($cart instanceof Cart)) {
            return false;
        }
        unset($shipping_cost);

        /**
         * @var $address Address
         */
        $address = $cart->getAddressCollection();
        $address = current($address);

        $city = Hook::exec('actionGetCityGeolocation');
        if (!$address) {
            $address = new Address();
            $address->id = 900000;
            $address->city = $city;
            $address->id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        }
        if (!$address->city) {
            $address->city = $city;
        }

        if (!Validate::isLoadedObject($address)) {
            return false;
        }

        if (!isset($cart->id_carrier_current)) {
            return false;
        }

        if (array_key_exists($cart->id_carrier_current, self::$cache_order_shipping_cost)) {
            return self::$cache_order_shipping_cost[$cart->id_carrier_current];
        }

        $unique_name = SBCarrier::getUniqueNameByIdCarrier($cart->id_carrier_current);
        if (!$unique_name) {
            return false;
        }

        $carrier_cart = SBCarrierCart::getInstanceByCarrierAndCart(
            $cart->id_carrier_current,
            $cart->id
        );

        $carrier = new Carrier($cart->id_carrier_current);
        $country = new Country($address->id_country);
        $id_zone = (int)$country->id_zone;
        if ($country->contains_states && $address->id_state)
        {
            $state = new State($address->id_state);
            $id_zone = (int)$state->id_zone;
        }

        $price = false;

        if (!$carrier_cart->delivery_price) {
            switch ($carrier->shipping_method)
            {
                case Carrier::SHIPPING_METHOD_FREE:
                    $price = 0;
                    break;
                case Carrier::SHIPPING_METHOD_PRICE:
                    $price = $carrier->getDeliveryPriceByPrice(
                        $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS),
                        $id_zone,
                        $this->context->currency->id
                    );
                    break;
                case Carrier::SHIPPING_METHOD_WEIGHT:
                    $price = $carrier->getDeliveryPriceByWeight(
                        $cart->getTotalWeight(),
                        $id_zone,
                        $this->context->currency->id
                    );
                    break;
            }
        } else {
            $price = (float)$carrier_cart->delivery_price;
        }

        $price = Tools::convertPrice($price);

        self::$cache_order_shipping_cost[$cart->id_carrier_current] = $price;
        $carrier_cart->save();
        if (isset(self::$cache_order_shipping_cost[$cart->id_carrier_current])) {
            return self::$cache_order_shipping_cost[$cart->id_carrier_current];
        }

        return false;
    }

    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->addJqueryUI('autocomplete');

        if (Tools::isSubmit('sb_ajax')) {
            $method = Tools::getValue('method');
            $method_name = 'displayAjax'.Tools::toCamelCase($method, true);
            if (method_exists($this, $method_name)) {
                $response = call_user_func(array($this, $method_name));
                $errors = $this->context->controller->errors;
                die(Tools::jsonEncode(
                    array(
                        'data' => $response,
                        'hasErrors' => (count($errors) ? true : false),
                        'errors' => $errors
                    )
                ));
            }
        }

        if ($this->context->controller instanceof ProductController
        || $this->context->controller instanceof ParentOrderController
        || $this->context->controller instanceof OrderController) {
            if (version_compare(_PS_VERSION_, '1.7.0.0', '<')) {
                $this->context->controller->addJS(array(
                    '//app.salesbeat.pro/static/widget/js/widget.js',
                    '//app.salesbeat.pro/static/widget/js/cart_widget.js',
                    $this->getPathUri().'views/js/compatibility.js',
                    $this->getPathUri().'views/js/delivery.js',
                    $this->getPathUri().'views/js/product.js',
                ));
            } else {
                $this->context->controller->registerJavascript(
                    'salesbeat',
                    '//app.salesbeat.pro/static/widget/js/widget.js',
                    array(
                        'server' => 'remote'
                    )
                );
                $this->context->controller->registerJavascript(
                    'salesbeat-cart_widget',
                    '//app.salesbeat.pro/static/widget/js/cart_widget.js',
                    array(
                        'server' => 'remote'
                    )
                );
                $this->context->controller->registerJavascript(
                    'compatibility-salesbeat',
                    $this->getPathUri().'views/js/compatibility.js',
                    array(
                        'server' => 'remote'
                    )
                );
                $this->context->controller->registerJavascript(
                    'delivery-salesbeat',
                    $this->getPathUri().'views/js/delivery.js',
                    array(
                        'server' => 'remote'
                    )
                );
                $this->context->controller->registerJavascript(
                    'product-salesbeat',
                    $this->getPathUri().'views/js/product.js',
                    array(
                        'server' => 'remote'
                    )
                );
            }

            $this->context->controller->addCSS(
                array(
                    $this->getPathUri().'views/css/product.css'
                )
            );

            $this->context->smarty->assign('sb', $this->getTemplateVars());

            return $this->display(__FILE__, 'header.tpl');
        }
    }

    public function hookDisplayRightColumnProduct()
    {
        return $this->display(__FILE__, 'product.tpl');
    }

    public function hookDisplayProductButtons()
    {
        return $this->hookDisplayRightColumnProduct();
    }

    public function hookDisplayOrderDetail($params)
    {
        /**
         * @var Order $order
         */
        $order = $params['order'];
        return $this->renderAdminOrder($order);
    }

    public function hookDisplayAdminOrder($params)
    {
        $order = new Order($params['id_order']);
                
        if ($order->total_discounts) {
            $GLOBALS['total_discounts_paimon'] = round($order->total_discounts);
       }

        if (Tools::isSubmit('sendOrder')) {
            $GLOBALS['COD'] = 0;
            $this->sendOrder($order);
        }
        if (Tools::isSubmit('sendOrderCashOnDelivery')) {
            $GLOBALS['COD'] = 1;
            $this->sendOrder($order, true);
        }
        if (Tools::isSubmit('deleteOrder')) {
            SalesBeatApi::call(
                DeleteSBMethod::create()->setRequest($order)
            );
            $delivery_info = SBCarrierCart::getInstanceByCarrierAndCart(
                $order->id_carrier,
                $order->id_cart
            );
            $delivery_info->track_code = '';
            $delivery_info->salesbeat_order_id = '';
            $delivery_info->save();
        }
        return $this->renderAdminOrder($order);
    }

    /**
     * @param Order $order
     * @param bool $cach_on_delivery
     * @return bool
     */
    public function sendOrder($order, $cach_on_delivery = false)
    {
        

        $response = SalesBeatApi::call(
            CreateSBMethod::create()->setRequest($order, $cach_on_delivery)
        );

        if (isset($response['success']) && $response['success']) {
            $delivery_info = SBCarrierCart::getInstanceByCarrierAndCart(
                $order->id_carrier,
                $order->id_cart
            );
            $delivery_info->track_code = (string)$response['track_code'];
            $delivery_info->salesbeat_order_id = (string)$response['salesbeat_order_id'];
            $delivery_info->save();
        }
    }

    public function renderAdminOrder($order)
    {
        $delivery_info = SBCarrierCart::getInstanceByCarrierAndCart(
            $order->id_carrier,
            $order->id_cart
        );
        
        if (!Validate::isLoadedObject($delivery_info)) {
            return '';
        }

        $this->context->smarty->assign(array(
            'delivery_info' => $delivery_info,
            'is_admin' => defined('_PS_ADMIN_DIR_')
        ));
        
        return $this->display(__FILE__, 'admin_order.tpl');
    }

    public function hookActionAdminControllerSetMedia()
    {

    }

    public function hookActionValidateOrder($params)
    {
        /**
         * @var Order $order
         */
        $order = $params['order'];
        $carrier_cart = SBCarrierCart::getInstanceByCarrierAndCart(
            $order->id_carrier,
            $order->id_cart
        );

        if ($order->id_address_delivery) {
            $address = new Address($order->id_address_delivery);
            $city = (
                $carrier_cart->region_name ?
                $carrier_cart->region_name.', ' :
                ''
            ).$carrier_cart->city_name;
            $address_text = array();

            if ($carrier_cart->street) {
                $address_text[] = $carrier_cart->street;
            }

            if ($carrier_cart->house_block) {
                $address_text[] = $carrier_cart->house_block;
            }

            if ($carrier_cart->house) {
                $address_text[] = $carrier_cart->house;
            }

            if ($carrier_cart->flat) {
                $address_text[] = $carrier_cart->flat;
            }

            $address_text = implode(', ', $address_text);

            $address1 = '';
            $address2 = '';
            if (Tools::strlen($address_text) > 128) {
                $address1 = Tools::substr($address_text, 0, 128);
                $address2 = Tools::substr($address_text, 128);

                if (Tools::strlen($address2) > 128) {
                    $address2 = Tools::substr($address2, 128, 128);
                }
            } else {
                $address1 = $address_text;
            }

            if ($address1) {
                $address->address1 = $address1;
            }
            if ($address2) {
                $address->address2 = $address2;
            }

            if ($city && $address->city != $city) {
                $address->city = $city;
            }

            if ($carrier_cart->index
                && $address->postcode != $carrier_cart->index
                && Validate::isPostCode($carrier_cart->index)) {
                $address->postcode = $carrier_cart->index;
            }

            $address->save();
        }
    }
}