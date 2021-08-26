<?php

namespace App;

class Constants {
    const USER_ADMIN = 1;
    const USER_ARTIST = 2;
    const USER_COLLECTOR = 3;

    const USER_STATUS_INACTIVE = 0;
    const USER_STATUS_ACTIVE = 1;

    const TOKEN_ON_MARKET = 1;

    const WALLET_REQUEST = 0;
    const WALLET_DONE = 1;

    const NOTIF_WALLET_RES = 1;
    const NOTIF_MINTING_RES = 2;
    const NOTIF_WALLET_REQ = 3;
    const NOTIF_MINTING_REQ = 4;

    const PENDING = 0;
    const PROCESSING = 1;
    const FAILED = 2;
    const READY = 3;
    const COLLECTION = 4;


    const TRANSACTION_MINTING = 1;
    const TRANSACTION_TRANSFER = 2;
    const TRANSACTION_SETUP_WALLET = 3;

    const TRANSACTION_PENDING = 0;
    const TRANSACTION_PROCESSING = 1;
    const TRANSACTION_FAILED = 2;
    const TRANSACTION_SUCCESS = 3;

    const TRANSACTION_PAYMENT_PENDING = 0;
    const TRANSACTION_PAYMENT_SUCCESS = 1;
    const TRANSACTION_PAYMENT_FAILED = 2;
    const TRANSACTION_PAYMENT_CANCEL = 3;

    const TOKEN_HISTORY_MINT = 1;
    const TOKEN_HISTORY_BUY = 2;
    const TOKEN_HISTORY_SALE = 3;
}