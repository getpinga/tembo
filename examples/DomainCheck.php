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
        'domains' => array('test.example','tembo.example')
    );
    $domainCheck = $epp->domainCheck($params);

    if (array_key_exists('error', $domainCheck))
    {
        echo 'DomainCheck Error: ' . $domainCheck['error'] . PHP_EOL;
    }
    else
    {
		echo "DomainCheck result: " . $domainCheck['code'] . ": " . $domainCheck['msg'] . PHP_EOL;
		$x=1;
		foreach ($domainCheck['domains'] as $domain)
		{
			if ($domain['avail'] == 1)
			{
				echo "Domain ".$x.": " . $domain['name'] . " is available" . PHP_EOL;
			}
			else
			{
				echo "Domain ".$x.": " . $domain['name'] . " is not available because: " . $domain['reason'] . PHP_EOL;
			}
			$x++;
		}
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>