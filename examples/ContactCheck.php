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
        'contact' => 'tembo007'
    );
    $contactCheck = $epp->contactCheck($params);
	
    if (array_key_exists('error', $contactCheck))
    {
        echo 'ContactCheck Error: ' . $contactCheck['error'] . PHP_EOL;
    }
    else
    {
		echo "ContactCheck result: " . $contactCheck['code'] . ": " . $contactCheck['msg'] . PHP_EOL;
		$x=1;
		foreach ($contactCheck['contacts'] as $contact)
		{
			if ($contact['avail'])
			{
				echo "Contact ".$x.": ID " . $contact['id'] . " is available" . PHP_EOL;
			}
			else
			{
				echo "Contact ".$x.": ID " . $contact['id'] . " is not available because: " . $contact['reason'] . PHP_EOL;
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
