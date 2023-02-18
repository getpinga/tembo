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
         'domainname' => 'example1.com',
         'years' => 1,
         'authInfoPw' => 'domainpw123@'
    );
    $domainTransfer = $epp->domainTransfer($params);
	
    if (array_key_exists('error', $domainTransfer))
    {
        echo 'DomainTransfer Error: ' . $domainTransfer['error'] . PHP_EOL;
    }
    else
    {
		echo 'DomainTransfer Result: ' . $domainTransfer['code'] . ': ' . $domainTransfer['msg'] . PHP_EOL;
		echo 'Name: ' . $domainTransfer['name'] . PHP_EOL;
		echo 'Transfer Status: ' . $domainTransfer['trStatus'] . PHP_EOL;
		echo 'Gaining Registrar: ' . $domainTransfer['reID'] . PHP_EOL;
		echo 'Requested On: ' . $domainTransfer['reDate'] . PHP_EOL;
		echo 'Losing Registrar: ' . $domainTransfer['acID'] . PHP_EOL;
		echo 'Transfer Confirmed On: ' . $domainTransfer['acDate'] . PHP_EOL;
		echo 'New Expiration Date: ' . $domainTransfer['exDate'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
}
catch(EppException $e)
{
    echo 'Error: ', $e->getMessage();
}
?>
