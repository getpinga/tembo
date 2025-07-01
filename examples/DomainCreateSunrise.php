<?php
/**
 * Tembo EPP client test file
 *
 * Written in 2023-2025 by Taras Kondratyuk (https://namingo.org)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Connection.php';

try
{
    $epp = connectEpp('generic');
    
    $params = array(
        'domainname' => 'test.example',
        'period' => 1,
        'registrant' => 'tembo007',
        'contacts' => array(
           'admin' => 'tembo007',
           'tech' => 'tembo007'
        ),
        'authInfoPw' => 'Domainpw123@',
        'encodedSignedMark' => 'INSERT_HERE'
    );
    $domainCreateSunrise = $epp->domainCreateSunrise($params);

    if (array_key_exists('error', $domainCreateSunrise))
    {
        echo 'DomainCreateSunrise Error: ' . $domainCreateSunrise['error'] . PHP_EOL;
    }
    else
    {
        echo 'DomainCreateSunrise Result: ' . $domainCreateSunrise['code'] . ': ' . $domainCreateSunrise['msg'] . PHP_EOL;
        echo 'New Domain: ' . $domainCreateSunrise['name'] . PHP_EOL;
        echo 'Created On: ' . $domainCreateSunrise['crDate'] . PHP_EOL;
        echo 'Expires On: ' . $domainCreateSunrise['exDate'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}