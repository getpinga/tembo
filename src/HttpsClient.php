<?php
/**
 * Tembo EPP client library
 *
 * Written in 2023 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

namespace Pinga\Tembo;

use Pinga\Tembo\Epp;
use Pinga\Tembo\Exception\EppException;
use Pinga\Tembo\Exception\EppNotConnectedException;

class HttpsClient extends Epp
{
    /**
     * connect
     */
    public function connect($params = array())
    {
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, (string)$params['host']);
		    curl_setopt($ch, CURLOPT_PORT, (int)$params['port']);
		    curl_setopt($ch, CURLOPT_TIMEOUT, (int)$params['timeout']);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (bool)$params['verify_peer']);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $params['verify_host']);
		    if ($params['cafile']) {
		        curl_setopt($ch, CURLOPT_CAINFO, (string)$params['cafile']);
		    }
		    curl_setopt($ch, CURLOPT_SSLCERT, (string)$params['local_cert']);
		    curl_setopt($ch, CURLOPT_SSLKEY, (string)$params['local_pk']);
		    if ($params['passphrase']) {
		        curl_setopt($ch, CURLOPT_SSLKEYPASSWD, (string)$params['passphrase']);
		    }
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
		    curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/eppcookie.txt');
		    curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/eppcookie.txt');
		    $this->resource = curl_exec($ch);

        if (!$this->resource) {
            throw new EppException("Cannot connect to server '{$host}': {$errmsg}");
        }
		
		    $this->ch = $ch;
        return $this->readResponse();
    }
	
    /**
     * readResponse
     */
    public function readResponse()
    {
		    try {
			    $return = curl_exec($this->ch);
		    } catch (\EppException $e) {
			    $code = curl_errno($this->ch);
		    	$msg = curl_error($this->ch);
			    throw new \EppException($msg, $code);
		    }

		    return $return;
    }
	
    /**
     * writeRequest
     */
    public function writeRequest($xml)
    {
        $this->_request_log($xml);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $xml);
        $r = $this->readResponse();
        $r = new \SimpleXMLElement($r);
        if ($r->response->result->attributes()->code >= 2000) {
            throw new EppException($r->response->result->msg);
        }

        return $r;
    }

    /**
     * disconnect
     */
    public function disconnect()
    {
		return curl_close($this->ch);
    }
}
