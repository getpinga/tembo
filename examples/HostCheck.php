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
        'hostname' => 'ns351.blah.bg'
    );
    $hostCheck = $epp->hostCheck($params);
	
    if (array_key_exists('error', $hostCheck))
    {
        echo 'HostCheck Error: ' . $hostCheck['error'] . PHP_EOL;
    }
    else
    {
		echo "HostCheck result: " . $hostCheck['code'] . ": " . $hostCheck['msg'] . PHP_EOL;

		foreach ($hostCheck['hosts'] as $host)
		{
			if ($host['avail'] == 1)
			{
				echo "Host 1: " . $host['name'] . " is available" . PHP_EOL;
			}
			else
			{
				echo "Host 1: " . $host['name'] . " is not available because: " . $host['reason'] . PHP_EOL;
			}
		} 
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>