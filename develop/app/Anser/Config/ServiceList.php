<?php

namespace App\Anser\Config;

use SDPMlab\Anser\Service\ServiceList;

ServiceList::addLocalService("order_service","localhost",8080,false);
ServiceList::addLocalService("product_service","localhost",8081,false);
ServiceList::addLocalService("cart_service","localhost",8082,false);
ServiceList::addLocalService("payment_service","localhost",8083,false);