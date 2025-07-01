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
        'nss' => array('ns1.google.com','ns2.google.com'),
        'registrant' => 'tembo007',
        'contacts' => array(
           'admin' => 'tembo007',
           'tech' => 'tembo007',
           'billing' => 'tembo007'
        ),
        'authInfoPw' => 'Domainpw123@',
        'noticeID' => 'ABCDE1234FGHIJK5678',
        'notAfter' => '2023-02-24T09:30:00.0Z',
        'acceptedDate' => '2023-02-21T09:30:00.0Z'
    );
    $domainCreateClaims = $epp->domainCreateClaims($params);

    if (array_key_exists('error', $domainCreateClaims))
    {
        echo 'DomainCreateClaims Error: ' . $domainCreateClaims['error'] . PHP_EOL;
    }
    else
    {
        echo 'DomainCreateClaims Result: ' . $domainCreateClaims['code'] . ': ' . $domainCreateClaims['msg'] . PHP_EOL;
        echo 'New Domain: ' . $domainCreateClaims['name'] . PHP_EOL;
        echo 'Created On: ' . $domainCreateClaims['crDate'] . PHP_EOL;
        echo 'Expires On: ' . $domainCreateClaims['exDate'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}