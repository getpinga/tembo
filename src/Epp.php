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
//use Pinga\Tembo\HttpsClient;
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
			} else if ($params['ext'] == 'ua') {
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
         <objURI>urn:ietf:params:xml:ns:epp-1.0</objURI>
         <objURI>http://hostmaster.ua/epp/contact-1.1</objURI>
         <objURI>http://hostmaster.ua/epp/domain-1.1</objURI>
         <objURI>http://hostmaster.ua/epp/host-1.1</objURI>
         <svcExtension>
           <extURI>http://hostmaster.ua/epp/rgp-1.1</extURI>
           <extURI>http://hostmaster.ua/epp/uaepp-1.1</extURI>
           <extURI>http://hostmaster.ua/epp/balance-1.0</extURI>
           <extURI>http://hostmaster.ua/epp/secDNS-1.1</extURI>
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'nask') {
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
     * hostCheck
     */
    function hostCheck($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<check>
	  <host:check
		xmlns:host="http://hostmaster.ua/epp/host-1.1">
		<host:name>{{ name }}</host:name>
	  </host:check>
	</check>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="utf-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
   <command>
      <check>
         <nsset:check xmlns:nsset="http://www.nic.cz/xml/epp/nsset-1.2"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/nsset-1.2 nsset-1.2.2.xsd">
            <nsset:id>{{ name }}</nsset:id>
         </nsset:check>
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
	  <host:check
		xmlns:host="urn:ietf:params:xml:ns:host-1.0"
		xsi:schemaLocation="urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd">
		<host:name>{{ name }}</host:name>
	  </host:check>
	</check>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/host-1.1')->chkData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/nsset-1.2')->chkData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:host-1.0')->chkData;
			}
			
			if ($ext == 'fred') {
            $i = 0;
            foreach($r->cd as $cd) {
                $i++;
                $hosts[$i]['id'] = (string)$cd->id;
                $hosts[$i]['reason'] = (string)$cd->reason;
                $hosts[$i]['avail'] = (int)$cd->id->attributes()->avail;
            }
			} else {
            foreach($r->cd as $cd) {
                $i++;
                $hosts[$i]['name'] = (string)$cd->name;
                $hosts[$i]['reason'] = (string)$cd->reason;
                $hosts[$i]['avail'] = (int)$cd->name->attributes()->avail;
            }
			}

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'hosts' => $hosts
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
     * hostInfo
     */
    function hostInfo($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'fred') {
            $from[] = '/{{ authInfo }}/';
            $authInfo = (isset($params['authInfoPw']) ? "<nsset:authInfo><![CDATA[{$params['authInfoPw']}]]></nsset:authInfo>" : '');
            $to[] = $authInfo;
			}			
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-host-info-' . $microtime);
			$from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
			$to[] = '';

			if ($ext == 'ua') {
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
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="utf-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
<command>
   <info>
      <nsset:info xmlns:nsset="http://www.nic.cz/xml/epp/nsset-1.2"
       xsi:schemaLocation="http://www.nic.cz/xml/epp/nsset-1.2 nsset-1.2.2.xsd">
         <nsset:id>{{ name }}</nsset:id>
         {{ authInfo }}
      </nsset:info>
   </info>
   <clTRID>{{ clTRID }}</clTRID>
</command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
	if ($ext == 'ua') {
	$r = $r->response->resData->children('http://hostmaster.ua/epp/host-1.1')->infData[0];
	$name = (string)$r->name;
	$addr = array();
	foreach($r->addr as $ns) {
	   $addr[] = (string)$ns;
	    }
            $status = array();
            $i = 0;
            foreach($r->status as $e) {
                $i++;
                $status[$i] = (string)$e->attributes()->s;
            }
			} else if ($ext == 'fred') {
			$r = $r->response->resData->children('http://www.nic.cz/xml/epp/nsset-1.2')->infData[0];
			$name = (string)$r->id;
			$addr = array();
			foreach ($r->ns as $ns) {
				$addr[] = (string)$ns->name;
			}
            $status = array();
            $i = 0;
            foreach($r->status as $e) {
                $i++;
                $status[$i] = (string)$e->attributes()->s;
            }
			} else {
			$r = $r->response->resData->children('urn:ietf:params:xml:ns:host-1.0')->infData[0];
			$name = (string)$r->name;
			$addr = array();
			foreach($r->addr as $ns) {
				$addr[] = (string)$ns;
			}
            $status = array();
            $i = 0;
            foreach($r->status as $e) {
                $i++;
                $status[$i] = (string)$e->attributes()->s;
            }
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
	    $ext = isset($params['ext']) ? $params['ext'] : '';
	    if ($ext == 'fred') {      
            $from[] = '/{{ name2 }}/';
            $to[] = htmlspecialchars($params['hostname2']);
            $from[] = '/{{ ip2 }}/';
            $to[] = htmlspecialchars($params['ip2']);
            $from[] = '/{{ nsid }}/';
            $to[] = htmlspecialchars($params['nsid']);
            $from[] = '/{{ nstech }}/';
            $to[] = htmlspecialchars($params['nstech']);
	    }
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-host-create-' . $clTRID);
	    $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
	    if ($ext == 'ua') {
	    $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<create>
	  <host:create xmlns:host="http://hostmaster.ua/epp/host-1.1">
		<host:name>{{ name }}</host:name>
		<host:addr ip="{{ v }}">{{ ip }}</host:addr>
	  </host:create>
	</create>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="utf-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
   <command>
      <create>
         <nsset:create xmlns:nsset="http://www.nic.cz/xml/epp/nsset-1.2"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/nsset-1.2 nsset-1.2.2.xsd">
            <nsset:id>{{ nsid }}</nsset:id>
            <nsset:ns>
               <nsset:name>{{ name }}</nsset:name>
               <nsset:addr>{{ ip }}</nsset:addr>
            </nsset:ns>
            <nsset:ns>
               <nsset:name>{{ name2 }}</nsset:name>
               <nsset:addr>{{ ip2 }}</nsset:addr>
            </nsset:ns>
            <nsset:tech>{{ nstech }}</nsset:tech>
            <nsset:reportlevel>0</nsset:reportlevel>
         </nsset:create>
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
	  <host:create
	   xmlns:host="urn:ietf:params:xml:ns:host-1.0">
		<host:name>{{ name }}</host:name>
		<host:addr ip="{{ v }}">{{ ip }}</host:addr>
	  </host:create>
	</create>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/host-1.1')->creData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/nsset-1.2')->creData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:host-1.0')->creData;
			}
			
			if ($ext == 'fred') {
            $name = (string)$r->id;
			} else {
            $name = (string)$r->name;
			}

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
     * hostDelete
     */
    function hostDelete($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
	  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	  <command>
		<delete>
		  <host:delete xmlns:host="http://hostmaster.ua/epp/host-1.1">
			<host:name>{{ name }}</host:name>
		  </host:delete>
		</delete>
		<clTRID>{{ clTRID }}</clTRID>
	  </command>
	</epp>');
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="utf-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
   <command>
   <delete>
      <nsset:delete xmlns:nsset="http://www.nic.cz/xml/epp/nsset-1.2"
       xsi:schemaLocation="http://www.nic.cz/xml/epp/nsset-1.2 nsset-1.2.2.xsd">
         <nsset:id>{{ name }}</nsset:id>
      </nsset:delete>
   </delete>
   <clTRID>{{ clTRID }}</clTRID>
   </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
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
     * contactCheck
     */
    function contactCheck($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<check>
	  <contact:check
        xmlns:contact="http://hostmaster.ua/epp/contact-1.1">
		<contact:id>{{ id }}</contact:id>
	  </contact:check>
	</check>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<check>
	  <contact:check xmlns:contact="http://www.nic.cz/xml/epp/contact-1.6"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/contact-1.6 contact-1.6.2.xsd">
		<contact:id>{{ id }}</contact:id>
	  </contact:check>
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
	  <contact:check
		xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
		xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">
		<contact:id>{{ id }}</contact:id>
	  </contact:check>
	</check>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/contact-1.1')->chkData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/contact-1.6')->chkData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:contact-1.0')->chkData;
			}
            $i = 0;
            foreach($r->cd as $cd) {
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
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
	
    /**
     * contactInfo
     */
    function contactInfo($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<info>
	  <contact:info
	   xmlns:contact="http://hostmaster.ua/epp/contact-1.1">
		<contact:id>{{ id }}</contact:id>
        {{ authInfo }}
	  </contact:info>
	</info>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<info>
	  <contact:info xmlns:contact="http://www.nic.cz/xml/epp/contact-1.6"
    xsi:schemaLocation="http://www.nic.cz/xml/epp/contact-1.6 contact-1.6.2.xsd">
		<contact:id>{{ id }}</contact:id>
        {{ authInfo }}
	  </contact:info>
	</info>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($ext == 'ua') {
			$r = $r->response->resData->children('http://hostmaster.ua/epp/contact-1.1')->infData[0];
			} else if ($ext == 'fred') {
			$r = $r->response->resData->children('http://www.nic.cz/xml/epp/contact-1.6')->infData[0];
			} else {
			$r = $r->response->resData->children('urn:ietf:params:xml:ns:contact-1.0')->infData[0];
			}
			foreach($r->postalInfo as $e) {
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
            foreach($r->status as $e) {
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'nask') {
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
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<create>
	  <contact:create xmlns:contact="http://www.nic.cz/xml/epp/contact-1.6"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/contact-1.6 contact-1.6.2.xsd">
		<contact:id>{{ id }}</contact:id>
		<contact:postalInfo>
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
	{{ extensions }}
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<create>
	  <contact:create
	   xmlns:contact="http://hostmaster.ua/epp/contact-1.1">
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
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/contact-1.1')->creData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/contact-1.6')->creData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:contact-1.0')->creData;
			}
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
            $to[] = htmlspecialchars($this->prefix . '-contact-createIIS-' . $microtime);	
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
     * contactUpdate
     */
    function contactUpdate($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<update>
	  <contact:update xmlns:contact="http://hostmaster.ua/epp/contact-1.1">
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
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<update>
	  <contact:update xmlns:contact="http://www.nic.cz/xml/epp/contact-1.6"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/contact-1.6 contact-1.6.2.xsd">
		<contact:id>{{ id }}</contact:id>
		<contact:chg>
		  <contact:postalInfo>
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
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
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
     * contactDelete
     */
    function contactDelete($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0
    epp-1.0.xsd">
 <command>
   <delete>
     <contact:delete xmlns:contact="http://hostmaster.ua/epp/contact-1.1">
       <contact:id>{{ id }}</contact:id>
     </contact:delete>
   </delete>
   <clTRID>{{ clTRID }}</clTRID>
 </command>
</epp>');
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0
    epp-1.0.xsd">
 <command>
   <delete>
     <contact:delete
       xmlns:contact="http://www.nic.cz/xml/epp/contact-1.6"
       xsi:schemaLocation="http://www.nic.cz/xml/epp/contact-1.6 contact-1.6.2.xsd">
       <contact:id>{{ id }}</contact:id>
     </contact:delete>
   </delete>
   <clTRID>{{ clTRID }}</clTRID>
 </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'nask') {
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
			} else if ($ext == 'ua') {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <check>
      <domain:check
        xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
        {{ names }}
      </domain:check>
    </check>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <check>
      <domain:check xmlns:domain="http://www.nic.cz/xml/epp/domain-1.4"
       xsi:schemaLocation="http://www.nic.cz/xml/epp/domain-1.4 domain-1.4.2.xsd">
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'nask') {
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
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/domain-1.1')->chkData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/domain-1.4')->chkData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->chkData;
			}
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'fred') {
            $authInfo = (isset($params['authInfoPw']) ? "<domain:authInfo><![CDATA[{$params['authInfoPw']}]]></domain:authInfo>" : '');
			} else {
            $authInfo = (isset($params['authInfoPw']) ? "<domain:authInfo>\n<domain:pw><![CDATA[{$params['authInfoPw']}]]></domain:pw>\n</domain:authInfo>" : '');
			}
            $to[] = $authInfo;
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-info-' . $microtime);
			$from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
			$to[] = '';
			if ($ext == 'ua') {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <info>
      <domain:info xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
        <domain:name>{{ domainname }}</domain:name>
        {{ authInfo }}
      </domain:info>
    </info>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <info>
      <domain:info xmlns:domain="http://www.nic.cz/xml/epp/domain-1.4"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/domain-1.4 domain-1.4.2.xsd">
        <domain:name>{{ domainname }}</domain:name>
        {{ authInfo }}
      </domain:info>
    </info>
    <clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/domain-1.1')->infData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/domain-1.4')->infData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
			}
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
     * domainUpdateNS
     */
    function domainUpdateNS($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
	  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	  <command>
		<info>
		  <domain:info xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
			<domain:name hosts="all">{{ name }}</domain:name>
		  </domain:info>
		</info>
		<clTRID>{{ clTRID }}</clTRID>
	  </command>
	</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
			if ($ext == 'ua') {
			$r = $r->response->resData->children('http://hostmaster.ua/epp/domain-1.1')->infData;
			} else {
			$r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
			}
			$add = $rem = array();
			$i = 0;
			foreach($r->ns->hostObj as $ns) {
				$i++;
				$ns = (string)$ns;
				if (!$ns) {
					continue;
				}

				$rem["ns{$i}"] = $ns;
			}

			foreach($params as $k => $v) {
				if (!$v) {
					continue;
				}

				if (!preg_match("/^ns\d$/i", $k)) {
					continue;
				}

				if ($k0 = array_search($v, $rem)) {
					unset($rem[$k0]);
				}
				else {
					$add[$k] = $v;
				}
			}

			if (!empty($add) || !empty($rem)) {
				$from = $to = array();
				$text = '';
				foreach($add as $k => $v) {
					$text.= '<domain:hostObj>' . $v . '</domain:hostObj>' . "\n";
				}

				$from[] = '/{{ add }}/';
				$to[] = (empty($text) ? '' : "<domain:add><domain:ns>\n{$text}</domain:ns></domain:add>\n");
				$text = '';
				foreach($rem as $k => $v) {
					$text.= '<domain:hostObj>' . $v . '</domain:hostObj>' . "\n";
				}

				$from[] = '/{{ rem }}/';
				$to[] = (empty($text) ? '' : "<domain:rem><domain:ns>\n{$text}</domain:ns></domain:rem>\n");
				$from[] = '/{{ name }}/';
				$to[] = htmlspecialchars($params['domainname']);
				$from[] = '/{{ clTRID }}/';
				$clTRID = str_replace('.', '', round(microtime(1), 3));
				$to[] = htmlspecialchars($this->prefix . '-domain-updateNS-' . $clTRID);
				$ext = isset($params['ext']) ? $params['ext'] : '';
				if ($ext == 'ua') {
				$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
	<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
	  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
	  <command>
		<update>
		  <domain:update
         xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
			<domain:name>{{ name }}</domain:name>
		{{ add }}
		{{ rem }}
		  </domain:update>
		</update>
		<clTRID>{{ clTRID }}</clTRID>
	  </command>
	</epp>');
				} else {
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
				}
				$r = $this->writeRequest($xml);
				$code = (int)$r->response->result->attributes()->code;
				$msg = (string)$r->response->result->msg;

				$return = array(
					'code' => $code,
					'msg' => $msg
				);
			}
		}
		
        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
	
    /**
     * domainUpdateContactGR
     */
    function domainUpdateContactGR($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
		    $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<transfer op="request">
	  <domain:transfer 
         xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
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
			} else if ($ext == 'fred') {
		    $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<transfer op="request">
	  <domain:transfer xmlns:domain="http://www.nic.cz/xml/epp/domain-1.4"
       xsi:schemaLocation="http://www.nic.cz/xml/epp/domain-1.4 domain-1.4.2.xsd">
		<domain:name>{{ name }}</domain:name>
		<domain:authInfo>{{ authInfoPw }}</domain:authInfo>
	  </domain:transfer>
	</transfer>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/domain-1.1')->trnData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->trnData;
			}
			
			if ($ext == 'fred') {
            $return = array(
                'code' => $code,
                'msg' => $msg
            );
			} else {
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
			}
        }

        catch(\Exception $e) {
            $return = array(
                'error' => $e->getMessage()
            );
        }

        return $return;
    }
	
    /**
     * domainTransferGR
     */
    function domainTransferGR($params = array())
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
            $from[] = '/{{ authInfoPw }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-transferGR-' . $clTRID);
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
		<domain:authInfo>
		  <domain:pw>{{ authInfoPw }}</domain:pw>
		</domain:authInfo>
	  </domain:transfer>
	</transfer>
	<extension> <extdomain:transfer xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2" xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd"> <extdomain:registrantid>XXX</extdomain:registrantid> <extdomain:newPW>XXX</extdomain:newPW> </extdomain:transfer> </extension>
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
			} else if ($params['ext'] == 'fred') {
            $from[] = '/{{ nsid }}/';
            $to[] = htmlspecialchars($params['nsid']);
			} else {
				if (isset($params['nss'])) {
				$text = '';
				foreach ($params['nss'] as $hostObj) {
					$text .= '<domain:hostObj>' . $hostObj . '</domain:hostObj>' . "\n";
				}
				$from[] = '/{{ hostObjs }}/';
				$to[] = $text;
				} else {
				$from[] = '/{{ hostObjs }}/';
				$to[] = '';
				}
			}
            $from[] = '/{{ registrant }}/';
            $to[] = htmlspecialchars($params['registrant']);
			if ($params['ext'] == 'iis.se') {
            $from[] = '/{{ contacts }}/';
            $to[] = '';
			} else if ($params['ext'] == 'fred') {
            $from[] = '/{{ admin }}/';
            $to[] = htmlspecialchars($params['admin']);
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'nask') {
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
			} else if ($ext == 'ua') {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
    <create>
      <domain:create xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
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
			} else if ($ext == 'fred') {
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="utf-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
   <command>
      <create>
         <domain:create xmlns:domain="http://www.nic.cz/xml/epp/domain-1.4"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/domain-1.4 domain-1.4.2.xsd">
            <domain:name>{{ name }}</domain:name>
            <domain:period unit="y">{{ period }}</domain:period>
            <domain:nsset>{{ nsid }}</domain:nsset>
            <domain:registrant>{{ registrant }}</domain:registrant>
            <domain:admin>{{ admin }}</domain:admin>
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
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/domain-1.1')->creData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/domain-1.4')->creData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->creData;
			}
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
            $to[] = htmlspecialchars($this->prefix . '-domain-renew-' . $clTRID);
			$from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
			$to[] = '';
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
		    $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<info>
	  <domain:info xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
		<domain:name>{{ name }}</domain:name>
	  </domain:info>
	</info>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
		    $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<info>
	  <domain:info xmlns:domain="http://www.nic.cz/xml/epp/domain-1.4"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/domain-1.4 domain-1.4.2.xsd">
		<domain:name>{{ name }}</domain:name>
	  </domain:info>
	</info>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/domain-1.1')->infData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/domain-1.4')->infData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->infData;
			}
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
			if ($ext == 'ua') {
		    $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<renew>
	  <domain:renew xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
		<domain:name>{{ name }}</domain:name>
		<domain:curExpDate>{{ expDate }}</domain:curExpDate>
		<domain:period unit="y">{{ regperiod }}</domain:period>
	  </domain:renew>
	</renew>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
		    $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<renew>
	  <domain:renew xmlns:domain="http://www.nic.cz/xml/epp/domain-1.4"
          xsi:schemaLocation="http://www.nic.cz/xml/epp/domain-1.4 domain-1.4.2.xsd">
		<domain:name>{{ name }}</domain:name>
		<domain:curExpDate>{{ expDate }}</domain:curExpDate>
		<domain:period unit="y">{{ regperiod }}</domain:period>
	  </domain:renew>
	</renew>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;
			if ($ext == 'ua') {
            $r = $r->response->resData->children('http://hostmaster.ua/epp/domain-1.1')->renData;
			} else if ($ext == 'fred') {
            $r = $r->response->resData->children('http://www.nic.cz/xml/epp/domain-1.4')->renData;
			} else {
            $r = $r->response->resData->children('urn:ietf:params:xml:ns:domain-1.0')->renData;
			}
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
	
    /**
     * domainRenewTransferGR
     */
    function domainRenewTransferGR($params = array())
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
            $to[] = htmlspecialchars($this->prefix . '-domain-renewTransferGR-' . $clTRID);
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
	<extension> <extdomain:renew xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd" xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2"> <extdomain:registrantid>XXX</extdomain:registrantid> <extdomain:currentPW>XXX</extdomain:currentPW> <extdomain:newPW>XXX</extdomain:newPW> </extdomain:renew> </extension>
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
	
    /**
     * domainDelete
     */
    function domainDelete($params = array())
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
			$ext = isset($params['ext']) ? $params['ext'] : '';
			if ($ext == 'ua') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<delete>
	  <domain:delete xmlns:domain="http://hostmaster.ua/epp/domain-1.1">
		<domain:name>{{ name }}</domain:name>
	  </domain:delete>
	</delete>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else if ($ext == 'fred') {
			$xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd">
  <command>
	<delete>
	  <domain:delete xmlns:domain="http://www.nic.cz/xml/epp/domain-1.4"
       xsi:schemaLocation="http://www.nic.cz/xml/epp/domain-1.4 domain-1.4.2.xsd">
		<domain:name>{{ name }}</domain:name>
	  </domain:delete>
	</delete>
	<clTRID>{{ clTRID }}</clTRID>
  </command>
</epp>');
			} else {
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
			}
            $r = $this->writeRequest($xml);
            $code = (int)$r->response->result->attributes()->code;
            $msg = (string)$r->response->result->msg;

            $return = array(
                'code' => $code,
                'msg' => $msg
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
     * domainRestore
     */
    function domainRestore($params = array())
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
	    $ext = isset($params['ext']) ? $params['ext'] : '';
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
    $handle = fopen(dirname(__FILE__) . '/../log/response.log', 'a');
    ob_start();
    echo "\n==================================\n";
    ob_end_clean();
    fwrite($handle, $content);
    fclose($handle);
}

function _request_log($content)
{
    $handle = fopen(dirname(__FILE__) . '/../log/request.log', 'a');
    ob_start();
    echo "\n==================================\n";
    ob_end_clean();
    fwrite($handle, $content);
    fclose($handle);
}    

}
