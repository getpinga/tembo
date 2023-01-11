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
		'authInfoPw' => 'domainpw123@'
    );
    $domainInfo = $epp->domainInfo($params);
	
    if (array_key_exists('error', $domainInfo))
    {
        echo 'DomainInfo Error: ' . $domainInfo['error'] . PHP_EOL;
    }
    else
    {
		echo 'DomainInfo Result: ' . $domainInfo['code'] . ': ' . $domainInfo['msg'] . PHP_EOL;
		echo 'Name: ' . $domainInfo['name'] . PHP_EOL;
		echo 'ROID: ' . $domainInfo['roid'] . PHP_EOL;
		echo 'Status: ' . $domainInfo['status'][1] . PHP_EOL;
		echo 'Name: ' . $domainInfo['name'] . PHP_EOL;
		echo 'Registrant: ' . $domainInfo['registrant'] . PHP_EOL;
		echo 'Contact: ';
		foreach ($domainInfo['contact'] as $key => $value) {
			echo $key . ': ' . $value . ', ';
		}
		echo PHP_EOL;
		echo 'NS: ';
		foreach ($domainInfo['ns'] as $key => $value) {
			echo $key . ': ' . $value . ', ';
		}
		echo PHP_EOL;
		echo 'Host: ';
		foreach ($domainInfo['host'] as $key => $value) {
			echo $key . ': ' . $value . ', ';
		}
		echo PHP_EOL;
		echo 'Current Registrar: ' . $domainInfo['clID'] . PHP_EOL;
		echo 'Original Registrar: ' . $domainInfo['crID'] . PHP_EOL;
		echo 'Created On: ' . $domainInfo['crDate'] . PHP_EOL;
		echo 'Updated By: ' . $domainInfo['upID'] . PHP_EOL;
		echo 'Updated On: ' . $domainInfo['upDate'] . PHP_EOL;
		echo 'Expires On: ' . $domainInfo['exDate'] . PHP_EOL;
		echo 'Transferred On: ' . $domainInfo['trDate'] . PHP_EOL;
		echo 'Password: ' . $domainInfo['authInfo'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>
