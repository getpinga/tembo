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

try {
    $epp = EppRegistryFactory::create('ES');

    $params = array(
        'domains' => array('test.es'),
        'user' => 'your_user',
        'pass' => 'your_pass'
    );

    $domainCheck = $epp->domainCheck($params);

    if (array_key_exists('error', $domainCheck)) {
        echo 'DomainCheck Error: ' . $domainCheck['error'] . PHP_EOL;
    } else {
        $msg = trim($domainCheck['msg']);
        echo "DomainCheck result: " . $domainCheck['code'] . ": " . $msg . PHP_EOL;
        $x = 1;
        foreach ($domainCheck['domains'] as $domain) {
            if ($domain['avail'] == 'true') {
                echo "Domain " . $x . ": " . $domain['name'] . " is available" . PHP_EOL;
            } else {
                if (!empty($domain['reason'])) {
                    echo "Domain " . $x . ": " . $domain['name'] . " is not available because: " . $domain['reason'] . PHP_EOL;
                } else {
                    echo "Domain " . $x . ": " . $domain['name'] . " is not available." . PHP_EOL;
                }
            }

            $x++;
        }
    }
} catch (EppException $e) {
    echo 'Error: ' . $e->getMessage();
}
