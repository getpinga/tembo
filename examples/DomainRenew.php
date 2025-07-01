<?php
/**
 * Tembo EPP client test file
 *
 * Written in 2023-2025 by Taras Kondratyuk (https://namingo.org)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Connection.php';

try
{
    $epp = connectEpp('generic');

    $params = array(
        'domainname' => 'test.example',
        'regperiod' => 1
    );
    $domainRenew = $epp->domainRenew($params);
    
    if (array_key_exists('error', $domainRenew))
    {
        echo 'DomainRenew Error: ' . $domainRenew['error'] . PHP_EOL;
    }
    else
    {
        echo "DomainRenew result: " . $domainRenew['code'] . ": " . $domainRenew['msg'] . PHP_EOL;
        echo 'Domain Name: ' . $domainRenew['name'] . PHP_EOL;
        echo 'New Expiration Date: ' . $domainRenew['exDate'] . PHP_EOL;
    }
    
    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}