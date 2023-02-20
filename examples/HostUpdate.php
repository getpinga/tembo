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
        'hostname' => 'ns1.example.com',
        'currentipaddress' => '4.4.4.4',
        'newipaddress' => '8.8.8.8'
    );
    $hostUpdate = $epp->hostUpdate($params);

    if (array_key_exists('error', $hostUpdate))
    {
        echo 'HostUpdate Error: ' . $hostUpdate['error'] . PHP_EOL;
    }
    else
    {
        echo 'HostUpdate Result: ' . $hostUpdate['code'] . ': ' . $hostUpdate['msg'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>
