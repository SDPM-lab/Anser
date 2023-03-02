<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

$routes->group(
    'api/v1',
    [
        'namespace' => 'App\Controllers\V1'
    ],
    function (\CodeIgniter\Router\RouteCollection $routes) {
        //USER APIs
        $routes->resource("user", [
            'controller' => 'User',
            'only' => ['index','show','create'],
        ]);
        //ORDER APIs
        $routes->resource("order", [
            'controller' => 'Order',
            'only' => ['show', 'create', 'delete'],
        ]);
        //PAYMENT APIs
        $routes->resource("payment", [
            'controller' => 'Payment',
            'only' => ['show','create', 'delete'],
        ]);
        //Fail APIs
        $routes->get('fail', 'Fail::awayls429');
        $routes->get('fail/(:num)', 'Fail::awayls500/$1');

        $routes->get('redis/index', 'Redis::index');
        $routes->get('redis/testSerialize', 'Redis::testCacheSerialize');
        $routes->get('serialize', 'Serialize::testJsonSerializer');

        $routes->get('userOrder', 'UserOrder::userOrder');

        $routes->get('userOrderRestart', 'UserOrderRestarter::restartUserOrchestrator');
    }
);

$routes->group(
    'api/v2',
    [
        'namespace' => 'App\Controllers\v2'
    ],
    function (\CodeIgniter\Router\RouteCollection $routes) {
        //PRODUCT APIs
        $routes->resource("product", [
            'controller' => 'ProductController',
            'only' => ['index', 'show', 'create', 'update', 'delete'],
        ]);

        //ORDER APIs
        $routes->resource("order", [
            'controller' => 'OrderController',
            'only' => ['index', 'show', 'create', 'update', 'delete'],
            'filter' => 'user'
        ]);
        //PAYMENT APIs
        $routes->resource("payment", [
            'controller' => 'PaymentController',
            'only' => ['index', 'show', 'create', 'update', 'delete'],
            'filter' => 'user'
        ]);

        $routes->delete("order", "OrderController::delete", ["filter" => "user"]);
        $routes->delete("payment", "PaymentController::delete", ["filter" => "user"]);

        //WALLET APIs
        $routes->get('wallet', 'WalletController::show', ['filter' => 'user']);
        $routes->post('wallet/increaseWalletBalance', 'WalletController::increaseWalletBalance', ['filter' => 'user']);
        $routes->post('wallet/reduceWalletBalance', 'WalletController::reduceWalletBalance', ['filter' => 'user']);

        //PRODUCT AMOUNT API
        $routes->post('inventory/addInventory', 'ProductController::addInventory');
        $routes->post('inventory/reduceInventory', 'ProductController::reduceInventory');

        $routes->get('createOrder', 'CreateOrder::createOrder');

        $routes->get(
            'restartCreateOrderOrchestratorByServerName',
            'CreateOrderRestarter::restartCreateOrderOrchestratorByServerName'
        );
        $routes->get(
            'restartCreateOrderOrchestratorByServerCluster',
            'CreateOrderRestarter::restartCreateOrderOrchestratorByServerCluster'
        );
        $routes->get(
            'restartCreateOrderOrchestratorByClassName',
            'CreateOrderRestarter::restartCreateOrderOrchestratorByClassName'
        );
        $routes->get(
            'restartCreateOrderOrchestratorByServerNameAndNeedRestart',
            'CreateOrderRestarter::restartCreateOrderOrchestratorByServerNameAndNeedRestart'
        );
        $routes->get(
            'restartCreateOrderOrchestratorByClassNameAndNeedRestart',
            'CreateOrderRestarter::restartCreateOrderOrchestratorByClassNameAndNeedRestart'
        );

        //HISTORY
        $routes->post('history/getInventoryHistory', 'HistoryController::getInventoryHistory');
        $routes->post('history/getOrderHistory', 'HistoryController::getOrderHistory');
        $routes->post('history/getPaymentHistory', 'HistoryController::getPaymentHistory');
        $routes->post('history/getWalletHistory', 'HistoryController::getWalletHistory');
    }
);

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
