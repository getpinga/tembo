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
        'contact' => 'tembo005'
    );
    $contactInfo = $epp->contactInfo($params);
	
    if (array_key_exists('error', $contactInfo))
    {
        echo 'ContactInfo Error: ' . $contactInfo['error'] . PHP_EOL;
    }
    else
    {
		echo 'ContactInfo Result: ' . $contactInfo['code'] . ': ' . $contactInfo['msg'] . PHP_EOL;
		echo 'ID: ' . $contactInfo['id'] . PHP_EOL;
		echo 'ROID: ' . $contactInfo['roid'] . PHP_EOL;
		echo 'Name: ' . $contactInfo['name'] . PHP_EOL;
		echo 'Org: ' . $contactInfo['org'] . PHP_EOL;
		echo 'Street 1: ' . $contactInfo['street1'] . PHP_EOL;
		echo 'Street 2: ' . $contactInfo['street2'] . PHP_EOL;
		echo 'Street 3: ' . $contactInfo['street3'] . PHP_EOL;
		echo 'City: ' . $contactInfo['city'] . PHP_EOL;
		echo 'State: ' . $contactInfo['state'] . PHP_EOL;
		echo 'Postal: ' . $contactInfo['postal'] . PHP_EOL;
		echo 'Country: ' . $contactInfo['country'] . PHP_EOL;
		echo 'Voice: ' . $contactInfo['voice'] . PHP_EOL;
		echo 'Fax: ' . $contactInfo['fax'] . PHP_EOL;
		echo 'Email: ' . $contactInfo['email'] . PHP_EOL;
		echo 'Current Registrar: ' . $contactInfo['clID'] . PHP_EOL;
		echo 'Original Registrar: ' . $contactInfo['crID'] . PHP_EOL;
		echo 'Created On: ' . $contactInfo['crDate'] . PHP_EOL;
		echo 'Updated By: ' . $contactInfo['upID'] . PHP_EOL;
		echo 'Updated On: ' . $contactInfo['upDate'] . PHP_EOL;
		echo 'Password: ' . $contactInfo['authInfo'] . PHP_EOL;
		echo 'Status: ' . $contactInfo['status'][1] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>