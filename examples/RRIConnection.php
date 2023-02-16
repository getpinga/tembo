<?php
/**
 * Tembo EPP client test file
 *
 * Written in 2023 by Taras Kondratyuk (https://getpinga.com)
 * Based on phprri by Bigwern/phprri and DENIC
 *
 * @license MIT
 */

 // Include the Composer autoloader
require_once '../vendor/autoload.php';

// Use the Epp class from your package
use Pinga\Tembo\Epp;
use Pinga\Tembo\RRIClient;

try {
        $epp = new RRIClient();
        $info = array(
            'host' => 'rri.denic.de',
            'port' => 1234,
            'timeout' => 30
        );
        $user = 'your_username';
        $password = 'your_password';
		
        $conn =  $epp->connect($info);
		
        // Login
        $login = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: LOGIN\nuser: $user\npassword: $password\n");
        echo 'Login: ' . PHP_EOL . $login . PHP_EOL;
		
        // Domain Info
        $domain = 'domain-check.de';
        $dom_info = $epp->RRI_SendAndRead($conn, "Action: info\nVersion: 3.0\nRecursive: true\nDomain: $domain\n");
        echo 'Domain Info: ' . PHP_EOL . $dom_info . PHP_EOL;

        // Logout
        $logout = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: LOGOUT\n");
        echo 'Logout: ' . PHP_EOL . $logout . PHP_EOL;

    } catch (EppException $e) {
        return "Error : ".$e->getMessage();
    }
