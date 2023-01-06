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
use Pinga\Tembo\EppClient;
use Pinga\Tembo\HttpsClient;
use Pinga\Tembo\Exception\EppException;
use Pinga\Tembo\Exception\EppNotConnectedException;
 
class Epp
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
            }

            catch(\ErrorException $e) {
                throw new EppException($e->getMessage());
            }

            if (!is_resource($this->resource)) {
                throw new EppException('An error occured while trying to connect to EPP server.');
            }

            $result = null;
        }
        elseif (!is_resource($this->resource)) {
            throw new EppNotConnectedException();
        }
        else {
            array_unshift($args, $this->resource);
            try {
                $result = call_user_func_array($func, $args);
            }

            catch(\ErrorException $e) {
                throw new EppException($e->getMessage());
            }
        }

        return $result;
    }

    /**
     * login
     */
    function login($params = array())
    {
        $return = array();
        try {
            $from = $to = array();
            $from[] = '/{{ clID }}/';
            $to[] = htmlspecialchars($params['clID']);
            $from[] = '/{{ pwd }}/';
            $to[] = htmlspecialchars($params['pw']);
			if ($params['ext'] == 'iis.se') {
            $from[] = '/{{ extensions }}/';
            $to[] = '<extURI>urn:se:iis:xml:epp:iis-1.2</extURI>';
			} else {
            $from[] = '/{{ extensions }}/';
            $to[] = '';
			}
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($params['prefix'] . '-login-' . $microtime);
			if ($params['ext'] == 'nask') {
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
			} else {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <login>
      <clID>{{ clID }}</clID>
      <pw>{{ pwd }}</pw>
      <options>
        <version>1.0</version>
        <lang>en</lang>
      </options>
      <svcs>
        <objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>
        <objURI>urn:ietf:params:xml:ns:contact-1.0</objURI>
        <objURI>urn:ietf:params:xml:ns:host-1.0</objURI>
        <svcExtension>
          <extURI>urn:ietf:params:xml:ns:secDNS-1.1</extURI>
		  {{ extensions }}
        </svcExtension>
      </svcs>
    </login>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			}
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * logout
     */
    function logout($params = array())
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
			if ($params['ext'] == 'nask') {
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
			} else {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <logout/>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            if ($code == 1500) {
                $this->isLoggedIn = false;
            }

            $return = array(
                'code' => $code,
                'msg' => $r->response->result->msg
            );
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
	
    /**
     * hostCreate
     */
    function hostCreate($params = array())
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
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-host-create-' . $clTRID);
			$from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
			$to[] = '';
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<create>
	  <host:create
	   xmlns:host="urn:ietf:params:xml:ns:host-1.0">
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
	
    /**
     * contactCreate
     */
    function contactCreate($params = array())
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
			$from[] = '/{{ email }}/';
			$to[] = htmlspecialchars($params['email']);
            $from[] = '/{{ authInfo }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ extensions }}/';
            $to[] = '<extension>
 <extcon:create xmlns:extcon="http://www.dns.pl/nask-epp-schema/extcon-2.1" xsi:schemaLocation="http://www.dns.pl/nask-epp-schema/extcon-2.1 
  extcon-2.1.xsd">
 <extcon:individual>1</extcon:individual>
 </extcon:create>
 </extension>';
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-create-' . $microtime);	
			$from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
			$to[] = '';
			if ($params['ext'] == 'nask') {
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
	{{ extensions }}
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<create>
	  <contact:create
	   xmlns:contact="urn:ietf:params:xml:ns:contact-1.0">
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
	{{ extensions }}
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			}
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
	
    /**
     * contactCreateIIS
     */
    function contactCreateIIS($params = array())
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
			$from[] = '/{{ email }}/';
			$to[] = htmlspecialchars($params['email']);
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-create-' . $microtime);	
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
		<contact:postalInfo type="loc">
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
	  </contact:create>
	</create>
	<extension>
	  <iis:create xmlns:iis="urn:se:iis:xml:epp:iis-1.2"
	  xsi:schemaLocation="urn:se:iis:xml:epp:iis-1.2 iis-1.2.xsd">
	    <iis:orgno>[{{ country }}]{{ orgno }}</iis:orgno>
	    <iis:vatno>{{ vatno }}</iis:vatno>
	  </iis:create>
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainCheck
     */
    function domainCheck($params = array())
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
			if ($params['ext'] == 'nask') {
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
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($params['ext'] == 'nask') {
			$namespaces = $r->getNamespaces(true);
			$r = $r->response->resData->children($namespaces['domain'])->chkData;
            $i = 0;
            foreach($r->cd as $cd) {
                $i++;
                $domains[$i]['name'] = (string)$cd->name;
                $domains[$i]['avail'] = $cd->name->attributes()->avail;
                $domains[$i]['reason'] = (string)$cd->reason;
            }
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->chkData;
            $i = 0;
            foreach($r->cd as $cd) {
                $i++;
                $domains[$i]['name'] = (string)$cd->name;
                $domains[$i]['avail'] = (int)$cd->name->attributes()->avail;
                $domains[$i]['reason'] = (string)$cd->reason;
            }
			}

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'domains' => $domains
            );
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainInfo
     */
    function domainInfo($params = array())
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
            foreach($r->status as $e) {
                $i++;
                $status[$i] = (string)$e->attributes()->s;
            }
            $registrant = (string)$r->registrant;
            $contact = array();
            $i = 0;
            foreach($r->contact as $e) {
                $i++;
                $contact[$i]['type'] = (string)$e->attributes()->type;
                $contact[$i]['id'] = (string)$e;
            }
            $ns = array();
            $i = 0;
            foreach($r->ns->hostObj as $hostObj) {
                $i++;
                $ns[$i] = (string)$hostObj;
            }
            $host = array();
            $i = 0;
            foreach($r->host as $hostname) {
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainTransfer
     */
    function domainTransfer($params = array())
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
                'exDate' => $exDate,
                'exDate' => $exDate
            );
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

    /**
     * domainCreate
     */
    function domainCreate($params = array())
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
			if ($params['ext'] == 'nask') {
            $text = '';
            foreach ($params['nss'] as $hostObj) {
                $text .= '<domain:ns>' . $hostObj . '</domain:ns>' . "\n";
            }
            $from[] = '/{{ hostObjs }}/';
            $to[] = $text;
			} else {
            $text = '';
            foreach ($params['nss'] as $hostObj) {
                $text .= '<domain:hostObj>' . $hostObj . '</domain:hostObj>' . "\n";
            }
            $from[] = '/{{ hostObjs }}/';
            $to[] = $text;
			}
            $from[] = '/{{ registrant }}/';
            $to[] = htmlspecialchars($params['registrant']);
			if ($params['ext'] == 'iis.se') {
            $from[] = '/{{ contacts }}/';
            $to[] = '';
			} else {
            $text = '';
            foreach ($params['contacts'] as $id => $contactType) {
                $text .= '<domain:contact type="' . $contactType . '">' . $id . '</domain:contact>' . "\n";
            }
            $from[] = '/{{ contacts }}/';
            $to[] = $text;
			}
            $from[] = '/{{ authInfoPw }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-create-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
			if ($params['ext'] == 'nask') {
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
			} else {
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
          {{ hostObjs }}
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
			}
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
	
    /**
     * domainRenew
     */
    function domainRenew($params = array())
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }

function _response_log($content)
{
    $handle = fopen(dirname(__FILE__) . '/response.log', 'a');
    ob_start();
    echo "\n==================================\n";
    ob_end_clean();
    fwrite($handle, $content);
    fclose($handle);
}

function _request_log($content)
{
    $handle = fopen(dirname(__FILE__) . '/request.log', 'a');
    ob_start();
    echo "\n==================================\n";
    ob_end_clean();
    fwrite($handle, $content);
    fclose($handle);
}    

}
