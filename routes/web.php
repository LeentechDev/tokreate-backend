<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->get('profile', 'UserController@profile');
    $router->get('users/{user_id}', 'UserController@singleUser');
    $router->get('users', 'UserController@allUsers');
    $router->post('update-account', 'UserController@updateAccount');
});

$router->group(['prefix' => 'api/token'], function () use ($router) {
    $router->post('request-minting', 'TokenController@mintRequest');
    $router->post('portfolio', 'TokenController@portfolio');
    $router->post('collection', 'TokenController@collection');
    $router->post('update-status', 'TokenController@updateTokenStatus');
    $router->post('minting-list', 'TokenController@mintingList');
    $router->get('specific-token/{token_id}', 'TokenController@specificToken');
});

$router->group(['prefix' => 'api/home'], function () use ($router) {
    $router->post('browse-token', 'HomeController@browseToken');
    $router->get('token', 'HomeController@homeToken');
    $router->get('specific-token/{token_id}', 'HomeController@specificToken');
});

$router->group(['prefix' => 'api/wallet'], function () use ($router) {
    $router->post('connect-wallet', 'WalletController@connectWallet');
    $router->post('create-wallet', 'WalletController@createWallet');
    $router->post('request-wallet', 'WalletController@requestWallet');
    $router->post('decrypt-seed-phrase', 'WalletController@decryptSeedPhrase');
    $router->post('wallet_list', 'WalletController@walletList');
});
