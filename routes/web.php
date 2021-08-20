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
$router->get('/mail', 'MailController@mail');
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('admin/login', 'AuthController@admin_login');
    $router->get('profile', 'UserController@profile');
    $router->get('users/{user_id}', 'UserController@singleUser');
    $router->get('users', 'UserController@allUsers');
    $router->post('update-account', 'UserController@updateAccount');
    $router->get('user/tokens', 'UserController@getUserTokens');
    $router->get('tokens', 'HomeController@getTokens');
    $router->get('public/token/specific-token/{token_id}', 'HomeController@specificToken');
    $router->post('user/change-password', 'UserController@changePassword');
    $router->put('user/notification-settings', 'UserController@changeNotifSettings');
    $router->post('reset-password', 'AuthController@resetPassword');
    $router->post('change-password', 'AuthController@changePassword');
    $router->get('validate-token', 'AuthController@validateTokenRP');
});

$router->group(['prefix' => 'api/token'], function () use ($router) {
    $router->post('request-minting', 'TokenController@mintRequest');
    $router->post('portfolio', 'TokenController@portfolio');
    $router->post('collection', 'TokenController@collection');
    $router->post('update-status', 'TokenController@updateTokenStatus');
    $router->get('minting-list', 'TokenController@mintingList');
    $router->get('specific-token/{token_id}', 'TokenController@specificToken');
    $router->post('add-to-market', 'TokenController@addToMarket');
    $router->get('management-list', 'TokenController@userManagementList');

    
});

$router->group(['prefix' => 'api/wallet'], function () use ($router) {
    $router->post('connect-wallet', 'WalletController@connectWallet');
    $router->post('create-wallet', 'WalletController@createWallet');
    $router->post('request-wallet', 'WalletController@requestWallet');
    $router->post('decrypt-seed-phrase', 'WalletController@decryptSeedPhrase');
    $router->get('wallet_list', 'WalletController@walletList');
    $router->get('specific-wallet/{wallet_id}', 'WalletController@specificWallet');
});


$router->group(['prefix' => 'api/cms'], function () use ($router) {
    $router->post('add-faqs', 'FaqsController@addFaqs');
    $router->post('update-faqs', 'FaqsController@updateFaqs');
    $router->get('specific-faqs/{id}', 'FaqsController@specificFaqs');
    $router->get('faqs_list', 'FaqsController@faqsList');
    $router->get('terms-and-conditions', 'TermsandConditionController@viewTermsandConditions');
    $router->post('update-terms-and-conditions', 'TermsandConditionController@updateTermsandConditions');
    $router->get('data-policy', 'DataPolicyController@viewDataPolicy');
    $router->post('update-data-policy', 'DataPolicyController@updateDataPolicy');
    $router->post('update-gas-fee', 'GasFeeController@updateGasFee');
    $router->get('gas-fee', 'GasFeeController@viewGasfee');
});


$router->group(['prefix' => 'api/notification'], function () use ($router) {
    $router->get('read', 'NotificationController@read');
    $router->get('list', 'NotificationController@list');
});
