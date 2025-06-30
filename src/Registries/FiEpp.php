<?php
/**
 * Namingo EPP client library
 *
 * Written in 2024-2025 by Taras Kondratyuk (https://namingo.org)
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

class FiEpp extends Epp
{
    protected function addLoginExtensions(\XMLWriter $xml): void
    {
        $xml->startElement('svcExtension');
        $xml->writeElement('extURI', 'urn:ietf:params:xml:ns:secDNS-1.1');
        $xml->writeElement('extURI', 'urn:ietf:params:xml:ns:domain-ext-1.0');
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
            $from[] = '/{{ role }}/';
            $to[] = htmlspecialchars($params['role']);
            $from[] = '/{{ isfinnish }}/';
            $to[] = htmlspecialchars($params['isfinnish']);
            $from[] = '/{{ id }}/';
            $to[] = htmlspecialchars($params['id']);
            $from[] = '/{{ name }}/';
            $to[] = htmlspecialchars($params['firstname'] . ' ' . $params['lastname']);
            $from[] = '/{{ org }}/';
            $to[] = htmlspecialchars($params['companyname']);
            $from[] = '/{{ registernumber }}/';
            $to[] = htmlspecialchars($params['registernumber']);
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
        <contact:role>{{ role }}</contact:role>
        <contact:type>{{ type }}</contact:type> 
        <contact:postalInfo type="loc">
          <contact:isfinnish>{{ isfinnish }}</contact:isfinnish> 
          <contact:name>{{ name }}</contact:name>
          <contact:org>{{ org }}</contact:org>
          <contact:registernumber>{{ registernumber }}</contact:registernumber> 
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
        <contact:legalemail>{{ email }}</contact:legalemail>
        <contact:authInfo>
          <contact:pw>{{ authInfo }}</contact:pw>
        </contact:authInfo>
      </contact:create>
    </create>
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
            $from[] = '/{{ isfinnish }}/';
            $to[] = htmlspecialchars($params['isfinnish']);
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
          <contact:type>{{ type }}</contact:type> 
          <contact:postalInfo type="loc">
            <contact:isfinnish>{{ isfinnish }}</contact:isfinnish> 
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
                default:
                    throw new EppException('Invalid value for transfer:op specified.');
                    break;
            }
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
            $name = (string)($r->name ?? 'N/A');
            $trStatus = (string)($r->trStatus ?? 'N/A');
            $reID = (string)($r->reID ?? 'N/A');
            $reDate = (string)($r->reDate ?? 'N/A');
            $acID = (string)($r->acID ?? 'N/A');

            $return = array(
                'code' => $code,
                'msg' => $msg,
                'name' => $name,
                'trStatus' => $trStatus,
                'reID' => $reID,
                'reDate' => $reDate,
                'acID' => $acID
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
            $from[] = '/{{ authInfoPw }}/';
            $to[] = htmlspecialchars($params['authInfoPw']);
            $from[] = '/{{ clTRID }}/';
            $clTRID = str_replace('.', '', round(microtime(1), 3));
            $to[] = htmlspecialchars($this->prefix . '-domain-create-' . $clTRID);
            $from[] = "/<\w+:\w+>\s*<\/\w+:\w+>\s+/ims";
            $to[] = '';
            $xml = preg_replace($from, $to, '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
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
