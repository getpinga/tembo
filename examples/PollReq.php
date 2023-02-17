<?php
/**
 * Tembo EPP client test file
 *
 * Written in 2023 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

// Include the Composer autoloader
require_once '../vendor/autoload.php';
require_once 'Connection.php';

// Use the Epp class from your package
use Pinga\Tembo\Epp;
use Pinga\Tembo\EppClient;
use Pinga\Tembo\HttpsClient;

try
{
    $epp = connectEpp();

    $pollReq = $epp->pollReq();
    echo 'Messages in Poll: ' . $pollReq['messages'] . PHP_EOL;
    echo 'Last message ID: ' . $pollReq['last_id'] . PHP_EOL;
    echo 'Last message date: ' . $pollReq['qDate'] . PHP_EOL;
    echo 'Last message content: ' . $pollReq['last_msg'] . PHP_EOL;
	
    $logout = $epp->logout();
    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}