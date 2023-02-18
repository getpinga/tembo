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

use Pinga\Tembo\EppRegistryFactory;

function connectEppDB($registry){
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=mydatabase', 'username', 'password');
        $stmt = $pdo->prepare("SELECT local_cert, local_pk, passphrase FROM epp_credentials WHERE id = :id");
        $stmt->execute(array('id' => 1));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $epp = EppRegistryFactory::create($registry);
        $info = array(
            'host' => 'epp.example.com',
            'port' => 700,
            'timeout' => 30,
            'tls' => '1.3',
            'bind' => false,
            'bindip' => '1.2.3.4:0',
            'verify_peer' => false,
            'verify_peer_name' => false,
            'verify_host' => false,
            'cafile' => '',
            'local_cert' => $result['local_cert'],
            'local_pk' => $result['local_pk'],
            'passphrase' => $result['passphrase'],
            'allow_self_signed' => true
        );
        $epp->connect($info);
        $login = $epp->login(array(
            'clID' => 'testregistrar1',
            'pw' => 'testpassword1',
            'prefix' => 'tembo',
        ));
	echo 'Login Result: ' . $login['code'] . ': ' . $login['msg'][0] . PHP_EOL;
        return $epp;
    }catch(EppException $e){
        return "Error : ".$e->getMessage();
    }
}

?>
