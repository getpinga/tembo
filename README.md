# Namingo EPP Client

[![StandWithUkraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

**Namingo EPP** is an open-source PHP EPP client that enables seamless connection to EPP servers for domain registration and management. It supports multiple EPP extensions, integrates easily with any PHP framework, and is fully customizable for various domain registries.

The client also provides EPP modules for WHMCS and FOSSBilling, supporting all essential features for efficient domain management.

## Installation

To begin, simply follow the steps below. This installation process is optimized for a VPS running Ubuntu 22.04/24.04 or Debian 12.

1. Navigate to your project directory and run the following command:

```composer require pinga/tembo```

2. In your PHP code, include the **Connection.php** file from the Tembo package:

```
require_once 'Connection.php';
```

3. To create test certificates (cert.pem and key.pem), if the registry does not have mandatory SSL certificates, you can use:

```
openssl genrsa -out key.pem 2048
```

```
openssl req -new -x509 -key key.pem -out cert.pem -days 365
```

4. You can now use the EppClient class and its functions in your code. You can refer to the **examples** directory for examples of how the package can be used.

5. To test if you have access to the EPP server from your system, you may use:

```
openssl s_client -showcerts -connect epp.example.com:700
```

```
openssl s_client -connect epp.example.com:700 -CAfile cacert.pem -cert cert.pem -key key.pem
```

## Supported EPP Commands

| | domain | contact | host | session |
|----------|----------|----------|----------|----------|
| check | ✅ | ✅ | ✅ | login ✅ |
| checkClaims | ✅ | N/A | N/A | logout ✅ |
| info | ✅ | ✅ | ✅ | poll ✅ |
| create | ✅ | ✅ | ✅ | hello ✅ |
| createDNSSEC | ✅ | N/A | N/A | keep-alive ✅ |
| createClaims | ✅ | N/A | N/A | new password ✅ |
| update | N/A | ✅ | ✅ | |
| updateNS | ✅ | N/A | N/A | |
| updateContact | ✅ | N/A | N/A | |
| updateAuthinfo | ✅ | N/A | N/A | |
| updateStatus | ✅ | ❌ | ❌| |
| updateDNSSEC | ✅ | N/A | N/A | |
| renew | ✅ | N/A | N/A | |
| delete | ✅ | ✅ | ✅ |  |
| transferRequest | ✅ | ❌ | ❌ | |
| transferQuery | ✅ | ❌ | ❌ | |
| transferApprove | ✅ | ❌ | ❌ | |
| transferReject | ✅ | ❌ | ❌ | |
| transferCancel | ✅ | ❌ | ❌ | |
| rgp:restore | ✅ | N/A | N/A | |
| rgp:report | ✅ | N/A | N/A | |

## Supported Connection Types

| type | status |
|----------|----------|
| EPP over TLS/TCP | ✅ |
| EPP over HTTPS | ✅ |
| RRI | ✅ |
| TMCH | ✅ |
| REGRR | ❌ |

## Registry Support (36 backends and counting)

| Registry | TLDs | Extension | Status | TODO |
|----------|----------|----------|----------|----------|
| Generic RFC EPP | any | | ✅ | |
| AFNIC | .fr/others | FR | ✅ | |
| CARNET | .hr | HR | ✅ | |
| Caucasus Online | .ge | | ✅ | |
| CentralNic | all | | ✅ | |
| CoCCA | all | | ✅ | |
| CORE/Knipp | all | | ✅ | |
| DENIC | .de | | ✅ | |
| Domicilium | .im | | ✅ | |
| DOMREG | .lt | LT | 🚧 | work on extensions |
| DRS.UA | all | | ✅ | |
| EURid | .eu | EU | ✅ | |
| FORTH-ICS | .gr, .ελ | GR | ✅ | |
| FRED | .cz/any | FRED | ✅ | domain update NS/DNSSEC |
| GoDaddy Registry | all | | ✅ | |
| Google Nomulus | all | | ✅ | |
| Hostmaster | .ua | UA | ✅ | |
| Identity Digital | all | | ✅ | |
| IIS | .se, .nu | SE | ✅ | |
| HKIRC | .hk | HK | ✅ | |
| NASK | .pl | PL | ✅ | |
| Namingo | all | | ✅ | |
| NIC Chile | .cl | | ✅ | |
| NIC Mexico | .mx | MX | ✅ | |
| NIC.LV | .lv | LV | ✅ | |
| NORID | .no | NO | ✅ | |
| .PT | .pt | PT | ✅ | |
| Registr.io | all | | ✅ | |
| Registro.it | .it | IT | 🚧 | work on extensions |
| RoTLD | .ro | | ✅ | |
| RyCE | all | | ✅ | |
| SIDN | all | | ✅ | more tests |
| SWITCH | .ch, .li | | ✅ | |
| Verisign | all | VRSN | ✅ | |
| ZADNA | .za |  | ✅ | |
| ZDNS | all |  | ✅ | |

## Integration with billing systems

Would you like to see any registry added as a WHMCS/FOSSBilling module? Or an EPP module for any other billing system? Simply create an [issue](https://github.com/getpinga/tembo/issues) in this project and let us know.

### WHMCS

| Registry | TLDs | Status | Project |
|----------|----------|----------|----------|
| Generic RFC EPP | any | ✅ | [whmcs-epp-rfc](https://github.com/getpinga/whmcs-epp-rfc) |
| Hostmaster | .ua | ✅ | [whmcs-epp-ua](https://github.com/getpinga/whmcs-epp-ua) |
| EURid | .eu | ✅ | [whmcs-epp-eurid](https://github.com/getpinga/whmcs-epp-eurid) |

### FOSSBilling

| Registry | TLDs | Status | Project |
|----------|----------|----------|----------|
| Generic RFC EPP | any | ✅ | [fossbilling-epp-rfc](https://github.com/getpinga/fossbilling-epp-rfc) |
| AFNIC | .fr/others | ✅ | [fossbilling-epp-fr](https://github.com/getpinga/fossbilling-epp-fr) |
| Caucasus Online | .ge | ✅ | [fossbilling-epp-ge](https://github.com/getpinga/fossbilling-epp-ge) |
| FRED | .cz/any | ✅ | [fossbilling-epp-fred](https://github.com/getpinga/fossbilling-epp-fred) |
| Hostmaster | .ua | ✅ | [fossbilling-epp-ua](https://github.com/getpinga/fossbilling-epp-ua) |