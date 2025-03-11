<?php
/**
 * Tembo EPP client library
 *
 * Written in 2024 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

namespace Pinga\Tembo\Registries;

use Pinga\Tembo\EppRegistryInterface;
use Pinga\Tembo\Exception\EppException;
use Pinga\Tembo\Exception\EppNotConnectedException;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class ItEpp implements EppRegistryInterface
{
    private $resource;
    private $isLoggedIn;
    private $prefix;

    public function __construct()
    {
        if (!extension_loaded('SimpleXML')) {
            throw new \Exception('PHP extension SimpleXML is not loaded.');
        }

        // Create the loggers
        $this->responseLogger = new Logger('Response');
        $this->requestLogger = new Logger('Request');
        $this->commonLogger = new Logger('Tembo');

        // Define the line format
        $lineFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $dateFormat = "Y-m-d H:i:s"; // Customize the date format if needed

        // Create a LineFormatter instance
        $formatter = new LineFormatter($lineFormat, $dateFormat);

        // Create handlers - The second parameter is the max number of files to keep (0 means unlimited)
        // The third parameter is the log level
        $responseHandler = new RotatingFileHandler(dirname(__FILE__) . '/../../log/response-it.log', 0, Logger::DEBUG);
        $requestHandler = new RotatingFileHandler(dirname(__FILE__) . '/../../log/request-it.log', 0, Logger::DEBUG);
        $commonHandler = new RotatingFileHandler(dirname(__FILE__) . '/../../log/common-it.log', 0, Logger::DEBUG);

        // Set the formatter to the handlers
        $responseHandler->setFormatter($formatter);
        $requestHandler->setFormatter($formatter);
        $commonHandler->setFormatter($formatter);

        // Push handlers to the loggers
        $this->responseLogger->pushHandler($responseHandler);
        $this->requestLogger->pushHandler($requestHandler);
        $this->commonLogger->pushHandler($commonHandler);
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
            $to[] = $params['pw'];
            if (isset($params['newpw']) && !empty($params['newpw'])) {
            $from[] = '/{{ newpw }}/';
            $to[] = PHP_EOL . '      <newPW>'. $params['newpw'] .'</newPW>';
            } else {
            $from[] = '/{{ newpw }}/';
            $to[] = '';
            }
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($params['prefix'] . '-login-' . $microtime);
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <login>
      <clID>{{ clID }}</clID>
      <pw>{{ pwd }}</pw>{{ newpw }}
      <options>
        <version>1.0</version>
        <lang>en</lang>
      </options>
      <svcs>
        <objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>
        <objURI>urn:ietf:params:xml:ns:contact-1.0</objURI>
        <svcExtension>
          <extURI>http://www.nic.it/ITNIC-EPP/extepp-2.0</extURI>
          <extURI>http://www.nic.it/ITNIC-EPP/extcon-1.0</extURI>
          <extURI>http://www.nic.it/ITNIC-EPP/extdom-2.0</extURI>
          <extURI>urn:ietf:params:xml:ns:rgp-1.0</extURI>
          <extURI>urn:ietf:params:xml:ns:secDNS-1.1</extURI>
          <extURI>http://www.nic.it/ITNIC-EPP/extsecDNS-1.0</extURI> 
        </svcExtension>
      </svcs>
    </login>
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
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
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

        throw new EppException("Hosts not supported!");
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

        throw new EppException("Hosts not supported!");
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

        throw new EppException("Hosts not supported!");
    }
    
    /**
     * hostUpdate
     */
    public function hostUpdate($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        throw new EppException("Hosts not supported!");
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

        throw new EppException("Hosts not supported!");
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
            $text = '';
            foreach ($params['contact'] as $id) {
                $text .= '<contact:id>' . htmlspecialchars($id) . '</contact:id>' . "\n";
            }
            $from[] = '/{{ ids }}/';
            $to[] = $text;
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-check-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <check>
      <contact:check
        xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
        xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">
        {{ ids }}
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
                $availStr = (string)$cd->id->attributes()->avail;
                $contacts[$i]['avail'] = ($availStr === 'true' || $availStr === '1') ? true : false;
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
            $from[] = '/{{ fax }}/';
            $to[] = htmlspecialchars($params['fax']);
            $from[] = '/{{ email }}/';
            $to[] = htmlspecialchars($params['email']);
            $from[] = '/{{ authInfo }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ consentForPublishing }}/';
            $to[] = htmlspecialchars($params['consentForPublishing']);
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-create-' . $microtime);

            $registrantXml = '';
            if (!empty($params['nationalityCode']) && !empty($params['entityType']) && !empty($params['regCode'])) {
                $registrantXml = '
                <extcon:registrant>
                  <extcon:nationalityCode>' . htmlspecialchars($params['nationalityCode']) . '</extcon:nationalityCode>
                  <extcon:entityType>' . htmlspecialchars($params['entityType']) . '</extcon:entityType>
                  <extcon:regCode>' . htmlspecialchars($params['regCode']) . '</extcon:regCode>
                </extcon:registrant>';
            }
            $from[] = '/{{ registrant }}/';
            $to[] = $registrantXml;

            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <create>
      <contact:create
       xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
        <contact:id>{{ id }}</contact:id>
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
        <contact:voice>{{ phonenumber }}</contact:voice>
        <contact:fax>{{ fax }}</contact:fax>
        <contact:email>{{ email }}</contact:email>
        <contact:authInfo>
          <contact:pw>{{ authInfo }}</contact:pw>
        </contact:authInfo>
      </contact:create>
    </create>
    <extension>
      <extcon:create xmlns:extcon="http://www.nic.it/ITNIC-EPP/extcon-1.0"
       xsi:schemaLocation="http://www.nic.it/ITNIC-EPP/extcon-1.0 extcon1.0.xsd">
        <extcon:consentForPublishing>{{ consentForPublishing }}</extcon:consentForPublishing>
        {{ registrant }}
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
            $from[] = '/{{ fax }}/';
            $to[] = htmlspecialchars($params['fax']);
            $from[] = '/{{ email }}/';
            $to[] = htmlspecialchars($params['email']);
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-update-' . $microtime);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            
            $extensionBlock = '';
            if (isset($params['consentForPublishing']) && $params['consentForPublishing'] !== '') {
                $consentValue = htmlspecialchars($params['consentForPublishing']);
                $extensionBlock = '
        <extension>
          <extcon:update xmlns:extcon="http://www.nic.it/ITNIC-EPP/extcon-1.0"
           xsi:schemaLocation="http://www.nic.it/ITNIC-EPP/extcon-1.0 extcon1.0.xsd">
            <extcon:consentForPublishing>' . $consentValue . '</extcon:consentForPublishing>
          </extcon:update>
        </extension>';
            }
            $from[] = '/{{ extension }}/';
            $to[] = $extensionBlock;

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
          <contact:fax>{{ fax }}</contact:fax>
          <contact:email>{{ email }}</contact:email>
        </contact:chg>
      </contact:update>
    </update>
    {{ extension }}
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
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <check>
      <domain:check
        xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
        xsi:schemaLocation="urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd">
        {{ names }}
      </domain:check>
    </check>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->chkData;

            $i = 0;
            foreach ($r->cd as $cd) {
                $i++;
                $domains[$i]['name'] = (string)$cd->name;
                $availStr = (string)$cd->name->attributes()->avail;
                $domains[$i]['avail'] = ($availStr === 'true' || $availStr === '1') ? true : false;
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
     * domainCheckClaims
     */
    public function domainCheckClaims($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        throw new EppException("Launch extension not supported!");
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
            if (!empty($params['authInfoPw'])) {
                $from[] = '/{{ authInfo }}/';
                $authInfo = "<domain:authInfo>\n<domain:pw>{$params['authInfoPw']}</domain:pw>\n</domain:authInfo>";
                $to[] = $authInfo;
            } else {
                $from[] = '/{{ authInfo }}/';
                $to[] = '';
            }
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
            foreach ($r->ns->hostAttr as $hostAttr) {
                $i++;
                $ns[$i] = (string)$hostAttr->hostName;
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
            // Step 1: Fetch current nameservers via domain info
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

            // Step 2: Parse existing nameservers
            $currentNs = array();
            foreach ($r->ns->hostAttr as $hostAttr) {
                $hostName = (string)$hostAttr->hostName;

                // Initialize IPv4 and IPv6 as empty
                $ipv4 = '';
                $ipv6 = '';

                // Parse <domain:hostAddr> elements
                foreach ($hostAttr->hostAddr as $hostAddr) {
                    $ipType = (string)$hostAddr->attributes()->ip; // Get the 'ip' attribute (v4 or v6)
                    if ($ipType === 'v4') {
                        $ipv4 = (string)$hostAddr;
                    } elseif ($ipType === 'v6') {
                        $ipv6 = (string)$hostAddr;
                    }
                }

                // Add to the current nameservers list
                $currentNs[$hostName] = array_filter([
                    'hostName' => $hostName,
                    'ipv4' => $ipv4,
                    'ipv6' => $ipv6
                ]);
            }

            // Step 3: Determine changes (additions, removals)
            $add = $rem = array();
            foreach ($params['nss'] as $ns) {
                if (is_array($ns)) {
                    $hostName = $ns['hostName'];
                    $ipv4 = $ns['ipv4'] ?? '';
                    $ipv6 = $ns['ipv6'] ?? '';
                    $nsKey = $hostName . ($ipv4 ? "|v4:$ipv4" : '') . ($ipv6 ? "|v6:$ipv6" : '');

                    if (!isset($currentNs[$hostName]) || $currentNs[$hostName] != $ns) {
                        $add[$nsKey] = $ns;
                    }
                } else {
                    // Handle simple hostObj case
                    if (!isset($currentNs[$ns])) {
                        $add[$ns] = ['hostName' => $ns];
                    }
                }
            }

            foreach ($currentNs as $hostName => $nsData) {
                if (!in_array($hostName, array_column($params['nss'], 'hostName'))) {
                    $rem[$hostName] = $nsData;
                }
            }

            // Step 4: Generate update XML
            if (!empty($add) || !empty($rem)) {
                $from = $to = array();
                $addXml = '';
                foreach ($add as $ns) {
                    $addXml .= '<domain:hostAttr>';
                    $addXml .= '<domain:hostName>' . htmlspecialchars($ns['hostName']) . '</domain:hostName>';
                    if (!empty($ns['ipv4'])) {
                        $addXml .= '<domain:hostAddr ip="v4">' . htmlspecialchars($ns['ipv4']) . '</domain:hostAddr>';
                    }
                    if (!empty($ns['ipv6'])) {
                        $addXml .= '<domain:hostAddr ip="v6">' . htmlspecialchars($ns['ipv6']) . '</domain:hostAddr>';
                    }
                    $addXml .= '</domain:hostAttr>' . "\n";
                }

                $from[] = '/{{ add }}/';
                $to[] = (empty($addXml) ? '' : "<domain:add><domain:ns>\n{$addXml}</domain:ns></domain:add>\n");

                $remXml = '';
                foreach ($rem as $ns) {
                    $remXml .= '<domain:hostAttr>';
                    $remXml .= '<domain:hostName>' . htmlspecialchars($ns['hostName']) . '</domain:hostName>';
                    if (!empty($ns['ipv4'])) {
                        $remXml .= '<domain:hostAddr ip="v4">' . htmlspecialchars($ns['ipv4']) . '</domain:hostAddr>';
                    }
                    if (!empty($ns['ipv6'])) {
                        $remXml .= '<domain:hostAddr ip="v6">' . htmlspecialchars($ns['ipv6']) . '</domain:hostAddr>';
                    }
                    $remXml .= '</domain:hostAttr>' . "\n";
                }

                $from[] = '/{{ rem }}/';
                $to[] = (empty($remXml) ? '' : "<domain:rem><domain:ns>\n{$remXml}</domain:ns></domain:rem>\n");
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
            if ($params['contacttype'] === 'registrant') {
            $from[] = '/{{ add }}/';
            $to[] = "";
            $from[] = '/{{ rem }}/';
            $to[] = "";
            $from[] = '/{{ chg }}/';
            $to[] = "<domain:chg><domain:registrant>".htmlspecialchars($params['new_contactid'])."</domain:registrant></domain:chg>\n";
            } else {
            $from[] = '/{{ add }}/';
            $to[] = "<domain:add><domain:contact type=\"".htmlspecialchars($params['contacttype'])."\">".htmlspecialchars($params['new_contactid'])."</domain:contact></domain:add>\n";
            $from[] = '/{{ rem }}/';
            $to[] = "<domain:rem><domain:contact type=\"".htmlspecialchars($params['contacttype'])."\">".htmlspecialchars($params['old_contactid'])."</domain:contact></domain:rem>\n";
            $from[] = '/{{ chg }}/';
            $to[] = "";    
            }
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-updateContact-' . $clTRID);
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
       {{ rem }}
       {{ chg }}
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
     * domainUpdateStatus
     */
    public function domainUpdateStatus($params = array())
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
            if ($params['command'] === 'add') {
            $from[] = '/{{ add }}/';
            $to[] = "<domain:add><domain:status s=\"".htmlspecialchars($params['status'])."\"/></domain:add>\n";
            $from[] = '/{{ rem }}/';
            $to[] = "";    
            } else if ($params['command'] === 'rem') {
            $from[] = '/{{ add }}/';
            $to[] = "";    
            $from[] = '/{{ rem }}/';
            $to[] = "<domain:rem><domain:status s=\"".htmlspecialchars($params['status'])."\"/></domain:rem>\n";
            }
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-updateStatus-' . $clTRID);
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
        } catch (\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
    
    /**
     * domainUpdateAuthinfo
     */
    public function domainUpdateAuthinfo($params = array())
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
            $from[] = '/{{ authInfo }}/';
            $to[] = htmlspecialchars($params['authInfo']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-updateAuthinfo-' . $clTRID);
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
       <domain:chg>
         <domain:authInfo>
           <domain:pw>{{ authInfo }}</domain:pw>
         </domain:authInfo>
       </domain:chg>
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
     * domainUpdateDNSSEC
     */
    public function domainUpdateDNSSEC($params = array())
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
            if ($params['command'] == 'add') {
                $from[] = '/{{ add }}/';
                $to[] = "<secDNS:add>
                <secDNS:dsData>
            <secDNS:keyTag>".htmlspecialchars($params['keyTag_1'])."</secDNS:keyTag>
            <secDNS:alg>".htmlspecialchars($params['alg_1'])."</secDNS:alg>
            <secDNS:digestType>".htmlspecialchars($params['digestType_1'])."</secDNS:digestType>
            <secDNS:digest>".htmlspecialchars($params['digest_1'])."</secDNS:digest>
          </secDNS:dsData>
          </secDNS:add>";
                $from[] = '/{{ rem }}/';
                $to[] = "";
                $from[] = '/{{ addrem }}/';
                $to[] = "";
            } else if ($params['command'] == 'rem') {
                $from[] = '/{{ add }}/';
                $to[] = "";
                $from[] = '/{{ rem }}/';
                $to[] = "<secDNS:rem>
                <secDNS:dsData>
            <secDNS:keyTag>".htmlspecialchars($params['keyTag_1'])."</secDNS:keyTag>
            <secDNS:alg>".htmlspecialchars($params['alg_1'])."</secDNS:alg>
            <secDNS:digestType>".htmlspecialchars($params['digestType_1'])."</secDNS:digestType>
            <secDNS:digest>".htmlspecialchars($params['digest_1'])."</secDNS:digest>
          </secDNS:dsData>
          </secDNS:rem>";
                $from[] = '/{{ addrem }}/';
                $to[] = "";
            } else if ($params['command'] == 'addrem') {
                $from[] = '/{{ add }}/';
                $to[] = "";
                $from[] = '/{{ rem }}/';
                $to[] = "";
                $from[] = '/{{ addrem }}/';
                $to[] = "<secDNS:rem>
                <secDNS:dsData>
            <secDNS:keyTag>".htmlspecialchars($params['keyTag_1'])."</secDNS:keyTag>
            <secDNS:alg>".htmlspecialchars($params['alg_1'])."</secDNS:alg>
            <secDNS:digestType>".htmlspecialchars($params['digestType_1'])."</secDNS:digestType>
            <secDNS:digest>".htmlspecialchars($params['digest_1'])."</secDNS:digest>
          </secDNS:dsData>
          </secDNS:rem>
          <secDNS:add>
          <secDNS:dsData>
            <secDNS:keyTag>".htmlspecialchars($params['keyTag_2'])."</secDNS:keyTag>
            <secDNS:alg>".htmlspecialchars($params['alg_2'])."</secDNS:alg>
            <secDNS:digestType>".htmlspecialchars($params['digestType_2'])."</secDNS:digestType>
            <secDNS:digest>".htmlspecialchars($params['digest_2'])."</secDNS:digest>
          </secDNS:dsData>
          </secDNS:add>";
            }
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-updateDNSSEC-' . $clTRID);
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
     </domain:update>
   </update>
<extension>
      <secDNS:update
        xmlns:secDNS="urn:ietf:params:xml:ns:secDNS-1.1"
        xsi:schemaLocation="urn:ietf:params:xml:ns:secDNS-1.1 secDNS-1.1.xsd">
        {{ add }}
        {{ rem }}
        {{ addrem }}
      </secDNS:update>
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
            switch (htmlspecialchars($params['op'])) {
                case 'request':
                    $from[] = '/{{ authInfoPw }}/';
                    $to[] = htmlspecialchars($params['authInfoPw']);
                    $xmltype = 'req';
                    break;
                case 'query':
                    $from[] = '/{{ type }}/';
                    $to[] = 'query';
                    $xmltype = 'oth';
                    $from[] = '/{{ authInfoPw }}/';
                    $to[] = htmlspecialchars($params['authInfoPw']);
                    break;
                case 'cancel':
                    $from[] = '/{{ type }}/';
                    $to[] = 'cancel';
                    $xmltype = 'oth';
                    $from[] = '/{{ authInfoPw }}/';
                    $to[] = htmlspecialchars($params['authInfoPw']);
                    break;
                case 'reject':
                    $from[] = '/{{ type }}/';
                    $to[] = 'reject';
                    $xmltype = 'oth';
                    $from[] = '/{{ authInfoPw }}/';
                    $to[] = htmlspecialchars($params['authInfoPw']);
                    break;
                case 'approve':
                    $xmltype = 'apr';
                    $from[] = '/{{ authInfoPw }}/';
                    $to[] = htmlspecialchars($params['authInfoPw']);
                    break;
                default:
                    throw new EppException('Invalid value for transfer:op specified.');
                    break;
            }
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-transfer-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';

            $extension = '';
            if (!empty($params['newRegistrant']) && !empty($params['newAuthInfo'])) {
                $extension = '
        <extension>
          <extdom:trade xmlns:extdom="http://www.nic.it/ITNIC-EPP/extdom-2.0"
           xsi:schemaLocation="http://www.nic.it/ITNIC-EPP/extdom-2.0 extdom-2.0.xsd">
            <extdom:transferTrade>
              <extdom:newRegistrant>' . htmlspecialchars($params['newRegistrant']) . '</extdom:newRegistrant>
              <extdom:newAuthInfo>
                <extdom:pw>' . htmlspecialchars($params['newAuthInfo']) . '</extdom:pw>
              </extdom:newAuthInfo>
            </extdom:transferTrade>
          </extdom:trade>
        </extension>';
            }
            $from[] = '/{{ extension }}/';
            $to[] = $extension;

            if ($xmltype === 'req') {
                $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
              <command>
                <transfer op="request">
                  <domain:transfer
                   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                    <domain:name>{{ name }}</domain:name>
                    <domain:authInfo>
                      <domain:pw>{{ authInfoPw }}</domain:pw>
                    </domain:authInfo>
                  </domain:transfer>
                </transfer>
                {{ extension }}
                <clTRID>{{ clTRID }}</clTRID>
              </command>
            </epp>');
            } else if ($xmltype === 'apr') {
                $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
              <command>
                <transfer op="approve">
                  <domain:transfer
                   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                    <domain:name>{{ name }}</domain:name>
                    <domain:authInfo>
                      <domain:pw>{{ authInfoPw }}</domain:pw>
                    </domain:authInfo>
                  </domain:transfer>
                </transfer>
                <clTRID>{{ clTRID }}</clTRID>
              </command>
            </epp>');
            } else if ($xmltype === 'oth') {
                $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
            <epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
              <command>
                <transfer op="{{ type }}">
                  <domain:transfer
                   xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                    <domain:name>{{ name }}</domain:name>
                    <domain:authInfo>
                      <domain:pw>{{ authInfoPw }}</domain:pw>
                    </domain:authInfo>
                  </domain:transfer>
                </transfer>
                <clTRID>{{ clTRID }}</clTRID>
              </command>
            </epp>');
            }
            
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->trnData;
            $name = (string)($r->name ?? 'N/A');
            $trStatus = (string)($r->trStatus ?? 'N/A');
            $reID = (string)($r->reID ?? 'N/A');
            $reDate = (string)($r->reDate ?? 'N/A');
            $acID = (string)($r->acID ?? 'N/A');
            $acDate = (string)($r->acDate ?? 'N/A');
            $exDate = (string)($r->exDate ?? 'N/A');

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
            if (isset($params['nss'])) {
                $text = '';
                foreach ($params['nss'] as $hostAttr) {
                    $text .= '<domain:hostAttr>';
                    $text .= '<domain:hostName>' . htmlspecialchars($hostAttr['hostName']) . '</domain:hostName>';
                    if (!empty($hostAttr['ipv4'])) {
                        $text .= '<domain:hostAddr ip="v4">' . htmlspecialchars($hostAttr['ipv4']) . '</domain:hostAddr>';
                    }
                    if (!empty($hostAttr['ipv6'])) {
                        $text .= '<domain:hostAddr ip="v6">' . htmlspecialchars($hostAttr['ipv6']) . '</domain:hostAddr>';
                    }
                    
                    $text .= '</domain:hostAttr>' . "\n";
                }
                $from[] = '/{{ hostAttr }}/';
                $to[] = $text;
            } else {
                $from[] = '/{{ hostAttr }}/';
                $to[] = '';
            }
            $from[] = '/{{ registrant }}/';
            $to[] = htmlspecialchars($params['registrant']);
            $text = '';
            foreach ($params['contacts'] as $contactType => $contactID) {
                $text .= '<domain:contact type="' . $contactType . '">' . $contactID . '</domain:contact>' . "\n";
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
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <create>
      <domain:create
       xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
        <domain:name>{{ name }}</domain:name>
        <domain:period unit="y">{{ period }}</domain:period>
        <domain:ns>
          {{ hostAttr }}
        </domain:ns>
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
     * domainCreateClaims
     */
    public function domainCreateClaims($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        throw new EppException("Launch extension not supported!");
    }
    
    /**
     * domainCreateSunrise
     */
    public function domainCreateSunrise($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        throw new EppException("Launch extension not supported!");
    }
    
    /**
     * domainCreateDNSSEC
     */
    public function domainCreateDNSSEC($params = array())
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
            if (isset($params['nss'])) {
                $text = '';
                foreach ($params['nss'] as $hostAttr) {
                    $text .= '<domain:hostAttr>';
                    $text .= '<domain:hostName>' . htmlspecialchars($hostAttr['hostName']) . '</domain:hostName>';
                    if (!empty($hostAttr['ipv4'])) {
                        $text .= '<domain:hostAddr ip="v4">' . htmlspecialchars($hostAttr['ipv4']) . '</domain:hostAddr>';
                    }
                    if (!empty($hostAttr['ipv6'])) {
                        $text .= '<domain:hostAddr ip="v6">' . htmlspecialchars($hostAttr['ipv6']) . '</domain:hostAddr>';
                    }
                    
                    $text .= '</domain:hostAttr>' . "\n";
                }
                $from[] = '/{{ hostAttr }}/';
                $to[] = $text;
            } else {
                $from[] = '/{{ hostAttr }}/';
                $to[] = '';
            }
            $from[] = '/{{ registrant }}/';
            $to[] = htmlspecialchars($params['registrant']);
            $text = '';
            foreach ($params['contacts'] as $id => $contactType) {
                $text .= '<domain:contact type="' . $contactType . '">' . $id . '</domain:contact>' . "\n";
            }
            $from[] = '/{{ contacts }}/';
            $to[] = $text;
            if ($params['dnssec_records'] == 1) {
                $from[] = '/{{ dnssec_data }}/';
                $to[] = "<secDNS:dsData>
                    <secDNS:keyTag>".htmlspecialchars($params['keyTag_1'])."</secDNS:keyTag>
                    <secDNS:alg>".htmlspecialchars($params['alg_1'])."</secDNS:alg>
                    <secDNS:digestType>".htmlspecialchars($params['digestType_1'])."</secDNS:digestType>
                    <secDNS:digest>".htmlspecialchars($params['digest_1'])."</secDNS:digest>
                  </secDNS:dsData>";
            } else if ($params['dnssec_records'] == 2) {
                $from[] = '/{{ dnssec_data }}/';
                $to[] = "<secDNS:dsData>
                    <secDNS:keyTag>".htmlspecialchars($params['keyTag_1'])."</secDNS:keyTag>
                    <secDNS:alg>".htmlspecialchars($params['alg_1'])."</secDNS:alg>
                    <secDNS:digestType>".htmlspecialchars($params['digestType_1'])."</secDNS:digestType>
                    <secDNS:digest>".htmlspecialchars($params['digest_1'])."</secDNS:digest>
                  </secDNS:dsData>
                  <secDNS:dsData>
                    <secDNS:keyTag>".htmlspecialchars($params['keyTag_2'])."</secDNS:keyTag>
                    <secDNS:alg>".htmlspecialchars($params['alg_2'])."</secDNS:alg>
                    <secDNS:digestType>".htmlspecialchars($params['digestType_2'])."</secDNS:digestType>
                    <secDNS:digest>".htmlspecialchars($params['digest_2'])."</secDNS:digest>
                  </secDNS:dsData>";
            }
            $from[] = '/{{ authInfoPw }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-createDNSSEC-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <create>
      <domain:create
       xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
        <domain:name>{{ name }}</domain:name>
        <domain:period unit="y">{{ period }}</domain:period>
        <domain:ns>
          {{ hostAttr }}
        </domain:ns>
        <domain:registrant>{{ registrant }}</domain:registrant>
        {{ contacts }}
        <domain:authInfo>
          <domain:pw>{{ authInfoPw }}</domain:pw>
        </domain:authInfo>
      </domain:create>
    </create>
    <extension>
      <secDNS:create xmlns:secDNS="urn:ietf:params:xml:ns:secDNS-1.1">
        {{ dnssec_data }}
      </secDNS:create>
    </extension>
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

        throw new EppException("Manual domain renew not supported!");
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

        throw new EppException("RGP report not supported!");
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
        $messages = (int)($r->response->msgQ->attributes()->count ?? 0);
        $last_id = (int)($r->response->msgQ->attributes()->id ?? 0);
        $qDate = (string)($r->response->msgQ->qDate ?? '');
        $last_msg = (string)($r->response->msgQ->msg ?? '');

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

    /**
     * rawXml
     */
    public function rawXml($params = array())
    {
        if (!$this->isLoggedIn) {
            return array(
                'code' => 2002,
                'msg' => 'Command use error'
            );
        }

        $return = array();
        try {
            $r = $this->writeRequest($params['xml']);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'xml' => $r->asXML()
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
        // Add formatted content to the log
        $this->responseLogger->info($content);
        $this->commonLogger->info($content);
    }

    public function _request_log($content)
    {
        // Add formatted content to the log
        $this->requestLogger->info($content);
        $this->commonLogger->info($content);
    }
}
