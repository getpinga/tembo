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

try
{
    $epp = connectEpp('generic');

    $params = array(
        'msgID' => '12345'
    );
    $pollAck = $epp->pollAck($params);
    
    if (isset($pollAck['error'])) {
    echo 'Error: ' . $pollAck['error'] . PHP_EOL;
    } else {
    echo 'Poll Ack Result: ' . $pollAck['code'] . ': ' . $pollAck['msg'] . PHP_EOL;
    }
    
    $logout = $epp->logout();
    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    return "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    return "Error : ".$e->getMessage() . PHP_EOL;
}