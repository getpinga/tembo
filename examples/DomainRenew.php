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
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>