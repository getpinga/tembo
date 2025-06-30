<?php
/**
 * Tembo EPP client library
 *
 * Written in 2024-2025 by Taras Kondratyuk (https://getpinga.com)
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

class LvEpp extends Epp
{
    protected function addLoginObjects(\XMLWriter $xml): void
    {
        $xml->writeElement('objURI', 'urn:ietf:params:xml:ns:domain-1.0');
        $xml->writeElement('objURI', 'urn:ietf:params:xml:ns:contact-1.0');
    }

    protected function addLoginExtensions(\XMLWriter $xml): void
    {
        $xml->startElement('svcExtension');
        $xml->writeElement('extURI', 'http://www.nic.lv/epp/schema/lvdomain-ext-1.0');
        $xml->writeElement('extURI', 'http://www.nic.lv/epp/schema/lvcontact-ext-1.0');
        $xml->endElement(); // svcExtension
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
            $org = isset($params['companyname']) && $params['companyname'] !== '' ? htmlspecialchars($params['companyname']) : '';

            $from[] = '/{{ org }}/';
            $to[] = $org;
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
            $from[] = '/{{ clTRID }}/';
            $microtime = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-contact-create-' . $microtime);
            
            $vatNr = isset($params['vatNr']) && $params['vatNr'] !== '' ? '<lvcontact:vatNr>' . htmlspecialchars($params['vatNr']) . '</lvcontact:vatNr>' : '';
            $regNr = isset($params['regNr']) && $params['regNr'] !== '' ? '<lvcontact:regNr>' . htmlspecialchars($params['regNr']) . '</lvcontact:regNr>' : '';

            $from[] = '/{{ vatNr }}/';
            $to[] = $vatNr;
            $from[] = '/{{ regNr }}/';
            $to[] = $regNr;

            $from[] = "/<lvcontact:create[^>]*>\s*<\/lvcontact:create>/";
            $to[] = '';

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
        <contact:email>{{ email }}</contact:email>
        <contact:authInfo>
          <contact:pw>{{ authInfo }}</contact:pw>
        </contact:authInfo>
      </contact:create>
    </create>
    <extension>
      <lvcontact:create xmlns:lvcontact="http://www.nic.lv/epp/schema/lvcontact-ext-1.0">
        {{ vatNr }}
        {{ regNr }}
      </lvcontact:create>
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

  throw new EppException("Claims extension not supported!");
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
            $to[] = "<lvdomain:add><lvdomain:status s=\"".htmlspecialchars($params['status'])."\" lang=\"en\">Sample reason here.</lvdomain:status></lvdomain:add>\n";
            $from[] = '/{{ rem }}/';
            $to[] = "";    
            } else if ($params['command'] === 'rem') {
            $from[] = '/{{ add }}/';
            $to[] = "";    
            $from[] = '/{{ rem }}/';
            $to[] = "<lvdomain:rem><lvdomain:status s=\"".htmlspecialchars($params['status'])."\" lang=\"en\">Sample reason here.</lvdomain:status></lvdomain:rem>\n";
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

     </domain:update>
   </update>
    <extension>
      <lvdomain:update xmlns:lvdomain="http://www.nic.lv/epp/schema/lvdomain-ext-1.0">
       {{ add }}
       {{ rem }}
      </lvdomain:update>
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
        <domain:period unit="y">1</domain:period>
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
            foreach ($params['contacts'] as $contactType => $contactID) {
                $text .= '<domain:contact type="' . $contactType . '">' . $contactID . '</domain:contact>' . "\n";
            }
            $from[] = '/{{ contacts }}/';
            $to[] = $text;
            $from[] = '/{{ authInfoPw }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
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

        throw new EppException("Claims extension not supported!");
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

        throw new EppException("Domain renew not supported!");
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
