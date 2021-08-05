<?php

namespace App;

class Constants {
    const USER_ADMIN = 1;
    const USER_ARTIST = 2;
    const USER_COLLECTOR = 3;

    const PENDING = 0;
    const PROCESSING = 1;
    const FAILED = 2;
    const FORSALE = 3;
    const COLLECTION = 4;

    const TOKEN_ON_MARKET = 1;

    const TRANSACTION_MINTING = 1;
    const TRANSACTION_TRANNSFER = 2;
    const TRANSACTION_SETUP_WALLET = 3;

    const WALLET_REQUEST = 0;
    const WALLET_DONE = 1;
}