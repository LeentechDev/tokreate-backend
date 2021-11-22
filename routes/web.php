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

$router->post('/dragonpay-webhook', 'PostbackController@webhook');
$router->get('/dragonpay-webhook-payout', 'PostbackController@webhookPayout');

// $router->post('/payoutTest', 'PostbackController@payoutTest');

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');
    $router->post('admin/login', 'AuthController@admin_login');
    $router->post('reset-password', 'AuthController@resetPassword');
    $router->post('change-password', 'AuthController@changePassword');
    $router->get('validate-token', 'AuthController@validateTokenRP');

    $router->post('payment', 'DragonpayController@payment');;
    // $router->get('dragonpay-webhook', 'DragonpayController@webhook');

    $router->get('tokens', 'HomeController@getTokens');
    $router->get('public/token/specific-token', 'HomeController@specificToken');
    $router->get('gas-fee', 'HomeController@getGasFees');
    $router->get('site-settings', 'HomeController@siteSettings');
    $router->post('update-site-settings', 'HomeController@updateSiteSettings');
});


$router->group(['prefix' => 'api/user'], function () use ($router) {
    $router->get('profile', 'UserController@profile');
    $router->get('tokens', 'UserController@getUserTokens')->middleware('\App\Http\Middleware\Authenticate::class');
    $router->get('specific-token', 'UserController@specificToken');
    $router->post('change-password', 'UserController@changePassword');
    $router->put('update-web-notif', 'UserController@changeNotifSettings');
    $router->put('update-mail-notif', 'UserController@changeEmailNotifSettings');
    $router->post('update-account', 'UserController@updateAccount');
    $router->post('update-payout', 'UserController@updatePayout');
    $router->get('copy_link_profile/{id}', 'UserController@copyLinkArtistProfile');
    $router->get('tokens_artist', 'UserController@getUserTokensID');
});


$router->group(['prefix' => 'api/users'], function () use ($router) {
    $router->get('minting-list/{user_id}', 'UserController@getUserSpecificMintingList');
    $router->get('token-list/{user_id}', 'UserController@getReadyTokens');
    $router->get('token/{token_id}', 'UserController@viewSpecificPortfolio');
    $router->post('deactivate', 'UserController@deactivateUser');
    $router->post('activate', 'UserController@activateUser');
    $router->get('', 'UserController@userManagementList');
    $router->get('{user_id}', 'UserController@viewUserProfile');
});


$router->group(['prefix' => 'api/token'], function () use ($router) {
    $router->post('request-minting', 'TokenController@mintRequest');
    $router->post('portfolio', 'TokenController@portfolio');
    $router->post('collection', 'TokenController@collection');
    $router->post('update-status', 'TokenController@updateTokenStatus');
    $router->get('minting-list', 'TokenController@mintingList');
    $router->get('specific-token/{token_id}', 'TokenController@specificToken');
    $router->post('add-to-market', 'TokenController@addToMarket');
    $router->get('history', 'TokenController@getTokenHistory');
    $router->get('download/{id}', 'TokenController@downloadToken');
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

$router->group(['prefix' => 'api/transactions'], function () use ($router) {
    $router->get('transfer-ownership', 'TransactionController@transferOwnership');
    $router->post('update-status', 'TransactionController@updateTransactionStatus');
    $router->get('withdrawal-list', 'TransactionController@withdrawalList');
    $router->get('purchase-list', 'TransactionController@purchaseList');
    $router->get('get-total-earning', 'WithdrawalController@getTotalEarnings');
    $router->get('get-commission-list', 'WithdrawalController@getCommissionList');
    $router->get('{id}', 'TransactionController@transactionDetails');
    $router->get('', 'TransactionController@transactionList');
    $router->post('request-transfer-ownership', 'TransactionController@requestTransferOwnership');
});


$router->group(['prefix' => 'api/reports'], function () use ($router) {
    $router->get('dashboard', 'DashboardController@dashboardReports');
    $router->get('pending-transaction', 'DashboardController@pendingTransactions');
    $router->get('success-transaction', 'DashboardController@successTransactions');
    $router->get('user-sales', 'DashboardController@userSales');
    $router->get('user-purchase', 'DashboardController@userPurchase');
    $router->get('user-incoming', 'DashboardController@userIncoming');
    $router->get('user-outgoing', 'DashboardController@userOutgoing');
    $router->get('user-royalties', 'DashboardController@userRoyalties');
    $router->get('user-dashboard', 'DashboardController@userDashboardReports');
    $router->get('total-earnings', 'DashboardController@getTotalEarnings');
    $router->get('earning-list', 'DashboardController@getCommissionList');
});

$router->group(['prefix' => 'api/withdrawals'], function () use ($router) {
    $router->post('request', 'WithdrawalController@requestWithdrawal');
    $router->get('update-wthdrawal-status', 'WithdrawalController@updateWithdrawalStatus');
    $router->get('user', 'WithdrawalController@getUserWithdrawals');
    $router->get('', 'WithdrawalController@getWithdrawals');
});
