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
require_once 'vendor/autoload.php';

// Use the Epp class from your package
use Pinga\Tembo\Epp;

    try {
        $epp = new Epp();

        $info = array(
            'host' => 'epp.example.com',
            'port' => 700,
            'timeout' => 30,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'verify_host' => false,
            'cafile' => '',
            'local_cert' => '/root/epp/cert.pem',
            'local_pk' => '/root/epp/key.pem',
            'passphrase' => '',
            'allow_self_signed' => true
        );
        $epp->connect($info);


        $epp->login(array(
            'clID' => 'testregistrar1',
            'pw' => 'testpassword1',
            'prefix' => 'TESTR1'
        ));


        $checkparams = array(
            'domains' => array('example1.com','example2.com')
        );
        $domainCheck = $epp->domainCheck($checkparams);


        $infoparams = array(
            'domainname' => 'example1.com',
            'authInfoPw' => 'domainpw123@'
        );
        $domainInfo = $epp->domainInfo($infoparams);
		
 
        $createparams = array(
            'domainname' => 'example1.com',
            'period' => 1,
            'nss' => array('ns1.example.com','ns2.example.com'),
            'registrant' => 'EX-1234567',
            'contacts' => array(
                'EX-1234567' => 'admin',
                'EX-1234567' => 'tech',
                'EX-1234567' => 'billing'
                ),
            'authInfoPw' => 'domainpw123@'
        );
        $domainCreate = $epp->domainCreate($createparams);


        $transferparams = array(
            'domainname' => 'example1.com',
            'years' => 1,
            'authInfoPw' => 'domainpw123@'
        );
        $domainTransfer = $epp->domainTransfer($transferparams);

		
        $renewparams = array(
            'domainname' => 'example1.com',
            'regperiod' => 1
        );
        $domainRenew = $epp->domainRenew($renewparams);
    }

    catch (EppException $e) {
        echo 'Error: ', $e->getMessage();
    }


echo '<pre>';
//print_r($domainCheck);
//print_r($domainInfo);
//print_r($domainCreate);
//print_r($domainTransfer);
print_r($domainRenew);
echo '</pre>';

    //...
?>
