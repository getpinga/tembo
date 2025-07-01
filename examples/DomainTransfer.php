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
         'years' => 1,
         'authInfoPw' => 'Domainpw123@',
         'op' => 'request'
    );
    $domainTransfer = $epp->domainTransfer($params);
    
    if (array_key_exists('error', $domainTransfer)) {
        echo 'DomainTransfer Error: ' . $domainTransfer['error'] . PHP_EOL;
    } else {
        if (array_key_exists('code', $domainTransfer) && array_key_exists('msg', $domainTransfer)) {
            echo 'DomainTransfer Result: ' . $domainTransfer['code'] . ': ' . $domainTransfer['msg'] . PHP_EOL;
        }
        if (array_key_exists('name', $domainTransfer)) {
            echo 'Name: ' . $domainTransfer['name'] . PHP_EOL;
        }
        if (array_key_exists('trStatus', $domainTransfer)) {
            echo 'Transfer Status: ' . $domainTransfer['trStatus'] . PHP_EOL;
        }
        if (array_key_exists('reID', $domainTransfer)) {
            echo 'Gaining Registrar: ' . $domainTransfer['reID'] . PHP_EOL;
        }
        if (array_key_exists('reDate', $domainTransfer)) {
            echo 'Requested On: ' . $domainTransfer['reDate'] . PHP_EOL;
        }
        if (array_key_exists('acID', $domainTransfer)) {
            echo 'Losing Registrar: ' . $domainTransfer['acID'] . PHP_EOL;
        }
        if (array_key_exists('acDate', $domainTransfer)) {
            echo 'Transfer Confirmed On: ' . $domainTransfer['acDate'] . PHP_EOL;
        }
        if (array_key_exists('exDate', $domainTransfer)) {
            echo 'New Expiration Date: ' . $domainTransfer['exDate'] . PHP_EOL;
        }
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}