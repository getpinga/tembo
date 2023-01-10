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
use Pinga\Tembo\EppClient;
//use Pinga\Tembo\HttpsClient;

    try {
        $epp = new EppClient();

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
            'prefix' => 'TESTR1',
            'ext' => 'iis.se'
        ));
		
		
/*         $ccreateparams = array(
            'id' => 'ABCTEST123',
            'firstname' => 'Petko',
            'lastname' => 'Petkov',
            'companyname' => 'Petkovi OOD',
            'address1' => 'bul. Vitosha 1',
            'address2' => 'ap. 1',
            'city' => 'Sofia',
            'state' => 'Sofia-Grad',
            'postcode' => '1000',
            'country' => 'BG',
            'fullphonenumber' => '+359.1234567',
            'email' => 'test@petkovi.bg',
            'orgno' => '0049132590',
            'vatno' => ''
        );
        $contactCreate = $epp->contactCreateIIS($ccreateparams); */

/*        $checkparams = array(
            'domains' => array('tembo3.test')
        );
        $domainCheck = $epp->domainCheck($checkparams); 
		
	 foreach ($domainCheck['domains'] as $domain) {
    // Check if the domain is available
    if ($domain['avail'] === 1) {
        echo "The domain " . $domain['name'] . " is available." . PHP_EOL;
    } else {
        echo "The domain " . $domain['name'] . " is not available. Reason: " . $domain['reason'] . PHP_EOL;
    }
}  */


/*         $infoparams = array(
            'domainname' => 'example1.com',
            'authInfoPw' => 'domainpw123@'
        );
        $domainInfo = $epp->domainInfo($infoparams);
		*/
 
        $createparams = array(
            'domainname' => 'georgievi.test',
            'period' => 1,
            'nss' => array('ns1.example.com','ns2.example.com'),
            'registrant' => 'ABCTEST123',
/*             'contacts' => array(
                'EX-1234567' => 'admin',
                'EX-1234567' => 'tech',
                'EX-1234567' => 'billing'
                ), */
             'authInfoPw' => 'Domainpw123@',
            'ext' => 'iis.se'
        );
        $domainCreate = $epp->domainCreate($createparams); 
/*

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
        $domainRenew = $epp->domainRenew($renewparams); */
	    
/*  	$updparams = array(
            'domainname' => 'tembo1.test',
            'ns1' => 'ns1.google.com',
            'ns2' => 'ns2.google.com'
        );
        $domainUpdateNS = $epp->domainUpdateNS($updparams); 
		print_r($domainUpdateNS);  */

        $logout = $epp->logout();
		print_r($logout);
    }

    catch (EppException $e) {
        echo 'Error: ', $e->getMessage();
    }
?>
