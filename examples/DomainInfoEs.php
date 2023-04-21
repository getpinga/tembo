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
        'domainname' => 'test.es',
        'user' => 'your_user',
        'pass' => 'your_pass'
    );
    $domainInfo = $epp->domainInfo($params);
	
    if (array_key_exists('error', $domainInfo))
    {
        echo 'DomainInfo Error: ' . $domainInfo['error'] . PHP_EOL;
    }
    else
    {
		$msg = trim($domainInfo['msg']);
		echo 'DomainInfo Result: ' . $domainInfo['code'] . ': ' . $msg . PHP_EOL;
		echo 'Name: ' . $domainInfo['name'] . PHP_EOL;
		echo 'ROID: ' . $domainInfo['roid'] . PHP_EOL;
		echo 'Status: ' . $domainInfo['status'][0] . PHP_EOL;
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
		asort($domainInfo['ns']);
		foreach ($domainInfo['ns'] as $server) {
		  echo "Name Server: $server\n";
		}
		asort($domainInfo['host']);
		foreach ($domainInfo['host'] as $host) {
		  echo "Host: $host\n";
		}
		echo 'Current Registrar: ' . $domainInfo['clID'] . PHP_EOL;
		echo 'Created On: ' . $domainInfo['crDate'] . PHP_EOL;
		echo 'Expires On: ' . $domainInfo['exDate'] . PHP_EOL;
		echo 'Password: ' . $domainInfo['authInfo'] . PHP_EOL;
    }

}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
