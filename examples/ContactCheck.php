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
        'contact' => 'tembo002'
    );
    $contactCheck = $epp->contactCheck($params);
	
    if (array_key_exists('error', $contactCheck))
    {
        echo 'ContactCheck Error: ' . $contactCheck['error'] . PHP_EOL;
    }
    else
    {
		echo "ContactCheck result: " . $contactCheck['code'] . ": " . $contactCheck['msg'] . PHP_EOL;
		foreach ($contactCheck['contacts'] as $contact)
		{
			if ($contact['avail'] == 1)
			{
				echo "Contact 1: ID " . $contact['id'] . " is available" . PHP_EOL;
			}
			else
			{
				echo "Contact 1: ID " . $contact['id'] . " is not available because: " . $contact['reason'] . PHP_EOL;
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