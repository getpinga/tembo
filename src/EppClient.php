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
        $opts = array(
            'ssl' => array(
                'verify_peer' => (bool)$params['verify_peer'],
				        'verify_peer_name' => (bool)$params['verify_peer_name'],
				        'verify_host' => (bool)$params['verify_host'],
                'cafile' => (string)$params['cafile'],
                'local_cert' => (string)$params['local_cert'],
                'local_pk' => (string)$params['local_pk'],
                'passphrase' => (string)$params['passphrase'],
                'allow_self_signed' => (bool)$params['allow_self_signed']
            )
        );
        $context = stream_context_create($opts);
        $this->resource = stream_socket_client("tlsv1.3://{$host}:{$port}", $errno, $errmsg, $timeout, STREAM_CLIENT_CONNECT, $context);
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
        if (feof($this->resource)) {
            throw new EppException('Connection appears to have closed.');
        }

        $hdr = @fread($this->resource, 4);
        if (empty($hdr)) {
            throw new EppException("Error reading from server: $php_errormsg");
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
        @fwrite($this->resource, pack('N', (strlen($xml) + 4)) . $xml);
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
        return @fclose($this->resource);
    }
}
