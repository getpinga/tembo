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
        'authInfoPw' => 'Domainpw123@'
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
        $status = $domainInfo['status'] ?? 'No status available';
        if (is_array($status)) {
            echo 'Status: ' . implode(', ', $status) . PHP_EOL;
        } else {
            echo 'Status: ' . $status . PHP_EOL;
        }
        echo 'Registrant: ' . $domainInfo['registrant'] . PHP_EOL;

        $contact_types = array("admin", "billing", "tech");
        foreach ($contact_types as $type) {
            $contact = array_values(array_filter($domainInfo['contact'], function($c) use ($type) {
                return $c["type"] == $type;
            }));
            if (count($contact) > 0) {
                $type = ucfirst($type);
                echo $type . ": " . $contact[0]["id"] . "\n";
            }
        }
        if (isset($domainInfo['ns']) && is_array($domainInfo['ns'])) {
            asort($domainInfo['ns']);
            foreach ($domainInfo['ns'] as $server) {
                echo "Name Server: $server\n";
            }
        } else {
            echo "No Name Servers available.\n";
        }

        if (isset($domainInfo['host']) && is_array($domainInfo['host'])) {
            asort($domainInfo['host']);
            foreach ($domainInfo['host'] as $host) {
                echo "Host: $host\n";
            }
        } else {
            echo "No Hosts available.\n";
        }
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
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}