<?php
/**
 * Tembo EPP client library
 *
 * Written in 2023-2025 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

namespace Pinga\Tembo\Registries;

use Pinga\Tembo\Epp;
use Pinga\Tembo\EppRegistryInterface;
use Pinga\Tembo\Exception\EppException;
use Pinga\Tembo\Exception\EppNotConnectedException;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class GrEpp extends Epp
{
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

    protected function addLoginExtensions(\XMLWriter $xml): void
    {
        $xml->startElement('svcExtension');
        $xml->writeElement('extURI', 'urn:ietf:params:xml:ns:secDNS-1.1');
        $xml->endElement(); // svcExtension
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
            $from[] = '/{{ ext }}/';
            $to[] = "<extension><extdomain:update xsi:schemaLocation=\"urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd\" xmlns:extdomain=\"urn:ics-forth:params:xml:ns:extdomain-1.2\">
<extdomain:op>ownerNameChange</extdomain:op>
</extdomain:update>
</extension>";    
            } else {
            $from[] = '/{{ add }}/';
            $to[] = "<domain:add><domain:contact type=\"".htmlspecialchars($params['contacttype'])."\">".htmlspecialchars($params['new_contactid'])."</domain:contact></domain:add>\n";
            $from[] = '/{{ rem }}/';
            $to[] = "<domain:rem><domain:contact type=\"".htmlspecialchars($params['contacttype'])."\">".htmlspecialchars($params['old_contactid'])."</domain:contact></domain:rem>\n";
            $from[] = '/{{ chg }}/';
            $to[] = "";    
            $from[] = '/{{ ext }}/';
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
   {{ ext }}
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

        throw new EppException("Status update not supported!");
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
                    $from[] = '/{{ years }}/';
                    $to[] = (int)($params['years']);
                    $from[] = '/{{ authInfoPw }}/';
                    $to[] = htmlspecialchars($params['authInfoPw']);
                    $from[] = '/{{ registrant }}/';
                    $to[] = htmlspecialchars($params['registrant']);
                    $from[] = '/{{ new_authInfoPw }}/';
                    $to[] = htmlspecialchars($params['new_authInfoPw']);
                    $xmltype = 'req';
                    break;
                case 'query':
                    $from[] = '/{{ type }}/';
                    $to[] = 'query';
                    $xmltype = 'oth';
                    break;
                case 'cancel':
                    $from[] = '/{{ type }}/';
                    $to[] = 'cancel';
                    $xmltype = 'oth';
                    break;
                case 'reject':
                    $from[] = '/{{ type }}/';
                    $to[] = 'reject';
                    $xmltype = 'oth';
                    break;
                case 'approve':
                    $xmltype = 'apr';
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
                    <domain:period unit="y">{{ years }}</domain:period>
                    <domain:authInfo>
                      <domain:pw>{{ authInfoPw }}</domain:pw>
                    </domain:authInfo>
                  </domain:transfer>
                </transfer>
                <extension>
                  <extdomain:transfer xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2" xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd">
                    <extdomain:registrantid>{{ registrant }}</extdomain:registrantid>
                    <extdomain:newPW>{{ new_authInfoPw }}</extdomain:newPW>
                  </extdomain:transfer>
                </extension>
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
                foreach ($params['nss'] as $hostObj) {
                    $text .= '<domain:hostObj>' . $hostObj . '</domain:hostObj>' . "\n";
                }
                $from[] = '/{{ hostObjs }}/';
                $to[] = $text;
            } else {
                $from[] = '/{{ hostObjs }}/';
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
          {{ hostObjs }}
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
            $from[] = '/{{ password }}/';
            $to[] = htmlspecialchars($params['password']);
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
<extension>
<extdomain:delete xmlns:extdomain="urn:ics-forth:params:xml:ns:extdomain-1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:ics-forth:params:xml:ns:extdomain-1.2 extdomain-1.2.xsd">
<extdomain:pw>{{ password }}</extdomain:pw>
<extdomain:op>deleteDomain</extdomain:op>
</extdomain:delete>
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

        throw new EppException("RGP extension not supported!");
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

        throw new EppException("RGP extension not supported!");
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
