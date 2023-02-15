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

// Use the Epp class from your package
use Pinga\Tembo\Epp;
use Pinga\Tembo\EppClient;
use Pinga\Tembo\HttpsClient;

function connectEpp(){
    try{
        $epp = new EppClient();
        $info = array(
            'host' => 'epp.example.com',
            'port' => 700,
            'timeout' => 30,
            'tls' => '1.3',
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
        $login = $epp->login(array(
            'clID' => 'testregistrar1',
            'pw' => 'testpassword1',
            'prefix' => 'tembo',
            'ext' => ''
        ));
		echo 'Login Result: ' . $login['code'] . ': ' . $login['msg'][0] . PHP_EOL;
        return $epp;
    }catch(EppException $e){
        return "Error : ".$e->getMessage();
    }
}

?>
