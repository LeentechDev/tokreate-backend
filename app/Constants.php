<?php

namespace App;

class Constants {
    const USER_ADMIN = 1;
    const USER_ARTIST = 2;
    const USER_COLLECTOR = 3;

    const USER_STATUS_INACTIVE = 0;
    const USER_STATUS_ACTIVE = 1;

    const PENDING = 0;
    const PROCESSING = 1;
    const FAILED = 2;
    const READY = 3;
    const COLLECTION = 4;

    const TOKEN_ON_MARKET = 1;

    const TRANSACTION_MINTING = 1;
    const TRANSACTION_TRANNSFER = 2;
    const TRANSACTION_SETUP_WALLET = 3;

    const WALLET_REQUEST = 0;
    const WALLET_DONE = 1;

    const NOTIF_WALLET_RES = 1;
    const NOTIF_MINTING_RES = 2;
    const NOTIF_WALLET_REQ = 3;
    const NOTIF_MINTING_REQ = 4;

    const TRANSACTION_SUCCESS = 1;
    const TRANSACTION_FAILED = 0;
}