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
        'authInfo' => 'P@ssword123!'
    );
    $domainUpdateAuthinfo = $epp->domainUpdateAuthinfo($params);
    
    if (array_key_exists('error', $domainUpdateAuthinfo))
    {
        echo 'DomainUpdateAuthinfo Error: ' . $domainUpdateAuthinfo['error'] . PHP_EOL;
    }
    else
    {
        echo "DomainUpdateAuthinfo result: " . $domainUpdateAuthinfo['code'] . ": " . $domainUpdateAuthinfo['msg'] . PHP_EOL;
    }
    
    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}