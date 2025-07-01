<?php
/**
 * PlexEPP: EPP server benchmark
 *
 * Written in 2025 by Taras Kondratyuk (https://namingo.org)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Pinga\Tembo\EppRegistryFactory;

function connectEpp(string $registry) {
    try 
    {
        $epp = EppRegistryFactory::create($registry);
        $info = array(
            //For EPP-over-HTTPS,  'host' => 'https://registry.example.com/epp',
            'host' => 'epp.example.com',
            //For EPP-over-HTTPS , port is usually 443
            'port' => 700,
            'timeout' => 30,
            'tls' => '1.2', // Change to 1.3 if required
            'bind' => false,
            'bindip' => '1.2.3.4:0',
            'verify_peer' => false,
            'verify_peer_name' => false,
            //For EPP-over-HTTPS , change false to 2
            'verify_host' => false,
            'cafile' => '',
            'local_cert' => '/root/tembo/cert.pem',
            'local_pk' => '/root/tembo/key.pem',
            'passphrase' => '',
            'allow_self_signed' => true
        );
        $epp->connect($info);
        $login = $epp->login(array(
            'clID' => 'testregistrar1',
            'pw' => 'testpassword1',
            //'newpw' => 'testpassword2',
            'prefix' => 'tembo'
        ));
        if (array_key_exists('error', $login)) {
            throw new RuntimeException('Login Error: ' . $login['error']);
        } else {
            echo 'Login Result: ' . $login['code'] . ': ' . $login['msg'][0] . PHP_EOL;
        }
        return $epp;
    } catch(\Pinga\Tembo\Exception\EppException $e) {
        echo "Error : ".$e->getMessage() . PHP_EOL;
    } catch(Throwable $e) {
        echo "Error : ".$e->getMessage() . PHP_EOL;
    }
}