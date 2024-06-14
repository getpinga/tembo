<?php
/**
 * Tembo EPP client test file
 *
 * Written in 2024 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

// Include the Composer autoloader
require_once '../vendor/autoload.php';
require_once 'Connection.php';

try
{
    $epp = connectEpp('generic');
    $clTRID = str_replace('.', '', round(microtime(1), 3));

    $params = array(
        'xml' => '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
     xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <check>
      <domain:check xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
        <domain:name>example.com</domain:name>
        <domain:name>example.net</domain:name>
      </domain:check>
    </check>
    <clTRID>'.$clTRID.'</clTRID>
  </command>
</epp>
');
    $rawXml = $epp->rawXml($params);
    
    if (array_key_exists('error', $rawXml))
    {
        echo 'RawXml Error: ' . $rawXml['error'] . PHP_EOL;
    }
    else
    {
        echo 'RawXml Result: ' . $rawXml['code'] . ': ' . $rawXml['msg'] . PHP_EOL;
        echo 'Command Result: ' . $rawXml['xml'] . PHP_EOL;
    }

    $logout = $epp->logout();

    echo 'Logout Result: ' . $logout['code'] . ': ' . $logout['msg'][0] . PHP_EOL;
} catch(\Pinga\Tembo\Exception\EppException $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
} catch(Throwable $e) {
    echo "Error : ".$e->getMessage() . PHP_EOL;
}