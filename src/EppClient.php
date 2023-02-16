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

class EppClient extends Epp
{
    /**
     * connect
     */
    public function connect($params = array())
    {
        $host = (string)$params['host'];
        $port = (int)$params['port'];
        $timeout = (int)$params['timeout'];
        $tls = (string)$params['tls'];
        $bind = (string)$params['bind'];
        $bindip = (string)$params['bindip'];
	if ($tls !== '1.3' && $tls !== '1.2' && $tls !== '1.1') {
	    throw new EppException('Invalid TLS version specified.');
	}
        $opts = array(
            'ssl' => array(
                'verify_peer' => (bool)$params['verify_peer'],
		'verify_peer_name' => (bool)$params['verify_peer_name'],
		'verify_host' => (bool)$params['verify_host'],
                'cafile' => (string)$params['cafile'],
                'local_cert' => (string)$params['local_cert'],
                'local_pk' => (string)$params['local_pk'],
                'passphrase' => (string)$params['passphrase'],
                'allow_self_signed' => (bool)$params['allow_self_signed'],
		'min_tls_version' => $tls
            )
        );
        if ($bind) {
            $opts['socket'] = array('bindto' => $bindip);
        }
        $context = stream_context_create($opts);
        $this->resource = stream_socket_client("tls://{$host}:{$port}", $errno, $errmsg, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$this->resource) {
            throw new EppException("Cannot connect to server '{$host}': {$errmsg}");
        }

        return $this->readResponse();
    }
	
    /**
     * readResponse
     */
    public function readResponse()
    {
        $hdr = stream_get_contents($this->resource, 4);
        if ($hdr === false) {
            throw new EppException('Connection appears to have closed.');
        }
        if (strlen($hdr) < 4) {
            throw new EppException('Failed to read header from the connection.');
        }
        $unpacked = unpack('N', $hdr);
        $xml = fread($this->resource, ($unpacked[1] - 4));
        $xml = preg_replace('/></', ">\n<", $xml);
        $this->_response_log($xml);        
        return $xml;
    }
	
    /**
     * writeRequest
     */
    public function writeRequest($xml)
    {
        $this->_request_log($xml);
        if (fwrite($this->resource, pack('N', (strlen($xml) + 4)) . $xml) === false) {
            throw new EppException('Error writing to the connection.');
        }
        $r = simplexml_load_string($this->readResponse());
        if (isset($r->response) && $r->response->result->attributes()->code >= 2000) {
            throw new EppException($r->response->result->msg);
        }
            return $r;
    }

    /**
     * disconnect
     */
    public function disconnect()
    {
        if (!fclose($this->resource)) {
            throw new EppException('Error closing the connection.');
        }
        $this->resource = null;
    }

}
