<?php
/**
 * This file is part of the Prestashop Shipping module of DPD Nederland B.V.
 *
 * Copyright (C) 2017  DPD Nederland B.V.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

require_once(__DIR__.'/vendor/autoload.php');

class dpdconnect extends Module
{
    const VERSION = 1.0;

    public $dpdHelper;
    public $dpdCarrier;
    public $dpdParcelPredict;
    public $dpdCustomTemplates;

    private $ownControllers = [
        'AdminDpdLabels' => 'DPD label',
        'AdminDpdShippingList' => 'DPD ShippingList',
        'AdminDpdProductAttributesController' => 'DPD Product Attributes',
        'AdminDpdFreshFreeze' => 'DPD Fresh/Freeze',
    ];
    private $hooks = [
        'displayAdminOrderTabOrder',
        'displayAdminOrderContentOrder',
        'actionAdminBulkAffectZoneAfter',
        'displayCarrierList',
        'actionCarrierProcess',
        'displayOrderConfirmation',
        'displayBeforeCarrier',
        'actionValidateOrder',
        'displayFooter',
    ];

    public function __construct()
    {
        $this->dpdHelper = new \DpdConnect\classes\DpdHelper();
        $this->dpdCarrier = new \DpdConnect\classes\DpdCarrier();
        $this->dpdParcelPredict = new \DpdConnect\classes\DpdParcelPredict();
        $this->dpdCustomTemplates = [
            'override/controllers/admin/templates/products/shipping.tpl',
        ];

        // the information about the plugin.
        $this->version = self::VERSION;
        $this->name = "dpdconnect";
        $this->displayName = $this->l("DPD Connect");
        $this->author = "DPD Nederland B.V.";
        $this->tab = 'shipping_logistics';
        $this->limited_countries = ['be', 'lu', 'nl'];
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();
    }
    /**
     * this is triggered when the plugin is installed
     */
    public function install()
    {
        if (parent::install()) {
            Configuration::updateValue('dpd', 'dpdconnect');
            Configuration::updateValue('dpdconnect_parcel_limit', 12);
            Configuration::updateValue('dpdconnect_connect_url', \DpdConnect\Sdk\Client::ENDPOINT);

            // Use one of previous keys before update to store in new 'gmaps_key'
            $gmapsKey = '';
            if (Configuration::get('gmaps_client_key')) {
                // Use client key
                $gmapsKey = Configuration::get('gmaps_client_key');
            } else if(!$gmapsKey && Configuration::get('gmaps_server_key')) {
                // Client key was not set, so we use server key
                $gmapsKey = Configuration::get('gmaps_server_key');
            }

            Configuration::updateValue('gmaps_key', $gmapsKey);
            Configuration::updateValue('use_gmaps_key', 1);
        }

        // Install Tabs
        $parent_tab = new Tab();

        $parent_tab->name[$this->context->language->id] = $this->l('DPD configuration');
        $parent_tab->class_name = 'AdminDpd';
        $parent_tab->id_parent = 0; // Home tab
        $parent_tab->module = $this->name;
        $parent_tab->add();
        $tab = new Tab();

        $tab->name[$this->context->language->id] = $this->l('DPD Product Attributes');
        $tab->class_name = 'AdminDpdProductAttributes';
        $tab->id_parent = $parent_tab->id;
        $tab->module = $this->name;
        $tab->add();

        if (!$this->dpdHelper->installDB()) {
            //TODO create log that database could not be installed.
            return false;
        }
        foreach ($this->hooks as $hookName) {
            if (!$this->registerHook($hookName)) {
            //TODO create a log that hook could not be installed.
                return false;
            }
        }
        if (!$this->dpdHelper->installControllers($this->ownControllers)) {
            //TODO create a log that hook could not be installed.
            return false;
        }
        if (!$this->dpdCarrier->createCarriers()) {
            //TODO create a log that the carrier could not be installed
            return false;
        }
        // Add custom templates
	    if (!$this->addOverrideTemplates()) {
            return false;
	    }
        return true;
    }

    public function enable($force_all = false)
    {
        parent::enable($force_all);

        $this->addOverrideTemplates();
    }

    public function disable($force_all = false)
    {
        parent::disable($force_all);

        $this->removeOverrideTemplates();
    }

    /**
     * this is triggered when the plugin is uninstalled
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        } else {
            $tab = new Tab();
            $tab->disablingForModule('dpdconnect');

            // Uninstall Tabs
            $moduleTabs = Tab::getCollectionFromModule($this->name);
            if (!empty($moduleTabs)) {
                foreach ($moduleTabs as $moduleTab) {
                    $moduleTab->delete();
                }
            }

            $this->dpdCarrier->deleteCarriers();
            Configuration::updateValue('dpd', 'not installed');

            // Remove custom templates
            $this->removeOverrideTemplates();

            return true;
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name)) {
            $connectusername = strval(Tools::getValue("dpdconnect_username"));
            $connectpassword = strval(Tools::getValue("dpdconnect_password"));
            if ($connectpassword == null) {
                $connectpassword = Configuration::get('dpdconnect_password');
            } else {
                $connectpassword = \DpdConnect\classes\DpdEncryptionManager::encrypt($connectpassword);
            }
            $depot = strval(Tools::getValue("dpdconnect_depot"));
            $company = strval(Tools::getValue("company"));
            $street = strval(Tools::getValue("street"));
            $postalcode = strval(Tools::getValue("postalcode"));
            $place = strval(Tools::getValue("place"));
            $country = strval(Tools::getValue("country"));
            $email = strval(Tools::getValue("email"));
            $vatnumber = strval(Tools::getValue("vatnumber"));
            $eorinumber = strval(Tools::getValue("eorinumber"));
            $spr = strval(Tools::getValue("spr"));
            $gmapsKey = Tools::getValue('gmaps_key');
            $useGmapsKey = Tools::getValue('use_gmaps_key');
            $defaultProductHcs = Tools::getValue('default_product_hcs');
            $defaultProductWeight = Tools::getValue('default_product_weight');
            $defaultProductCountryOfOrigin = Tools::getValue('default_product_country_of_origin');
            $countryOfOriginFeature = Tools::getValue('country_of_origin_feature');
            $customsValueFeature = Tools::getValue('customs_value_feature');
            $hsCodeFeature = Tools::getValue('hs_code_feature');
            $connecturl = strval(Tools::getValue("dpdconnect_url"));

            $error = null;
            if (empty($connecturl)) {
                $error = $this->l('Connect url is obligatory');
            }
            if (empty($company)) {
                $error = $this->l('Company is obligatory');
            }
            if (empty($street)) {
                $error = $this->l('Street is obligatory');
            }
            if (empty($postalcode)) {
                $error = $this->l('Postalcode is obligatory');
            }
            if (empty($place)) {
                $error = $this->l('Place is obligatory');
            }
            if (empty($country)) {
                $error = $this->l('Country is obligatory');
            }
            if (empty($email)) {
                $error = $this->l('Email is obligatory');
            }
            if (!$error) {
                Configuration::updateValue('dpdconnect_connect_username', $connectusername);
                if ($connectpassword) {
                    Configuration::updateValue('dpdconnect_connect_password', $connectpassword);
                }
                Configuration::updateValue('dpdconnect_depot', $depot);
                Configuration::updateValue('dpdconnect_company', $company);
                Configuration::updateValue('dpdconnect_street', $street);
                Configuration::updateValue('dpdconnect_postalcode', $postalcode);
                Configuration::updateValue('dpdconnect_place', $place);
                Configuration::updateValue('dpdconnect_country', $country);
                Configuration::updateValue('dpdconnect_email', $email);
                Configuration::updateValue('dpdconnect_vatnumber', $vatnumber);
                Configuration::updateValue('dpdconnect_eorinumber', $eorinumber);
                Configuration::updateValue('dpdconnect_spr', $spr);
                Configuration::updateValue('gmaps_key', $gmapsKey);
                Configuration::updateValue('use_gmaps_key', $useGmapsKey);
                Configuration::updateValue('dpdconnect_default_product_hcs', $defaultProductHcs);
                Configuration::updateValue('dpdconnect_default_product_weight', $defaultProductWeight);
                Configuration::updateValue('dpdconnect_default_product_country_of_origin', $defaultProductCountryOfOrigin);
                Configuration::updateValue('dpdconnect_country_of_origin_feature', $countryOfOriginFeature);
                Configuration::updateValue('dpdconnect_customs_value_feature', $customsValueFeature);
                Configuration::updateValue('dpdconnect_hs_code_feature', $hsCodeFeature);
                Configuration::updateValue('dpdconnect_connect_url', $connecturl);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError($error);
            }
        }

        $formAccountSettings = [
            'legend' => [
                'title' => $this->l('Account Settings'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('DPD-Connect username'),
                    'name' => 'dpdconnect_username',
                    'required' => true
                ],
                [
                    'type' => 'password',
                    'label' => $this->l('DPD-Connect password'),
                    'name' => 'dpdconnect_password',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('DPD-Connect depot'),
                    'name' => 'dpdconnect_depot',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Google Maps API key'),
                    'desc' => $this->l('When below field is "no", this value is required.'),
                    'name' => 'gmaps_key',
                    'required' => false
                ],
                [
                    'type'      => 'radio',
                    'label'     => $this->l("Use DPD's Google Maps API Key"),
                    'desc'      => $this->l('These may be subject to rate limiting, high volume users should use their own Google keys.'),
                    'name'      => 'use_gmaps_key',
                    'required'  => true,
                    'class'     => 't',
                    'is_bool'   => true,
                    'values'    => array(
                        array(
                            'id'    => 'yes',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id'    => 'no',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ],
            ],
        ];

        $formAdres = [
            'legend' => [
                'title' => $this->l('Shipping Address'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Company name'),
                    'name' => 'company',
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Street + house number'),
                    'name' => 'street',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Postal Code'),
                    'name' => 'postalcode',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Place'),
                    'name' => 'place',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Country code'),
                    'name' => 'country',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'email',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Vat Number'),
                    'name' => 'vatnumber',
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Eori Number'),
                    'name' => 'eorinumber',
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('HMRC number'),
                    'hint' => $this->l('Mandatory if the value of the parcel is ≤ £ 135.'),
                    'name' => 'spr',
                    'required' => false
                ],
            ],
        ];

        $features = Feature::getFeatures($this->context->language->id);
        $features[] = null;

        $productSettings = [
            'legend' => [
                'title' => $this->l('Product settings'),
            ],
            'description' => 'Configure what features or default will be used for products. If features or defaults are not a solution, use the "DPD Product Attributes" tab instead.',
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Default product weight'),
                    'name' => 'default_product_weight',
                    'required' => false
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Customs value Feature'),
                    'hint' => $this->l('Select the product feature where the customs value is defined. If features are not used for customs value, leave empty to use DPD Product attributes or regular product price.'),
                    'name' => 'customs_value_feature',
                    'options' => [
                        'query' => $features,
                        'id' => 'id_feature',
                        'name' => 'name',
                    ],
                    'required' => false
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Country of origin Feature'),
                    'hint' => $this->l('Select the product feature where the country of origin is defined. If features are not used for country of origin, leave empty.'),
                    'name' => 'country_of_origin_feature',
                    'options' => [
                        'query' => $features,
                        'id' => 'id_feature',
                        'name' => 'name',
                    ],
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Default Country of Origin'),
                    'name' => 'default_product_country_of_origin',
                    'required' => false
                ],
                [
                    'type' => 'select',
                    'label' => $this->l('Harmonized System Code Feature'),
                    'hint' => $this->l('Select the product feature where the Harmonized System Code is defined. If features are not used for harmonized system codes, leave empty.'),
                    'name' => 'hs_code_feature',
                    'options' => [
                        'query' => $features,
                        'id' => 'id_feature',
                        'name' => 'name',
                    ],
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Default Harmonized System Code'),
                    'name' => 'default_product_hcs',
                    'required' => false
                ],
            ],
        ];

        $advancedSettings = [
            'legend' => [
                'title' => $this->l('Advanced settings'),
            ],
            'description' => 'Settings below can be used for development and debugging purposes.',
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('DPD-Connect url'),
                    'name' => 'dpdconnect_url',
                    'required' => true
                ],
            ],
        ];

        $submit = [
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ],
        ];

        return $output . $this->dpdHelper->displayConfigurationForm($this, $formAccountSettings, $formAdres, $productSettings, $advancedSettings, $submit);
    }

    public function hookDisplayAdminOrderTabOrder($params)
    {
        $orderId = Tools::getValue('id_order');
        $parcelShopId = $this->dpdParcelPredict->getParcelShopId($orderId);

        if ($this->dpdParcelPredict->checkIfDpdSending($orderId)) {
            $this->context->smarty->assign(
                [
                    'isDpdCarrier' => $this->dpdParcelPredict->checkifParcelCarrier($orderId),
                    'dpdParcelshopId' => $parcelShopId
                ]
            );
            return $this->display(__FILE__, '_adminOrderTab.tpl');
        }
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        $orderId = Tools::getValue('id_order');
        $parcelShopId = $this->dpdParcelPredict->getParcelShopId($orderId);
        $parcelCarrier = $this->dpdParcelPredict->checkifParcelCarrier($orderId);

        if ($this->dpdParcelPredict->checkIfDpdSending($orderId)) {
            $link = new LinkCore;
            $urlGenerateLabel = $link->getAdminLink('AdminDpdLabels');
            $urlGenerateLabel = $urlGenerateLabel . '&ids_order[]=' . $orderId;

            $urlGenerateReturnLabel = $urlGenerateLabel . '&return=true';



            $this->context->smarty->assign(
                [
                    'parcelCarrier' => $parcelCarrier,
                    'parcelShopId' => $parcelShopId,
                    'number' => \DpdConnect\classes\DpdLabelGenerator::countLabels($orderId),
                    'isInDb' => \DpdConnect\classes\DpdLabelGenerator::getLabelOutOfDb($orderId),
                    'urlGenerateLabel' => $urlGenerateLabel,
                    'urlGenerateReturnLabel' => $urlGenerateReturnLabel,
                    'isReturnInDb'=> \DpdConnect\classes\DpdLabelGenerator::getLabelOutOfDb($orderId, true),
                    'deleteGeneratedLabel' => $urlGenerateLabel . '&delete=true',
                    'deleteGeneratedRetourLabel' => $urlGenerateReturnLabel . '&delete=true'
                ]
            );
            return $this->display(__FILE__, '_adminOrderTabLabels.tpl');
        }
    }
    public function hookActionCarrierProcess($params)
    {
         //this adds parcel-id to the coockie when the carrier is used
        if ((int)$params['cart']->id_carrier === (int)$this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_parcelshop"))) {
            if (empty($this->context->cookie->parcelId) && ($this->context->cookie->parcelId == '')) {
                $this->context->controller->errors[] = $this->l('Please select a parcelshop');
            }
        }
    }


    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['objOrder'];
        if ((int)$order->id_carrier === (int)$this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_parcelshop"))) {
            if (!empty($this->context->cookie->parcelId) && !($this->context->cookie->parcelId == '')) {
                Db::getInstance()->insert('parcelshop', [
                    'order_id' => pSQL($order->id),
                    'parcelshop_id' => pSQL($params['cookie']->parcelId)
                ]);
                unset($this->context->cookie->parcelId) ;
            }
        }
    }

    public function hookDisplayFooter($params)
    {
        $dpdPublicToken = new \DpdConnect\Sdk\Resources\Token(
            new \DpdConnect\Sdk\Common\HttpClient((string)Configuration::get("dpdconnect_connect_url"))
        );
        $dpdPublicToken = $dpdPublicToken->getPublicJWTToken(
            (string)Configuration::get("dpdconnect_connect_username"),
            \DpdConnect\classes\DpdEncryptionManager::decrypt((string)Configuration::get("dpdconnect_connect_password"))
        );

        $tempAddress = new Address($params['cart']->id_address_delivery);
        $shippingAddress = $tempAddress->address1 . ' ' . $tempAddress->postcode . ' ' . $tempAddress->country;

        $useDpdGmapsKey = Configuration::get('use_gmaps_key') == 1;
        $gmapsKey = '';
        if (!$useDpdGmapsKey) {
            $gmapsKey = Configuration::get('gmaps_key');
        }

        $this->context->smarty->assign([
            'parcelshopId' => $this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_parcelshop")),
            'sender' => $params['cart']->id_carrier,
            'shippingAddress' => $shippingAddress,
            'dpdPublicToken' => $dpdPublicToken,
            'shopCountryCode' => $this->context->language->iso_code,
            'gmapsKey' => $gmapsKey,
            'saturdaySenderIsAllowed' => (int)$this->dpdCarrier->checkIfSaturdayAllowed(),
            'saturdaySender' => (int)$this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_saturday")),
            'classicSaturdaySender' => (int)$this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get("dpdconnect_classic_saturday")),
            'cookieParcelId' => $this->context->cookie->parcelId,
        ]);
        return $this->display(__FILE__, '_dpdVariables.tpl');
    }

    public function dpdCarrier()
    {
        return new \DpdConnect\classes\DpdCarrier();
    }

    public function dpdLabelGenerator()
    {
        return new \DpdConnect\classes\DpdLabelGenerator();
    }

    public function dpdParcelPredict()
    {
        return $this->dpdParcelPredict;
    }

    public function dpdShippingList()
    {
        return new \DpdConnect\classes\DpdShippingList();
    }

    // Prestashop will only add .php files to the /override folder.
	// This method manually adds our templates to the /override folder.
	private function addOverrideTemplates() {
        $success = true;
        foreach ($this->dpdCustomTemplates as $template) {
            $destination = _PS_ROOT_DIR_ . '/' . $template;
            $customTemplate = $this->local_path . $template;

            if (!Tools::file_exists_cache($destination) && Tools::file_exists_cache($customTemplate)) {
                $templateDirectory = dirname($destination);
                if (!Tools::file_exists_no_cache($templateDirectory)) {
                    $success = $success && @mkdir($templateDirectory, 0755, true);
                }
                $success = $success && @copy($customTemplate, $destination);

                do {
                    if (Tools::file_exists_cache(_PS_ROOT_DIR_.'/config/index.php') && !Tools::file_exists_cache($templateDirectory.'/index.php')) {
                        $success = $success && @copy(_PS_ROOT_DIR_.'/config/index.php', $templateDirectory.'/index.php');
                    }
                    $templateDirectory = dirname($templateDirectory);

                } while (strlen(_PS_ROOT_DIR_) < strlen($templateDirectory));
            }
        }

        return $success;
	}

	// Remove manually added template files from /override
	private function removeOverrideTemplates() {
        foreach ($this->dpdCustomTemplates as $template) {
            $destination = _PS_ROOT_DIR_ . '/' . $template;

            if (file_exists($destination)) {
                unlink($destination);
            }
        }

        return true;
	}
}
