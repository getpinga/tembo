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

interface EppRegistryInterface
{
    public function connect(array $params);
    public function readResponse();
    public function writeRequest(string $xml);
    public function disconnect();

    public function login(array $params);
    public function logout(array $params);
    public function hello();

    public function hostCheck(array $params);
    public function hostInfo(array $params);
    public function hostCreate(array $params);
    public function hostUpdate(array $params);
    public function hostDelete(array $params);

    public function contactCheck(array $params);
    public function contactInfo(array $params);
    public function contactCreate(array $params);
    public function contactUpdate(array $params);
    public function contactDelete(array $params);

    public function domainCheck(array $params);
    public function domainCheckClaims(array $params);
    public function domainInfo(array $params);
    public function domainCreate(array $params);
    public function domainCreateDNSSEC(array $params);
    public function domainCreateClaims(array $params);
    public function domainUpdateNS(array $params);
    public function domainUpdateContact(array $params);   
    public function domainUpdateStatus(array $params);
    public function domainUpdateAuthinfo(array $params);
    public function domainUpdateDNSSEC(array $params);
    public function domainTransfer(array $params);
    public function domainRenew(array $params);
    public function domainDelete(array $params);
    public function domainRestore(array $params);
    public function domainReport(array $params);

    public function pollReq();
    public function pollAck(array $params);
}
