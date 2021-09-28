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

require_once (_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

class OrderController extends OrderControllerCore
{
    public $dpdCarrier;

    public function __construct()
    {
        parent::__construct();
        $dpdconnect = new dpdconnect();
        $this->dpdCarrier = $dpdconnect->dpdCarrier();
    }

    public function _assignWrappingAndTOS()
    {
        parent::_assignWrappingAndTOS();
        $deliveryOptionList = $this->context->cart->getDeliveryOptionList();

        $saturdayCarrierId = $this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get('dpdconnect_saturday'));
        $classicSaturdayCarrierId = $this->dpdCarrier->getLatestCarrierByReferenceId(Configuration::get('dpdconnect_classic_saturday'));

        foreach ($deliveryOptionList as &$carriers) {
            if (!$this->dpdCarrier->checkIfSaturdayAllowed()) {
                unset($carriers[$saturdayCarrierId . ',']);
                unset($carriers[$classicSaturdayCarrierId . ',']);
            }
        }

        $this->context->smarty->assign([
            'delivery_option_list' => $deliveryOptionList,
        ]);
    }


    public function setMedia()
    {
        parent::setMedia();
        $this->addJS(Configuration::get('dpdconnect_connect_url') . '/parcelshop/map/js');
//        $this->addJS('https://maps.googleapis.com/maps/api/js?key=' . Configuration::get('gmaps_client_key'));
        $this->addJS(_PS_MODULE_DIR_ . 'dpdconnect' . DS . 'views' . DS  . 'js' . DS . 'dpdLocator.js');
        $this->addCSS(_PS_MODULE_DIR_ . 'dpdconnect' . DS . 'views' . DS  . 'css' . DS . 'dpdLocator.css');
    }
}
