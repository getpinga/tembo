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
        // Comment these lines and uncomment the block below if the TLD requires <domain:hostAttr> with IP addresses:
        'ns1' => 'ns1.example.com',
        'ns2' => 'ns2.example.com',
        /* Uncomment the following block for TLDs requiring <domain:hostAttr> with IP addresses:
        'nss' => array(
            array(
                'hostName' => 'ns1.example.com',
                'ipv4' => '192.168.1.1',
                'ipv6' => '2001:db8::1'
            ),
            array(
                'hostName' => 'ns2.example.com',
                'ipv4' => '192.168.1.2'
            ),
            array(
                'hostName' => 'ns3.example.com'
            )
        ), */
    );
    $domainUpdateNS = $epp->domainUpdateNS($params);
    
    if (array_key_exists('error', $domainUpdateNS))
    {
        echo 'DomainUpdateNS Error: ' . $domainUpdateNS['error'] . PHP_EOL;
    }
    else
    {
        echo "DomainUpdateNS result: " . $domainUpdateNS['code'] . ": " . $domainUpdateNS['msg'] . PHP_EOL;
    }
    
    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}