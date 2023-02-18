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
        'id' => 'tembo007',
        'type' => 'int',
        'firstname' => 'Petko',
        'lastname' => 'Petkov',
        'companyname' => 'Petkovi OOD',
        'address1' => 'bul. Vitosha 1',
        'address2' => 'ap. 1',
        'city' => 'Sofia',
        'state' => 'Sofia-Grad',
        'postcode' => '1000',
        'country' => 'BG',
        'fullphonenumber' => '+359.1234567',
        'email' => 'test@petkovi.bg',
        'authInfoPw' => 'ABCSofi@345'
    );
    $contactCreate = $epp->contactCreate($params);

    if (array_key_exists('error', $contactCreate))
    {
        echo 'ContactCreate Error: ' . $contactCreate['error'] . PHP_EOL;
    }
    else
    {
        echo 'ContactCreate Result: ' . $contactCreate['code'] . ': ' . $contactCreate['msg'] . PHP_EOL . 'New Contact ID: ' . $contactCreate['id'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>