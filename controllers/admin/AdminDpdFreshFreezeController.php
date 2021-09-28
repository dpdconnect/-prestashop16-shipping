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

use DpdConnect\classes\DpdLabelGenerator;
use DpdConnect\classes\FreshFreezeHelper;

require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

class AdminDpdFreshFreezeController extends ModuleAdminController
{
    public $orderIds;

    public function __construct()
    {
        parent::__construct();

        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->orderIds = Tools::getValue('ids_order');
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addJqueryUI('ui.datepicker');
    }

    public function initContent()
    {
        parent::initContent();

        $bundledOrders = FreshFreezeHelper::bundleOrders($this->orderIds);

        $orderProducts = [];
        foreach ($this->orderIds as $orderId) {
            // Continue if label(s) already exist, so that no fresh/freeze data has to be entered for this order
            if (DpdLabelGenerator::getLabelOutOfDb($orderId)) {
                continue;
            }

            /** @var OrderCore $order */
            $order = new Order($orderId);

            // Collect all fresh and freeze order products in one array for easier handling
            foreach ($order->getProducts() as $orderProduct) {
                $orderProducts[$order->id] = [];

                if (isset($bundledOrders[$order->id][FreshFreezeHelper::TYPE_FRESH])) {
                    $orderProducts[$order->id] = array_merge($orderProducts[$order->id], $bundledOrders[$order->id][FreshFreezeHelper::TYPE_FRESH]);
                }
                if (isset($bundledOrders[$order->id][FreshFreezeHelper::TYPE_FREEZE])) {
                    $orderProducts[$order->id] = array_merge($orderProducts[$order->id], $bundledOrders[$order->id][FreshFreezeHelper::TYPE_FREEZE]);
                }

                if (empty($orderProducts[$order->id])) {
                    unset($orderProducts[$order->id]);
                }
            }
        }

        $redirectUrl = $this->context->link->getAdminLink('AdminDpdLabels') . '&parcel=' . Tools::getValue('parcel');
        foreach ($this->orderIds as $orderId) {
            $redirectUrl = $redirectUrl . '&ids_order[]=' . $orderId;
        }

        // Assign imageurl to every product
        foreach ($orderProducts as $orderId => $products) {
            foreach ($products as $index => $product) {
                $id_image = Product::getCover((int)$orderProducts[$orderId][$index]['id_product']);
                if ($id_image) {
                    $image = new Image($id_image['id_image']);
                    $image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().".jpg";
                } else {
                    $image_url = _PS_BASE_URL_._THEME_PROD_DIR_.$this->context->language->iso_code . '-default-large_default.jpg';
                }

                $orderProducts[$orderId][$index]['image_url'] = $image_url;
            }
        }

        $this->context->smarty->assign(array(
            'weight_unit' => Configuration::get('PS_WEIGHT_UNIT'),
            'defaultDate' => FreshFreezeHelper::getDefaultDate(),
            'orderProducts' => $orderProducts,
            'redirectUrl' => $redirectUrl
        ));
        $this->setTemplate('form.tpl');
    }
}
