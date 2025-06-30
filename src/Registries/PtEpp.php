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

class PtEpp extends Epp
{
    protected function addLoginExtensions(\XMLWriter $xml): void
    {
        $xml->startElement('svcExtension');
        $xml->writeElement('extURI', 'http://eppdev.dns.pt/schemas/ptcontact-1.0');
        $xml->writeElement('extURI', 'http://eppdev.dns.pt/schemas/ptdomain-1.0');
        $xml->writeElement('extURI', 'urn:ietf:params:xml:ns:secDNS-1.1');
        $xml->endElement(); // svcExtension
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
            $from[] = '/{{ ip }}/';
            $to[] = htmlspecialchars($params['ipaddress']);
            $from[] = '/{{ v }}/';
            $to[] = (preg_match('/:/', $params['ipaddress']) ? 'v6' : 'v4');
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
 xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
 xsi:schemaLocation="urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd">
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
<extension>
 <ptcontact:create xmlns:ptcontact="http://eppdev.dns.pt/schemas/ptcontact-1.0"
xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptcontact-1.0 ptcontact-1.0.xsd">
 <ptcontact:vat>{{ vat }}</ptcontact:vat>
 <ptcontact:mobile>{{ phonenumber }}</ptcontact:mobile>
 </ptcontact:create>
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
 <extension>
 <ptcontact:update xmlns:ptcontact="http://eppdev.dns.pt/schemas/ptcontact-1.0"
xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptcontact-1.0 ptcontact-1.0.xsd">
 <ptcontact:mobile>{{ voice }}</ptcontact:mobile>
 </ptcontact:update>
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
                  <ptdomain:transfer xmlns:ptdomain="http://eppdev.dns.pt/schemas/ptdomain-1.0"
            xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptdomain-1.0 ptdomain-1.0.xsd">
                    <ptdomain:autoRenew>1</ptdomain:autoRenew>
                  </ptdomain:transfer>
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
            $from[] = '/{{ tech }}/';
            $to[] = htmlspecialchars($params['tech']);
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
          {{ hostObjs }}
        </domain:ns>
        <domain:registrant>{{ registrant }}</domain:registrant>
        <domain:contact type="tech">{{ tech }}</domain:contact>
        <domain:authInfo>
          <domain:pw>{{ authInfoPw }}</domain:pw>
        </domain:authInfo>
      </domain:create>
    </create>
    <extension>
      <ptdomain:create xmlns:ptdomain="http://eppdev.dns.pt/schemas/ptdomain-1.0"
xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptdomain-1.0 ptdomain-1.0.xsd">
        <ptdomain:autoRenew>true</ptdomain:autoRenew>
        <ptdomain:Arbitration>true</ptdomain:Arbitration>
        <ptdomain:ownerConf>false</ptdomain:ownerConf>
      </ptdomain:create>
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
            $from[] = '/{{ tech }}/';
            $to[] = htmlspecialchars($params['tech']);
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
          {{ hostObjs }}
        </domain:ns>
        <domain:registrant>{{ registrant }}</domain:registrant>
        <domain:contact type="tech">{{ tech }}</domain:contact>
        <domain:authInfo>
          <domain:pw>{{ authInfoPw }}</domain:pw>
        </domain:authInfo>
      </domain:create>
    </create>
    <extension>
      <ptdomain:create xmlns:ptdomain="http://eppdev.dns.pt/schemas/ptdomain-1.0"
xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptdomain-1.0 ptdomain-1.0.xsd">
        <ptdomain:autoRenew>true</ptdomain:autoRenew>
        <ptdomain:Arbitration>true</ptdomain:Arbitration>
        <ptdomain:ownerConf>false</ptdomain:ownerConf>
      </ptdomain:create>
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
        <domain:period unit="y">1</domain:period>
      </domain:renew>
    </renew>
    <extension>
      <ptdomain:renew
xmlns:ptdomain="http://eppdev.dns.pt/schemas/ptdomain-1.0"
xsi:schemaLocation="http://eppdev.dns.pt/schemas/ptdomain-1.0 ptdomain-1.0.xsd">
        <ptdomain:autoRenew>1</ptdomain:autoRenew>
      </ptdomain:renew>
    </extension>
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
