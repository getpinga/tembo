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

use Pinga\Tembo\Registries\FredEpp;
use Pinga\Tembo\Registries\GrEpp;
use Pinga\Tembo\Registries\LvEpp;
use Pinga\Tembo\Registries\NoEpp;
use Pinga\Tembo\Registries\PlEpp;
use Pinga\Tembo\Registries\PtEpp;
use Pinga\Tembo\Registries\SeEpp;
use Pinga\Tembo\Registries\UaEpp;

class EppRegistryFactory
{
    public static function create($registry)
    {
        switch ($registry) {
            case 'FRED':
                return new FredEpp();
                break;
            case 'GR':
                return new GrEpp();
                break;
            case 'LV':
                return new LvEpp();
                break;
            case 'NO':
                return new NoEpp();
                break;
            case 'PL':
                return new PlEpp();
                break;
            case 'PT':
                return new PtEpp();
                break;
            case 'SE':
                return new SeEpp();
                break;
            case 'UA':
                return new UaEpp();
                break;
            default:
                return new Epp();
        }
    }
}
