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
        'period' => 1,
        'nss' => array('ns1.google.com','ns2.google.com'),
        'registrant' => 'tembo007',
        'contacts' => array(
           'admin' => 'tembo007',
           'tech' => 'tembo007',
           'billing' => 'tembo007'
        ),
        'authInfoPw' => 'Domainpw123@',
        'dnssec_records' => '2',
        'keyTag_1' => '33409',
        'alg_1' => '8',
        'digestType_1' => '1',
        'digest_1' => 'F4D6E26B3483C3D7B3EE17799B0570497FAF33BCB12B9B9CE573DDB491E16948',
        'keyTag_2' => '43409',
        'alg_2' => '8',
        'digestType_2' => '2',
        'digest_2' => 'F3D6E26B3483C3D7B3EE17799B0570497FAF33BCB12B9B9CE573DDB491E16564'
    );
    $domainCreateDNSSEC = $epp->domainCreateDNSSEC($params);

    if (array_key_exists('error', $domainCreateDNSSEC))
    {
        echo 'DomainCreateDNSSEC Error: ' . $domainCreateDNSSEC['error'] . PHP_EOL;
    }
    else
    {
        echo 'DomainCreateDNSSEC Result: ' . $domainCreateDNSSEC['code'] . ': ' . $domainCreateDNSSEC['msg'] . PHP_EOL;
		echo 'New Domain: ' . $domainCreateDNSSEC['name'] . PHP_EOL;
		echo 'Created On: ' . $domainCreateDNSSEC['crDate'] . PHP_EOL;
		echo 'Expires On: ' . $domainCreateDNSSEC['exDate'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}