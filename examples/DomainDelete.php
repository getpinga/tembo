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
        'domainname' => 'test.example'
    );
    $domainDelete = $epp->domainDelete($params);
	
    if (array_key_exists('error', $domainDelete))
    {
        echo 'DomainDelete Error: ' . $domainDelete['error'] . PHP_EOL;
    }
    else
    {
        echo "DomainDelete result: " . $domainDelete['code'] . ": " . $domainDelete['msg'] . PHP_EOL;
    }
	
    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>