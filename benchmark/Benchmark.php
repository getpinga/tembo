<?php
/**
 * PlexEPP: EPP server benchmark
 *
 * Written in 2025 by Taras Kondratyuk (https://namingo.org)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

require_once '../vendor/autoload.php';
require_once 'Connection.php';
require_once 'Helpers.php';

try {
    // Start timing
    $startTime = microtime(true);
    
    $epp = connectEpp('generic');

    // Create 200 batches of 5 domain checks each
    for ($i = 0; $i < 200; $i++) {
        $domains = [];
        for ($j = 0; $j < 5; $j++) {
            $domains[] = randomDomain();
        }
        performDomainCheck($epp, $domains);
    }
    
    // Create 10,000 random domain create requests
    for ($i = 0; $i < 10000; $i++) {
        $domain = randomDomain();
        performDomainCreate($epp, $domain);
    }

    // Create 10,000 random domain info requests
    for ($i = 0; $i < 10000; $i++) {
        $domain = randomDomain();
        performDomainInfo($epp, $domain);
    }

    // End timing
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    echo 'Total Execution Time: ' . $executionTime . ' seconds' . PHP_EOL;

    $epp->logout();
} catch (\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : " . $e->getMessage() . PHP_EOL;
} catch (Throwable $e) {
    echo "Error : " . $e->getMessage() . PHP_EOL;
}
