<?php
/**
 * Tembo EPP client test file
 *
 * Written in 2023 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */
 
use Pinga\Tembo\EppRegistryFactory;

// Include the Composer autoloader
require_once '../vendor/autoload.php';

try
{
	$epp = EppRegistryFactory::create('ES');
	
    $params = array(
        'type' => 'int',
        'firstname' => 'Petko',
        'lastname' => 'Petkov',
        'address1' => 'bul. Vitosha 1',
        'city' => 'Sofia',
        'state' => 'Sofia-Grad',
        'postcode' => '1000',
        'country' => 'BG',
        'fullphonenumber' => '+359.1234567',
        'email' => 'test@petkovi.bg',
        'authInfoPw' => 'ABCSofi@345',
        'uin' => '1234567890',
        'user' => 'your_user',
        'pass' => 'your_pass'
    );
    $contactCreate = $epp->contactCreate($params);

    if (array_key_exists('error', $contactCreate))
    {
        echo 'ContactCreate Error: ' . $contactCreate['error'] . PHP_EOL;
    }
    else
    {
		$msg = trim($contactCreate['msg']);
        echo 'ContactCreate Result: ' . $contactCreate['code'] . ': ' . $msg . PHP_EOL . 'New Contact ID: ' . $contactCreate['id'] . PHP_EOL . 'Created On: ' . $contactCreate['crDate'] . PHP_EOL;
    }

}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
