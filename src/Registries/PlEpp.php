<?php
/**
 * Tembo EPP client library
 *
 * Written in 2023 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

namespace Pinga\Tembo\Registries;

use Pinga\Tembo\EppRegistryInterface;
use Pinga\Tembo\Exception\EppException;
use Pinga\Tembo\Exception\EppNotConnectedException;

class PlEpp implements EppRegistryInterface
{
    private $resource;
    private $isLoggedIn;
    private $prefix;

    public function __construct()
    {
        if (!extension_loaded('SimpleXML')) {
            throw new \Exception('PHP extension SimpleXML is not loaded.');
        }
    }

    /**
     * connect
     */
    public function connect($params = array())
    {
        $host = (string)$params['host'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
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
            $xml = preg_replace('/></', ">\n<", $return);
            $this->_response_log($xml);
        } catch (\EppException $e) {
            $code = curl_errno($this->ch);
            $msg = curl_error($this->ch);
            throw new \EppException($msg, $code);
        }

        return $xml;
    }

    /**
     * writeRequest
     */
    public function writeRequest($xml)
    {
        $this->_request_log($xml);
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $xml);
        $r = simplexml_load_string($this->readResponse());
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

    /**
    * wrapper for functions
    */
    public function __call($func, $args)
    {
        if (!function_exists($func)) {
            throw new \Exception("Call to undefined method Epp::$func().");
        }

        if ($func === 'connect') {
            try {
                $result = call_user_func_array($func, $args);
            } catch (\ErrorException $e) {
                throw new EppException($e->getMessage());
            }

            if (!is_resource($this->resource)) {
                throw new EppException('An error occured while trying to connect to EPP server.');
            }

            $result = null;
        } elseif (!is_resource($this->resource)) {
            throw new EppNotConnectedException();
        } else {
            array_unshift($args, $this->resource);
            try {
                $result = call_user_func_array($func, $args);
            } catch (\ErrorException $e) {
                throw new EppException($e->getMessage());
            }
        }

        return $result;
    }

    /**
     * login
     */
    public function login($params = array())
    {
        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ clID }}/';
            $to[] = htmlspecialchars($params['clID']);
            $from[] = '/{{ pwd }}/';
            $to[] = htmlspecialchars($params['pw']);
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($params['prefix'] . '-login-' . $microtime);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
  <command>
    <login>
      <clID>{{ clID }}</clID>
      <pw>{{ pwd }}</pw>
      <options>
        <version>1.0</version>
        <lang>en</lang>
      </options>
 <svcs>
 <objURI>http://www.dns.pl/nask-epp-schema/contact-2.1</objURI>
 <objURI>http://www.dns.pl/nask-epp-schema/host-2.1</objURI>
 <objURI>http://www.dns.pl/nask-epp-schema/domain-2.1</objURI>
 <objURI>http://www.dns.pl/nask-epp-schema/future-2.1</objURI>
 <svcExtension>
 <extURI>http://www.dns.pl/nask-epp-schema/extcon-2.1</extURI>
 <extURI>http://www.dns.pl/nask-epp-schema/extdom-2.1</extURI>
 </svcExtension>
 </svcs>
    </login>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            if ($code == 1000) {
                $this->isLoggedIn = true;
                $this->prefix = $params['prefix'];
            }

            $return = array(
                'code' => $code,
                'msg' => $r->response->result->msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * logout
     */
    public function logout($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-logout-' . $microtime);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
  <command>
    <logout/>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            if ($code == 1500) {
                $this->isLoggedIn = false;
            }

            $return = array(
                'code' => $code,
                'msg' => $r->response->result->msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * hello
     */
    public function hello()
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-hello-' . $microtime);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
   <hello/>
</epp>');
            $r = $this->writeRequest($xml);
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $r->asXML();
    }

    /**
     * hostCheck
     */
    public function hostCheck($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['hostname']);
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-host-check-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
 <command>
 <check>
 <host:check
 xmlns:host="http://www.dns.pl/nask-epp-schema/host-2.1"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/host-2.1
 host-2.1.xsd">
 <host:name>{{ name }}</host:name>
 </host:check>
 </check>
 <clTRID>{{ clTRID }}</clTRID>
 </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:host-1.0')->chkData;

            $i = 0;
            foreach ($r->cd as $cd) {
                $i++;
                $hosts[$i]['name'] = (string)$cd->name;
                $hosts[$i]['reason'] = (string)$cd->reason;
                $hosts[$i]['avail'] = (int)$cd->name->attributes()->avail;
            }

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'hosts' => $hosts
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * hostInfo
     */
    public function hostInfo($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['hostname']);
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-host-info-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
   <info>
     <host:info
      xmlns:host="urn:ietf:params:xml:ns:host-1.0">
       <host:name>{{ name }}</host:name>
     </host:info>
   </info>
   <clTRID>{{ clTRID }}</clTRID>
 </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:host-1.0')->infData[0];
            $name = (string)$r->name;
            $addr = array();
            foreach ($r->addr as $ns) {
                $addr[] = (string)$ns;
            }
            $status = array();
            $i = 0;
            foreach ($r->status as $e) {
                $i++;
                $status[$i] = (string)$e->attributes()->s;
            }
            $clID = (string)$r->clID;
            $crID = (string)$r->crID;
            $crDate = (string)$r->crDate;
            $upID = (string)$r->upID;
            $upDate = (string)$r->upDate;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'name' => $name,
                'status' => $status,
                'addr' => $addr,
                'clID' => $clID,
                'crID' => $crID,
                'crDate' => $crDate,
                'upID' => $upID,
                'upDate' => $upDate
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * hostCreate
     */
    public function hostCreate($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['hostname']);
            $from[] = '/{{ v }}/';
            $to[] = htmlspecialchars($params['v']);
            $from[] = '/{{ ip }}/';
            $to[] = htmlspecialchars($params['ip']);
            if (!empty($params['contact'])) {
                $from[] = '/{{ contact }}/';
                $to[] = htmlspecialchars($params['contact']);
            }
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-host-create-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
  <command>
	<create>
	  <host:create
 xmlns:host="http://www.dns.pl/nask-epp-schema/host-2.1"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/host-2.1
 host-2.1.xsd">
		<host:name>{{ name }}</host:name>
		<host:addr ip="{{ v }}">{{ ip }}</host:addr>
	  </host:create>
	</create>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:host-1.0')->creData;
            $name = (string)$r->name;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'name' => $name
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * hostDelete
     */
    public function hostDelete($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['hostname']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-host-delete-' . $clTRID);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
	  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	  <command>
		<delete>
		  <host:delete
		   xmlns:host="urn:ietf:params:xml:ns:host-1.0">
			<host:name>{{ name }}</host:name>
		  </host:delete>
		</delete>
		<clTRID>{{ clTRID }}</clTRID>
	  </command>
	</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * contactCheck
     */
    public function contactCheck($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ id }}/';
            $id = $params['contact'];
            $to[] = htmlspecialchars($id);
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-check-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
 <command>
 <check>
 <contact:check
 xmlns:contact="http://www.dns.pl/nask-epp-schema/contact-2.1"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/contact-2.1
 contact-2.1.xsd">
 <contact:id>{{ id }}</contact:id>
 </contact:check>
 </check>
 <clTRID>{{ clTRID }}</clTRID>
 </command>
 </epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:contact-1.0')->chkData;

            $i = 0;
            foreach ($r->cd as $cd) {
                $i++;
                $contacts[$i]['id'] = (string)$cd->id;
                $contacts[$i]['avail'] = (int)$cd->id->attributes()->avail;
                $contacts[$i]['reason'] = (string)$cd->reason;
            }

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'contacts' => $contacts
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * contactInfo
     */
    public function contactInfo($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ id }}/';
            $to[] = htmlspecialchars($params['contact']);
            $from[] = '/{{ authInfo }}/';
            $authInfo = (isset($params['authInfoPw']) ? "<contact:authInfo>\n<contact:pw><![CDATA[{$params['authInfoPw']}]]></contact:pw>\n</contact:authInfo>" : '');
            $to[] = $authInfo;
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-info-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<info>
	  <contact:info
	   xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
		<contact:id>{{ id }}</contact:id>
        {{ authInfo }}
	  </contact:info>
	</info>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:contact-1.0')->infData[0];

            foreach ($r->postalInfo as $e) {
                $name = (string)$e->name;
                $org = (string)$e->org;
                $street1 = $street2 = $street3 = '';
                for ($i = 0; $i <= 2; $i++) {
                    ${'street' . ($i + 1)} = (string)$e->addr->street[$i];
                }
                $city = (string)$e->addr->city;
                $state = (string)$e->addr->sp;
                $postal = (string)$e->addr->pc;
                $country = (string)$e->addr->cc;
            }
            $id = (string)$r->id;
            $status = array();
            $i = 0;
            foreach ($r->status as $e) {
                $i++;
                $status[$i] = (string)$e->attributes()->s;
            }
            $roid = (string)$r->roid;
            $voice = (string)$r->voice;
            $fax = (string)$r->fax;
            $email = (string)$r->email;
            $clID = (string)$r->clID;
            $crID = (string)$r->crID;
            $crDate = (string)$r->crDate;
            $upID = (string)$r->upID;
            $upDate = (string)$r->upDate;
            $authInfo = (string)$r->authInfo->pw;

            $return = array(
                'id' => $id,
                'roid' => $roid,
                'code' => $code,
                'status' => $status,
                'msg' => $msg,
                'name' => $name,
                'org' => $org,
                'street1' => $street1,
                'street2' => $street2,
                'street3' => $street3,
                'city' => $city,
                'state' => $state,
                'postal' => $postal,
                'country' => $country,
                'voice' => $voice,
                'fax' => $fax,
                'email' => $email,
                'clID' => $clID,
                'crID' => $crID,
                'crDate' => $crDate,
                'upID' => $upID,
                'upDate' => $upDate,
                'authInfo' => $authInfo
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * contactCreate
     */
    public function contactCreate($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ type }}/';
            $to[] = htmlspecialchars($params['type']);
            $from[] = '/{{ id }}/';
            $to[] = htmlspecialchars($params['id']);
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['firstname'] . ' ' . $params['lastname']);
            $from[] = '/{{ org }}/';
            $to[] = htmlspecialchars($params['companyname']);
            if (!empty($params['companyid'])) {
                $from[] = '/{{ orgid }}/';
                $to[] = htmlspecialchars($params['companyid']);
            }
            if (!empty($params['vat'])) {
                $from[] = '/{{ vat }}/';
                $to[] = htmlspecialchars($params['vat']);
            }
            $from[] = '/{{ street1 }}/';
            $to[] = htmlspecialchars($params['address1']);
            $from[] = '/{{ street2 }}/';
            $to[] = htmlspecialchars($params['address2']);
            $from[] = '/{{ street3 }}/';
            $street3 = (isset($params['address3']) ? $params['address3'] : '');
            $to[] = htmlspecialchars($street3);
            $from[] = '/{{ city }}/';
            $to[] = htmlspecialchars($params['city']);
            $from[] = '/{{ state }}/';
            $to[] = htmlspecialchars($params['state']);
            $from[] = '/{{ postcode }}/';
            $to[] = htmlspecialchars($params['postcode']);
            $from[] = '/{{ country }}/';
            $to[] = htmlspecialchars($params['country']);
            $from[] = '/{{ phonenumber }}/';
            $to[] = htmlspecialchars($params['fullphonenumber']);
            $from[] = '/{{ email }}/';
            $to[] = htmlspecialchars($params['email']);
            $from[] = '/{{ authInfo }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ extensions }}/';
            $to[] = '';
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-create-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.1" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
  <command>
	<create>
	  <contact:create
 xmlns:contact="http://www.dns.pl/nask-epp-schema/contact-2.1" xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/contact-2.1 contact-2.1.xsd">
		<contact:id>{{ id }}</contact:id>
		<contact:postalInfo type="int">
		  <contact:name>{{ name }}</contact:name>
		  <contact:org>{{ org }}</contact:org>
		  <contact:addr>
			<contact:street>{{ street1 }}</contact:street>
			<contact:street>{{ street2 }}</contact:street>
			<contact:street>{{ street3 }}</contact:street>
			<contact:city>{{ city }}</contact:city>
			<contact:sp>{{ state }}</contact:sp>
			<contact:pc>{{ postcode }}</contact:pc>
			<contact:cc>{{ country }}</contact:cc>
		  </contact:addr>
		</contact:postalInfo>
		<contact:voice>{{ phonenumber }}</contact:voice>
		<contact:fax></contact:fax>
		<contact:email>{{ email }}</contact:email>
		<contact:authInfo>
		  <contact:pw>{{ authInfo }}</contact:pw>
		</contact:authInfo>
	  </contact:create>
	</create>
