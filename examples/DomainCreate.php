<?php
/**
 * Tembo EPP client test file
 *
 * Written in 2024 by Taras Kondratyuk (https://getpinga.com)
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
        // For TLDs that require the <domain:hostAttr> element (e.g., when assigning nameservers with specific IP addresses),
        // comment out the next line and uncomment the one below to include hostAttr details in the EPP request.
        'nss' => array('ns1.google.com','ns2.google.com'),
        /*'nss' => array(
            array(
                'hostName' => 'ns.test.it',
                'ipv4' => '192.168.100.10'
            ),
            array(
                'hostName' => 'ns2.test.it',
                'ipv4' => '192.168.100.20'
            ),
            array(
                'hostName' => 'ns3.foo.com'
            )
        ),*/
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
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}