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

require_once(_PS_MODULE_DIR_ . 'dpdconnect' . DIRECTORY_SEPARATOR . 'dpdconnect.php');

use DpdConnect\classes\DpdLabelGenerator;
use DpdConnect\classes\FreshFreezeHelper;

class AdminDpdLabelsController extends ModuleAdminController
{
    public $dpdClient;
    public $dpdParcelPredict;
    public $dpdLabelGenerator;
    public $delete;
    public $return;
    public $orderIds;
    public $hasFreshFreezeData;

    public function __construct()
    {
        parent::__construct();
        $dpdconnect = new dpdconnect();
        $this->dpdLabelGenerator = $dpdconnect->dpdLabelGenerator();
        $this->orderIds = Tools::getValue('ids_order');
        $this->delete = Tools::getValue('delete');
        $this->parcels = Tools::getValue('parcel');
        $this->hasFreshFreezeData = Tools::getIsset('hasFreshFreezeData');
        $this->return = (boolean)Tools::getValue('return');
    }

    public function checkErrors()
    {
        if (!empty($this->dpdLabelGenerator->errors)) {
            foreach ($this->dpdLabelGenerator->errors as $errorMessage) {
                $this->errors[] = $this->l($errorMessage);
            }
        }
    }

    public function initContent()
    {
        parent::initContent();

        if ($this->delete) {
            if ($this->dpdLabelGenerator->deleteLabelFromDb($this->orderIds, $this->return)) {
                $this->confirmations[]  = $this->l('Label is deleted successfully');
            }
        } else {
            $freshFreezeData = [];

            // Redirect to Fresh/Freeze page if order(s) contain Fresh or Freeze products
            if (!$this->return && FreshFreezeHelper::ordersContainFreshFreezeProducts($this->orderIds)) {
                $labelsExist = true;
                // Prevent entering fresh/freeze data if labels are created already
                foreach ($this->orderIds as $orderId) {
                    if (
                        FreshFreezeHelper::ordersContainFreshFreezeProducts([$orderId]) &&
                        !DpdLabelGenerator::getLabelOutOfDb($orderId, $this->return)
                    ) {
                        $labelsExist = false;
                        break;
                    }
                }

                if (!$this->hasFreshFreezeData && !$labelsExist) {
                    $link = new LinkCore;
                    $urlFreshFreeze = $link->getAdminLink('AdminDpdFreshFreeze');
                    $urlFreshFreeze = $urlFreshFreeze . '&' . http_build_query(['ids_order' => $this->orderIds, 'parcel' => $this->parcels]);

                    Tools::redirectAdmin($urlFreshFreeze);
                }

                // Collect fresh/freeze data from submitted POST
                $freshFreezeData = FreshFreezeHelper::collectFreshFreezeData();
            }

            if ($this->parcels == 0) {
                $this->parcels = 1;
            }
            $this->dpdLabelGenerator->generateLabel($this->orderIds, $this->parcels, $this->return, $freshFreezeData);
        }
        $this->checkErrors();
    }
}