<extension>
 <extcon:create xmlns:extcon="http://www.dns.pl/nask-epp-schema/extcon-2.1" xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/extcon-2.1 
  extcon-2.1.xsd">
 <extcon:individual>1</extcon:individual>
 </extcon:create>
 </extension>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:contact-1.0')->creData;
            $id = (string)$r->id;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'id' => $id
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * contactUpdate
     */
    public function contactUpdate($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ type }}/';
            $to[] = htmlspecialchars($params['type']);
            $from[] = '/{{ id }}/';
            $to[] = htmlspecialchars($params['id']);
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['firstname'] . ' ' . $params['lastname']);
            $from[] = '/{{ org }}/';
            $to[] = htmlspecialchars($params['companyname']);
            $from[] = '/{{ street1 }}/';
            $to[] = htmlspecialchars($params['address1']);
            $from[] = '/{{ street2 }}/';
            $to[] = htmlspecialchars($params['address2']);
            $from[] = '/{{ street3 }}/';
            $street3 = (isset($params['address3']) ? $params['address3'] : '');
            $to[] = htmlspecialchars($street3);
            $from[] = '/{{ city }}/';
            $to[] = htmlspecialchars($params['city']);
            $from[] = '/{{ state }}/';
            $to[] = htmlspecialchars($params['state']);
            $from[] = '/{{ postcode }}/';
            $to[] = htmlspecialchars($params['postcode']);
            $from[] = '/{{ country }}/';
            $to[] = htmlspecialchars($params['country']);
            $from[] = '/{{ voice }}/';
            $to[] = htmlspecialchars($params['fullphonenumber']);
            $from[] = '/{{ email }}/';
            $to[] = htmlspecialchars($params['email']);
            $from[] = '/{{ extensions }}/';
            $to[] = '';
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-update-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<update>
	  <contact:update xmlns:contact="urn:ietf:params:xml:ns:contact-1.0" xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">
		<contact:id>{{ id }}</contact:id>
		<contact:chg>
		  <contact:postalInfo type="{{ type }}">
			<contact:name>{{ name }}</contact:name>
			<contact:org>{{ org }}</contact:org>
			<contact:addr>
			  <contact:street>{{ street1 }}</contact:street>
			  <contact:street>{{ street2 }}</contact:street>
			  <contact:street>{{ street3 }}</contact:street>
			  <contact:city>{{ city }}</contact:city>
			  <contact:sp>{{ state }}</contact:sp>
			  <contact:pc>{{ postcode }}</contact:pc>
			  <contact:cc>{{ country }}</contact:cc>
			</contact:addr>
		  </contact:postalInfo>
		  <contact:voice>{{ voice }}</contact:voice>
		  <contact:fax></contact:fax>
		  <contact:email>{{ email }}</contact:email>
		</contact:chg>
	  </contact:update>
	</update>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * contactDelete
     */
    public function contactDelete($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ id }}/';
            $to[] = htmlspecialchars($params['contact']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-delete-' . $clTRID);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0
    epp-1.0.xsd">
 <command>
   <delete>
     <contact:delete
      xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
       <contact:id>{{ id }}</contact:id>
     </contact:delete>
   </delete>
   <clTRID>{{ clTRID }}</clTRID>
 </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainCheck
     */
    public function domainCheck($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $text = '';
            foreach ($params['domains'] as $name) {
                $text .= '<domain:name>' . $name . '</domain:name>' . "\n";
            }
            $from[] = '/{{ names }}/';
            $to[] = $text;
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-check-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
  <command>
    <check>
      <domain:check
 xmlns:domain="http://www.dns.pl/nask-epp-schema/domain-2.1"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/domain-2.1
 domain-2.1.xsd">
        {{ names }}
      </domain:check>
    </check>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $namespaces = $r->getNamespaces(true);
            $r = $r->response->resData->children($namespaces['domain'])->chkData;
            $i = 0;
            foreach ($r->cd as $cd) {
                $i++;
                $domains[$i]['name'] = (string)$cd->name;
                $domains[$i]['avail'] = $cd->name->attributes()->avail;
                $domains[$i]['reason'] = (string)$cd->reason;
            }

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'domains' => $domains
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainInfo
     */
    public function domainInfo($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ domainname }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ authInfo }}/';
            $authInfo = (isset($params['authInfoPw']) ? "<domain:authInfo>\n<domain:pw><![CDATA[{$params['authInfoPw']}]]></domain:pw>\n</domain:authInfo>" : '');
            $to[] = $authInfo;
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-info-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <info>
      <domain:info
       xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
       xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        <domain:name hosts="all">{{ domainname }}</domain:name>
        {{ authInfo }}
      </domain:info>
    </info>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
            $name = (string)$r->name;
            $roid = (string)$r->roid;
            $status = array();
            $i = 0;
            foreach ($r->status as $e) {
                $i++;
                $status[$i] = (string)$e->attributes()->s;
            }
            $registrant = (string)$r->registrant;
            $contact = array();
            $i = 0;
            foreach ($r->contact as $e) {
                $i++;
                $contact[$i]['type'] = (string)$e->attributes()->type;
                $contact[$i]['id'] = (string)$e;
            }
            $ns = array();
            $i = 0;
            foreach ($r->ns->hostObj as $hostObj) {
                $i++;
                $ns[$i] = (string)$hostObj;
            }
            $host = array();
            $i = 0;
            foreach ($r->host as $hostname) {
                $i++;
                $host[$i] = (string)$hostname;
            }
            $clID = (string)$r->clID;
            $crID = (string)$r->crID;
            $crDate = (string)$r->crDate;
            $upID = (string)$r->upID;
            $upDate = (string)$r->upDate;
            $exDate = (string)$r->exDate;
            $trDate = (string)$r->trDate;
            $authInfo = (string)$r->authInfo->pw;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'name' => $name,
                'roid' => $roid,
                'status' => $status,
                'registrant' => $registrant,
                'contact' => $contact,
                'ns' => $ns,
                'host' => $host,
                'clID' => $clID,
                'crID' => $crID,
                'crDate' => $crDate,
                'upID' => $upID,
                'upDate' => $upDate,
                'exDate' => $exDate,
                'trDate' => $trDate,
                'authInfo' => $authInfo
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainUpdateNS
     */
    public function domainUpdateNS($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-info-' . $clTRID);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
	  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	  <command>
		<info>
		  <domain:info
		   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
		   xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
			<domain:name hosts="all">{{ name }}</domain:name>
		  </domain:info>
		</info>
		<clTRID>{{ clTRID }}</clTRID>
	  </command>
	</epp>');
            $r = $this->writeRequest($xml);
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
            $add = $rem = array();
            $i = 0;
            foreach ($r->ns->hostObj as $ns) {
                $i++;
                $ns = (string)$ns;
                if (!$ns) {
                    continue;
                }

                $rem["ns{$i}"] = $ns;
            }

            foreach ($params as $k => $v) {
                if (!$v) {
                    continue;
                }

                if (!preg_match("/^ns\d$/i", $k)) {
                    continue;
                }

                if ($k0 = array_search($v, $rem)) {
                    unset($rem[$k0]);
                } else {
                    $add[$k] = $v;
                }
            }

            if (!empty($add) || !empty($rem)) {
                $from = $to = array();
                $text = '';
                foreach ($add as $k => $v) {
                    $text.= '<domain:hostObj>' . $v . '</domain:hostObj>' . "\n";
                }

                $from[] = '/{{ add }}/';
                $to[] = (empty($text) ? '' : "<domain:add><domain:ns>\n{$text}</domain:ns></domain:add>\n");
                $text = '';
                foreach ($rem as $k => $v) {
                    $text.= '<domain:hostObj>' . $v . '</domain:hostObj>' . "\n";
                }

                $from[] = '/{{ rem }}/';
                $to[] = (empty($text) ? '' : "<domain:rem><domain:ns>\n{$text}</domain:ns></domain:rem>\n");
                $from[] = '/{{ name }}/';
                $to[] = htmlspecialchars($params['domainname']);
                $from[] = '/{{ clTRID }}/';
                $clTRID = str_replace('.', '', round(microtime(1), 3));
                $to[] = htmlspecialchars($this->prefix . '-domain-updateNS-' . $clTRID);
                $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
	  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	  <command>
		<update>
		  <domain:update
		   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
		   xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
			<domain:name>{{ name }}</domain:name>
		{{ add }}
		{{ rem }}
		  </domain:update>
		</update>
		<clTRID>{{ clTRID }}</clTRID>
	  </command>
	</epp>');
                $r = $this->writeRequest($xml);
                $code = (int)$r->response->result->attributes()->code;
                $msg = (string)$r->response->result->msg;

                $return = array(
                    'code' => $code,
                    'msg' => $msg
                );
            }
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainUpdateContact
     */
    public function domainUpdateContact($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ add }}/';
            $to[] = "<domain:add><domain:contact type=\"admin\">XXX</domain:contact><domain:contact type=\"tech\">XXX</domain:contact></domain:add>\n";
            /* 			$from[] = '/{{ rem }}/';
                        $to[] = "<domain:rem><domain:contact type=\"admin\">XXX</domain:contact><domain:contact type=\"tech\">XXX</domain:contact></domain:rem>\n"; */
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-updateContactGR-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
	  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	  <command>
		<update>
		  <domain:update
		   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
		   xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
			<domain:name>{{ name }}</domain:name>
	
		{{ add }}
		  </domain:update>
		</update>
		<clTRID>{{ clTRID }}</clTRID>
	  </command>
	</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainTransfer
     */
    public function domainTransfer($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ years }}/';
            $to[] = (int)($params['years']);
            $from[] = '/{{ authInfoPw }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-transfer-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<transfer op="request">
	  <domain:transfer
	   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
		<domain:name>{{ name }}</domain:name>
		<domain:period unit="y">{{ years }}</domain:period>
		<domain:authInfo>
		  <domain:pw>{{ authInfoPw }}</domain:pw>
		</domain:authInfo>
	  </domain:transfer>
	</transfer>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->trnData;
            $name = (string)$r->name;
            $trStatus = (string)$r->trStatus;
            $reID = (string)$r->reID;
            $reDate = (string)$r->reDate;
            $acID = (string)$r->acID;
            $acDate = (string)$r->acDate;
            $exDate = (string)$r->exDate;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'name' => $name,
                'trStatus' => $trStatus,
                'reID' => $reID,
                'reDate' => $reDate,
                'acID' => $acID,
                'acDate' => $acDate,
                'exDate' => $exDate
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainCreate
     */
    public function domainCreate($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ period }}/';
            $to[] = (int)($params['period']);
            $text = '';
            foreach ($params['nss'] as $hostObj) {
                $text .= '<domain:ns>' . $hostObj . '</domain:ns>' . "\n";
            }
            $from[] = '/{{ hostObjs }}/';
            $to[] = $text;
            $from[] = '/{{ registrant }}/';
            $to[] = htmlspecialchars($params['registrant']);
            $text = '';
            foreach ($params['contacts'] as $id => $contactType) {
                $text .= '<domain:contact type="' . $contactType . '">' . $id . '</domain:contact>' . "\n";
            }
            $from[] = '/{{ contacts }}/';
            $to[] = $text;
            $from[] = '/{{ authInfoPw }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-create-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="http://www.dns.pl/nask-epp-schema/epp-2.1"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/epp-2.1
 epp-2.1.xsd">
  <command>
    <create>
       <domain:create
 xmlns:domain="http://www.dns.pl/nask-epp-schema/domain-2.1"
 xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/domain-2.1
 domain-2.1.xsd">
        <domain:name>{{ name }}</domain:name>
        <domain:period unit="y">{{ period }}</domain:period>
          {{ hostObjs }}
        <domain:registrant>{{ registrant }}</domain:registrant>
        {{ contacts }}
        <domain:authInfo>
          <domain:pw>{{ authInfoPw }}</domain:pw>
        </domain:authInfo>
      </domain:create>
    </create>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->creData;
            $name = (string)$r->name;
            $crDate = (string)$r->crDate;
            $exDate = (string)$r->exDate;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'name' => $name,
                'crDate' => $crDate,
                'exDate' => $exDate
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainRenew
     */
    public function domainRenew($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-renew-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<info>
	  <domain:info
	   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
	   xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
		<domain:name hosts="all">{{ name }}</domain:name>
	  </domain:info>
	</info>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
            $expDate = (string)$r->exDate;
            $expDate = preg_replace("/^(\d+\-\d+\-\d+)\D.*$/", "$1", $expDate);
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ regperiod }}/';
            $to[] = htmlspecialchars($params['regperiod']);
            $from[] = '/{{ expDate }}/';
            $to[] = htmlspecialchars($expDate);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-renew-' . $clTRID);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<renew>
	  <domain:renew
	   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
		<domain:name>{{ name }}</domain:name>
		<domain:curExpDate>{{ expDate }}</domain:curExpDate>
		<domain:period unit="y">{{ regperiod }}</domain:period>
	  </domain:renew>
	</renew>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->renData;
            $name = (string)$r->name;
            $exDate = (string)$r->exDate;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'name' => $name,
                'exDate' => $exDate
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainDelete
     */
    public function domainDelete($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-delete-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<delete>
	  <domain:delete
	   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
		<domain:name>{{ name }}</domain:name>
	  </domain:delete>
	</delete>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainRestore
     */
    public function domainRestore($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-restore-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
   <update>
	 <domain:update xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
	   <domain:name>{{ name }}</domain:name>
	   <domain:chg/>
	 </domain:update>
   </update>
   <extension>
	 <rgp:update xmlns:rgp="urn:ietf:params:xml:ns:rgp-1.0">
	   <rgp:restore op="request"/>
	 </rgp:update>
   </extension>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainReport
     */
    public function domainReport($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['domainname']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-report-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0
		epp-1.0.xsd">
	 <command>
	   <update>
		 <domain:update
		  xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
		  xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0
		  domain-1.0.xsd">
		   <domain:name>{{ name }}</domain:name>
		   <domain:chg/>
		 </domain:update>
	   </update>
	   <extension>
		 <rgp:update xmlns:rgp="urn:ietf:params:xml:ns:rgp-1.0"
		  xsi:schemaLocation="urn:ietf:params:xml:ns:rgp-1.0
		  rgp-1.0.xsd">
		   <rgp:restore op="report">
			 <rgp:report>
			   <rgp:preData>Pre-delete registration data goes here.
			   Both XML and free text are allowed.</rgp:preData>
			   <rgp:postData>Post-restore registration data goes here.
			   Both XML and free text are allowed.</rgp:postData>
			   <rgp:delTime>2019-10-10T22:00:00.0Z</rgp:delTime>
			   <rgp:resTime>2019-10-20T22:00:00.0Z</rgp:resTime>
			   <rgp:resReason>Registrant error.</rgp:resReason>
			   <rgp:statement>This registrar has not restored the
			   Registered Name in order to assume the rights to use
			   or sell the Registered Name for itself or for any
			   third party.</rgp:statement>
			   <rgp:statement>The information in this report is
			   true to best of this registrars knowledge, and this
			   registrar acknowledges that intentionally supplying
			   false information in this report shall constitute an
			   incurable material breach of the
			   Registry-Registrar Agreement.</rgp:statement>
			   <rgp:other>Supporting information goes
			   here.</rgp:other>
			 </rgp:report>
		   </rgp:restore>
		 </rgp:update>
	   </extension>
	<clTRID>{{ clTRID }}</clTRID>
	 </command>
   </epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * pollReq
     */
    public function pollReq()
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-poll-req-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <poll op="req"/>
       <clTRID>{{ clTRID }}</clTRID>
     </command>
   </epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $messages = (int)$r->response->msgQ->attributes()->count;
            $last_id = (int)$r->response->msgQ->attributes()->id;
            $qDate = (string)$r->response->msgQ->qDate;
            $last_msg = (string)$r->response->msgQ->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'messages' => $messages,
                'last_id' => $last_id,
                'qDate' => $qDate,
                'last_msg' => $last_msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * pollAck
     */
    public function pollAck($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ message }}/';
            $to[] = htmlspecialchars($params['msgID']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-poll-ack-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
   <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
     <command>
       <poll op="ack" msgID="{{ message }}"/>
       <clTRID>{{ clTRID }}</clTRID>
     </command>
   </epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
            );
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    public function _response_log($content)
    {
        $handle = fopen(dirname(__FILE__) . '/../../log/response.log', 'a');
        ob_start();
        echo "\n==================================\n";
        ob_end_clean();
        fwrite($handle, $content);
        fclose($handle);
    }

    public function _request_log($content)
    {
        $handle = fopen(dirname(__FILE__) . '/../../log/request.log', 'a');
        ob_start();
        echo "\n==================================\n";
        ob_end_clean();
        fwrite($handle, $content);
        fclose($handle);
    }
}