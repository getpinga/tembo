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
        'authInfoPw' => 'Domainpw123@'
    );
    $domainCreate = $epp->domainCreate($params);

    if (array_key_exists('error', $domainCreate))
    {
        echo 'DomainCreate Error: ' . $domainCreate['error'] . PHP_EOL;
    }
    else
    {
        echo 'DomainCreate Result: ' . $domainCreate['code'] . ': ' . $domainCreate['msg'] . PHP_EOL;
		echo 'New Domain: ' . $domainCreate['name'] . PHP_EOL;
		echo 'Created On: ' . $domainCreate['crDate'] . PHP_EOL;
		echo 'Expires On: ' . $domainCreate['exDate'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}