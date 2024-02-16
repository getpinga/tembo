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
         //rem or //addrem
        'command' => 'add',
        'keyTag_1' => '33409',
        'alg_1' => '8',
        'digestType_1' => '1',
        'digest_1' => 'F4D6E26B3483C3D7B3EE17799B0570497FAF33BCB12B9B9CE573DDB491E16948'
    );
    $domainUpdateDNSSEC = $epp->domainUpdateDNSSEC($params);
    
    if (array_key_exists('error', $domainUpdateDNSSEC))
    {
        echo 'DomainUpdateDNSSEC Error: ' . $domainUpdateDNSSEC['error'] . PHP_EOL;
    }
    else
    {
        echo "DomainUpdateDNSSEC result: " . $domainUpdateDNSSEC['code'] . ": " . $domainUpdateDNSSEC['msg'] . PHP_EOL;
    }
    
    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    return "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    return "Error : ".$e->getMessage() . PHP_EOL;
}