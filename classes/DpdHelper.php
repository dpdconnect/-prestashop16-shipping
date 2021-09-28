<?php

namespace DpdConnect\classes;

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

use Db;
use Tab;
use Tools;
use Language;
use HelperForm;
use Configuration;
use AdminController;

class DpdHelper
{
    const MODULENAME = 'dpdconnect';

    public function displayConfigurationForm($module, $formAccountSettings, $formAdress, $productSettings, $advancedSettings, $submit)
    {
        // Get the default language id of the shop
        $default_lang_id = (int)Configuration::get('PS_LANG_DEFAULT');

        // Set al the fields of the form
        $fields_form[0]['form'] = $formAccountSettings;
        $fields_form[1]['form'] = $formAdress;
        $fields_form[2]['form'] = $productSettings;
        $fields_form[3]['form'] = $advancedSettings;
        $fields_form[4]['form'] = $submit;

        $helperForm = new HelperForm();
        $helperForm->module = $module;
        $helperForm->name_controller = $module->name;
        $helperForm->token = Tools::getAdminTokenLite('AdminModules');
        $helperForm->currentIndex = AdminController::$currentIndex.'&configure='.$module->name;

        // Language
        $helperForm->default_form_language = $default_lang_id;
        $helperForm->allow_employee_form_lang = $default_lang_id;

        // Title
        $helperForm->title = $module->displayName;
        $helperForm->show_toolbar = true;
        $helperForm->toolbar_scroll = true;
        $helperForm->submit_action = 'submit'.$module->name;

        // Load current value
        $helperForm->fields_value['dpdconnect_username'] = Configuration::get('dpdconnect_connect_username') ?: Tools::getValue('dpdconnect_username');
        $helperForm->fields_value['dpdconnect_password'] = Configuration::get('dpdconnect_connect_password') ?: Tools::getValue('dpdconnect_password');
        $helperForm->fields_value['dpdconnect_depot'] = Configuration::get('dpdconnect_depot') ?: Tools::getValue('dpdconnect_depot');
        $helperForm->fields_value['company'] = Configuration::get('dpdconnect_company') ?: Tools::getValue('company');
        $helperForm->fields_value['street'] = Configuration::get('dpdconnect_street') ?: Tools::getValue('street');
        $helperForm->fields_value['postalcode'] = Configuration::get('dpdconnect_postalcode') ?: Tools::getValue('postalcode');
        $helperForm->fields_value['place'] = Configuration::get('dpdconnect_place') ?: Tools::getValue('place');
        $helperForm->fields_value['country'] = Configuration::get('dpdconnect_country') ?: Tools::getValue('country');
        $helperForm->fields_value['email'] = Configuration::get('dpdconnect_email') ?: Tools::getValue('email');
        $helperForm->fields_value['vatnumber'] = Configuration::get('dpdconnect_vatnumber') ?: Tools::getValue('vatnumber');
        $helperForm->fields_value['eorinumber'] = Configuration::get('dpdconnect_eorinumber') ?: Tools::getValue('eorinumber');
        $helperForm->fields_value['spr'] = Configuration::get('dpdconnect_spr') ?: Tools::getValue('spr');
        $helperForm->fields_value['gmaps_key'] =  Configuration::get('gmaps_key') ?: Tools::getValue('gmaps_key');
        $helperForm->fields_value['use_gmaps_key'] =  Configuration::get('use_gmaps_key') ?: Tools::getValue('use_gmaps_key');
        $helperForm->fields_value['default_product_hcs'] = Configuration::get('dpdconnect_default_product_hcs') ?: Tools::getValue('default_product_hcs');
        $helperForm->fields_value['default_product_weight'] = Configuration::get('dpdconnect_default_product_weight') ?: Tools::getValue('default_product_weight');
        $helperForm->fields_value['default_product_country_of_origin'] = Configuration::get('dpdconnect_default_product_country_of_origin') ?: Tools::getValue('default_product_country_of_origin');
        $helperForm->fields_value['country_of_origin_feature'] = Configuration::get('dpdconnect_country_of_origin_feature') ?: Tools::getValue('country_of_origin_feature');
        $helperForm->fields_value['customs_value_feature'] = Configuration::get('dpdconnect_customs_value_feature') ?: Tools::getValue('customs_value_feature');
        $helperForm->fields_value['hs_code_feature'] = Configuration::get('dpdconnect_hs_code_feature') ?: Tools::getValue('hs_code_feature');
        $helperForm->fields_value['hs_code_feature'] = Configuration::get('dpdconnect_hs_code_feature') ?: Tools::getValue('hs_code_feature');
        $helperForm->fields_value['dpdconnect_url'] = Configuration::get('dpdconnect_connect_url') ?: Tools::getValue('dpdconnect_url');

        return $helperForm->generateForm($fields_form);
    }

    public function checkIfExtensionIsLoaded($extension = 'soap')
    {
        return(extension_loaded($extension));
    }

    public function installDB()
    {
            $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'install.sql';
            $query = file_get_contents($file);
            $query = preg_replace('/_PREFIX_/', _DB_PREFIX_, $query);

            return Db::getInstance()->execute($query);
    }

    public function installControllers($controllerNames)
    {
        foreach ($controllerNames as $controllerName => $userReadableName) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = $controllerName;
            // Hide the tab from the menu.
            $tab->id_parent = -1;
            $tab->module = self::MODULENAME;
            $tab->name = [];

            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $userReadableName;
            }
            $tab->add();
        }
        return true;
    }
}
