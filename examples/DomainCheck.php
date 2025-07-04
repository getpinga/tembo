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
            if ($domain['avail'])
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
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}