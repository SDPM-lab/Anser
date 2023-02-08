<?php

namespace App\Anser;

use SDPMlab\Anser\Service\ServiceList;

ServiceList::addLocalService("order_service", "order_service", 80, false);
ServiceList::addLocalService("product_service", "product_service", 80, false);
ServiceList::addLocalService("payment_service", "payment_service", 80, false);