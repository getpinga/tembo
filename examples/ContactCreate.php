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
        'firstname' => 'Svyatoslav',
        'lastname' => 'Petrenko',
        'companyname' => 'TOV TEMBO',
        'address1' => 'vul. Stryiska 1',
        'address2' => 'kv. 1',
        'city' => 'Lviv',
        'state' => 'Lviv',
        'postcode' => '48000',
        'country' => 'UA',
        'fullphonenumber' => '+380.1234567',
        'email' => 'test@tembo.ua',
        'authInfoPw' => 'ABCLviv@345',
        //'euType' => 'tech',
        //'nin_type' => 'person',
        //'nin' => '1234567789',
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
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}