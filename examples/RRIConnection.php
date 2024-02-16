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
        
        // Contact Create
        $contact = "Action: Create\n";
        $contact .= "Version: 3.0\n";
        $contact .= "Ctid: " . uniqid() . "\n";
        $contact .= "Handle: DENIC-1000002-" . uniqid() . "\n";
        $contact .= "Type: person\n";
        $contact .= "Name: Petko Petkov\n";
        $contact .= "Organisation: Petkovi OOD\n";
        $contact .= "Address: bul. Vitosha 1\n";
        $contact .= "PostalCode: 1000\n";
        $contact .= "City: Sofia\n";
        $contact .= "CountryCode: BG\n";
        $contact .= "Email: test@petkovi.bg\n";
        //$con_create = $epp->RRI_SendAndRead($conn, $contact);
        //echo 'Contact Create: ' . PHP_EOL . $con_create . PHP_EOL;
        
        // Contact Check
        $handle = 'DENIC-1000002-MAX';
        //$con_check = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: check\nhandle: $handle\n");
        //echo 'Contact Check: ' . PHP_EOL . $con_check . PHP_EOL;
        
        // Contact Info
        $handle = 'DENIC-1000002-MAX';
        //$con_info = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: info\nhandle: $handle\n");
        //echo 'Contact Info: ' . PHP_EOL . $con_info . PHP_EOL;
        
        // Domain Create
        $domain = "Action: Create\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Domain: de-example.de\n";
        $domain .= "Holder: DENIC-1000002-HOLDER\n";
        $domain .= "Abusecontact: DENIC-1000002-ABUSE\n";
        $domain .= "Generalrequest: DENIC-1000002-GENERAL\n";
        $domain .= "Nserver: ns1.beispiel-eins.de\n";
        $domain .= "Nserver: ns2.beispiel-eins.de\n";
        //$dom_create = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain Create: ' . PHP_EOL . $dom_create . PHP_EOL;
        
        // Domain Check
        $domain = 'de-example.de';
        //$dom_check = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: check\nDomain: $domain\n");
        //echo 'Domain Check: ' . PHP_EOL . $dom_check . PHP_EOL;
        
        // Domain Info
        $domain = 'domain-check.de';
        $authinfo = 'abc123';
        //$dom_info = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: info\nrecursive: true\nAuthInfo: $authinfo\ndomain: $domain\n");
        //echo 'Domain Info: ' . PHP_EOL . $dom_info . PHP_EOL;
        
        // Domain Update
        $domain = "Action: update\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        $domain .= "Holder: DENIC-1000002-HOLDER\n";
        $domain .= "Abusecontact: DENIC-1000002-ABUSE\n";
        $domain .= "Generalrequest: DENIC-1000002-GENERAL\n";
        $domain .= "Nserver: ns1.beispiel-eins.de\n";
        $domain .= "Nserver: ns2.beispiel-eins.de 81.91.170.12\n";
        $domain .= "Nserver: ns2.beispiel-eins.de 2001:608:6:6:0:0:0:11\n";
        //$dom_update = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain Update: ' . PHP_EOL . $dom_update . PHP_EOL;
        
        // Domain Delete
        $domain = "Action: delete\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        $domain .= "Holder: DENIC-1000002-HOLDER\n";
        //$dom_delete = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain Delete: ' . PHP_EOL . $dom_delete . PHP_EOL;
        
        // Domain Restore
        $domain = "Action: restore\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        //$dom_restore = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain Restore: ' . PHP_EOL . $dom_restore . PHP_EOL;
        
        // Create AuthInfo1
        $domain = "Action: CREATE-AUTHINFO1\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        $domain .= "AuthInfoHash:     4213d924230224fd719218b4acbd92f96ebe4344f3d5d1478dede1aa44e4cf4b\n";
        $domain .= "AuthInfoExpire: 20100724\n";
        //$dom_authinfo1 = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain AuthInfo1: ' . PHP_EOL . $dom_authinfo1 . PHP_EOL;
        
        // Create AuthInfo2
        $domain = "Action: CREATE-AUTHINFO2\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        //$dom_authinfo2 = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain AuthInfo2: ' . PHP_EOL . $dom_authinfo2 . PHP_EOL;
        
        // Delete AuthInfo1
        $domain = "Action: DELETE-AUTHINFO1\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        //$dom_authinfo1 = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Delete AuthInfo1: ' . PHP_EOL . $dom_authinfo1 . PHP_EOL;
        
        // Domain Chprov
        $domain = "Action: CHPROV\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        $domain .= "Holder: DENIC-1000002-HOLDER\n";
        $domain .= "Abusecontact: DENIC-1000002-ABUSE\n";
        $domain .= "Generalrequest: DENIC-1000002-GENERAL\n";
        $domain .= "Nserver: ns1.beispiel-eins.de\n";
        $domain .= "Nserver: ns2.beispiel-eins.de\n";
        $domain .= "AuthInfo: SupermanistSuper\n";
        //$dom_chprov = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain Chprov: ' . PHP_EOL . $dom_chprov . PHP_EOL;
        
        // Domain Transit
        $domain = "Action: TRANSIT\n";
        $domain .= "Version: 3.0\n";
        $domain .= "Ctid: " . uniqid() . "\n";
        $domain .= "Domain: de-example.de\n";
        //choose one of the following 2
        $domain .= "Disconnect:     true\n";
        $domain .= "Disconnect:     false\n";
        //$dom_transit = $epp->RRI_SendAndRead($conn, $domain);
        //echo 'Domain Transit: ' . PHP_EOL . $dom_transit . PHP_EOL;
        
        // Queue Read
        // MsgType can be one of: chprovAuthInfo, authInfoExpire, authInfo2Notify, authInfo2Delete, expireWarning, expire, domainDelete
        $queue = "Action: QUEUE-READ\n";
        $queue .= "Version: 3.0\n";
        //$queue .= "MsgType: (!choose!)\n";
        //$queue_read = $epp->RRI_SendAndRead($conn, $queue);
        //echo 'Queue Read: ' . PHP_EOL . $queue_read . PHP_EOL;
        
        // Queue Delete
        // MsgType can be one of: chprovAuthInfo, authInfoExpire, authInfo2Notify, authInfo2Delete, expireWarning, expire, domainDelete
        $queue = "Action: QUEUE-DELETE\n";
        $queue .= "Version: 3.0\n";
        $queue .= "Msgid: (!message_id!)\n";
        //$queue .= "MsgType: (!choose!)\n";
        $queue .= "Ctid: " . uniqid() . "\n";
        //$queue_delete = $epp->RRI_SendAndRead($conn, $queue);
        //echo 'Queue Delete: ' . PHP_EOL . $queue_delete . PHP_EOL;
        
        // Registrar Info
        $registrar = 'DENIC-99995';
        //$reg_info = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: info\nRegacc: $registrar\n");
        //echo 'Registrar Info: ' . PHP_EOL . $reg_info . PHP_EOL;

        // Logout
        $logout = $epp->RRI_SendAndRead($conn, "version: 3.0\naction: LOGOUT\n");
        echo 'Logout: ' . PHP_EOL . $logout . PHP_EOL;

    } catch(\Pinga\Tembo\Exception\EppException $e) {
        echo "Error : ".$e->getMessage() . PHP_EOL;
    } catch(Throwable $e) {
        echo "Error : ".$e->getMessage() . PHP_EOL;
    }