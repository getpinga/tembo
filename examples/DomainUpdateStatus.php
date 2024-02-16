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
        'domainname' => 'test.example',
        'command' => 'add',
        'status' => 'clientTransferProhibited'
    );
    $domainUpdateStatus = $epp->domainUpdateStatus($params);
    
    if (array_key_exists('error', $domainUpdateStatus))
    {
        echo 'DomainUpdateStatus Error: ' . $domainUpdateStatus['error'] . PHP_EOL;
    }
    else
    {
        echo "DomainUpdateStatus result: " . $domainUpdateStatus['code'] . ": " . $domainUpdateStatus['msg'] . PHP_EOL;
    }
    
    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    return "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    return "Error : ".$e->getMessage() . PHP_EOL;
}