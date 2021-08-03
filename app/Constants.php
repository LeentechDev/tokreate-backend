<?php

namespace App;

class Constants {
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
    const WALLET_SETUP = 1;
    const WALLET_DONE = 2;
}