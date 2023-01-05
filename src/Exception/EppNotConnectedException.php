<?php
/**
 * Tembo EPP client library
 *
 * Written in 2023 by Taras Kondratyuk (https://getpinga.com)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */
 
namespace Pinga\Tembo\Exception;

class EppNotConnectedException extends EppException
{
    protected $message = 'Not connected to EPP server. Call connect() first.';
}
