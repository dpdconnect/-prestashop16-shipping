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

CREATE TABLE IF NOT EXISTS `_PREFIX_dpdshipment_label` (
  `id_dpdcarrier_label` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mps_id` varchar(255) NOT NULL,
  `label_nummer` text NOT NULL,
  `order_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `shipped` tinyint(4) NOT NULL,
  `label` mediumblob NOT NULL,
  `retour` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_dpdcarrier_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `_PREFIX_parcelshop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `parcelshop_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `_PREFIX_parcelshop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `parcelshop_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `_PREFIX_dpd_product_attributes` (
  `id_dpd_product_attributes` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `hs_code` varchar(255) NOT NULL,
  `country_of_origin` varchar(255) NOT NULL,
  `customs_value` int NOT NULL,
  PRIMARY KEY (`id_dpd_product_attributes`),
  UNIQUE (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `_PREFIX_product`
    ADD `dpd_shipping_product` VARCHAR(255) NOT NULL DEFAULT 'default',
    ADD `dpd_carrier_description` TEXT NULL DEFAULT NULL;
