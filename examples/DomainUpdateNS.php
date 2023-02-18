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
        'ns1' => 'ns1.example.com',
        'ns2' => 'ns2.example.com'
    );
    $domainUpdateNS = $epp->domainUpdateNS($params);
	
    if (array_key_exists('error', $domainUpdateNS))
    {
        echo 'DomainUpdateNS Error: ' . $domainUpdateNS['error'] . PHP_EOL;
    }
    else
    {
        echo "DomainUpdateNS result: " . $domainUpdateNS['code'] . ": " . $domainUpdateNS['msg'] . PHP_EOL;
    }
	
    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>
