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
        'hostname' => 'ns1.test.example'
    );
    $hostCheck = $epp->hostCheck($params);
    
    if (array_key_exists('error', $hostCheck))
    {
        echo 'HostCheck Error: ' . $hostCheck['error'] . PHP_EOL;
    }
    else
    {
        if ($registry == 'fred') {
        echo "HostCheck result: " . $hostCheck['code'] . ": " . $hostCheck['msg'] . PHP_EOL;
        $x=1;
        foreach ($hostCheck['hosts'] as $host)
        {
            if ($host['avail'])
            {
                echo "Host ".$x.": " . $host['id'] . " is available" . PHP_EOL;
            }
            else
            {
                echo "Host ".$x.": " . $host['id'] . " is not available because: " . $host['reason'] . PHP_EOL;
            }
            $x++;
        } 
        } else {
        echo "HostCheck result: " . $hostCheck['code'] . ": " . $hostCheck['msg'] . PHP_EOL;
        $x=1;
        foreach ($hostCheck['hosts'] as $host)
        {
            if ($host['avail'] == 1)
            {
                echo "Host ".$x.": " . $host['name'] . " is available" . PHP_EOL;
            }
            else
            {
                echo "Host ".$x.": " . $host['name'] . " is not available because: " . $host['reason'] . PHP_EOL;
            }
            $x++;
        } 
        }
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}