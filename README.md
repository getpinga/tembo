# Namingo EPP Client

An open-source PHP EPP client supporting 36 domain registry backends. Works with any PHP framework and is fully customizable.

[![StandWithUkraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

## Introduction

**Namingo EPP** is an open-source PHP EPP client that enables seamless connection to EPP servers for domain registration and management. It supports multiple EPP extensions, integrates easily with any PHP framework, and is fully customizable for various domain registries.

The client also provides EPP modules for WHMCS and FOSSBilling, supporting all essential features for efficient domain management.

## Get Involved

We're on a mission to make **Namingo** the best it can be, and we need your expertise! Whether you're adept in development, have a keen eye for design, or simply brim with innovative ideas, your contribution can make a world of difference.

## Features

### Supported EPP Commands

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

### Supported Connection Types

| type | status |
|----------|----------|
| EPP over TLS/TCP | ✅ |
| EPP over HTTPS | ✅ |
| RRI | ✅ |
| TMCH | ✅ |
| REGRR | ❌ |

### Registry Support (36 backends and counting)

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
| Registro.it | .it | IT | ✅ | |
| RoTLD | .ro | | ✅ | |
| RyCE | all | | ✅ | |
| SIDN | all | | ✅ | |
| SWITCH | .ch, .li | | ✅ | |
| Traficom | .fi | FI | ✅ | only org contacts |
| Verisign | all | VRSN | ✅ | |
| ZADNA | .za |  | ✅ | |
| ZDNS | all |  | ✅ | |

### Integration with billing systems

Would you like to see any registry added as a WHMCS/FOSSBilling module? Or an EPP module for any other billing system? Simply create an [issue](https://github.com/getpinga/tembo/issues) in this project and let us know.

#### WHMCS

| Registry | TLDs | Status | Project |
|----------|----------|----------|----------|
| Generic RFC EPP | any | ✅ | [whmcs-epp-rfc](https://github.com/getpinga/whmcs-epp-rfc) |
| Hostmaster | .ua | ✅ | [whmcs-epp-ua](https://github.com/getpinga/whmcs-epp-ua) |
| EURid | .eu | ✅ | [whmcs-epp-eurid](https://github.com/getpinga/whmcs-epp-eurid) |

#### FOSSBilling

| Registry | TLDs | Status | Project |
|----------|----------|----------|----------|
| Generic RFC EPP | any | ✅ | [fossbilling-epp-rfc](https://github.com/getpinga/fossbilling-epp-rfc) |
| AFNIC | .fr/others | ✅ | [fossbilling-epp-fr](https://github.com/getpinga/fossbilling-epp-fr) |
| Caucasus Online | .ge | ✅ | [fossbilling-epp-ge](https://github.com/getpinga/fossbilling-epp-ge) |
| FRED | .cz/any | ✅ | [fossbilling-epp-fred](https://github.com/getpinga/fossbilling-epp-fred) |
| Hostmaster | .ua | ✅ | [fossbilling-epp-ua](https://github.com/getpinga/fossbilling-epp-ua) |

## Documentation

### Installation

To begin, follow these steps for setting up the EPP Client. This installation process is optimized for a VPS running Ubuntu 22.04/24.04 or Debian 12.

#### 1. Install PHP

Make sure PHP is installed on your server. Use the appropriate commands for your operating system.

```bash
apt install -y curl software-properties-common ufw
add-apt-repository ppa:ondrej/php
apt update
apt install -y bzip2 composer git net-tools php8.3 php8.3-bz2 php8.3-cli php8.3-common php8.3-curl php8.3-fpm php8.3-gd php8.3-gmp php8.3-imagick php8.3-intl php8.3-mbstring php8.3-opcache php8.3-readline php8.3-soap php8.3-xml unzip wget whois
```

#### 2. Install Tembo Package

Navigate to your project directory and run the following command:

```bash
composer require pinga/tembo
```

#### 3. Configure Access to the Registry

Edit the `examples/Connection.php` file to configure your registry access credentials.
If the registry requires SSL certificates and you don't have them, refer to the troubleshooting section for steps to generate `cert.pem` and `key.pem`.

#### Using the EPP Client

- You can use the commands provided in the `examples` directory to interact with the EPP server.

- Alternatively, include the `Connection.php` file in your project and build your custom application using the `EppClient` class and its functions.

### Troubleshooting

#### EPP Server Access

If you're unsure whether your system can access the EPP server, you can test the connection using OpenSSL. Try one or both of the following commands:

1. Basic Connectivity Test:

```bash
openssl s_client -showcerts -connect epp.example.com:700
```

2. Test with Client Certificates:

```bash
openssl s_client -connect epp.example.com:700 -CAfile cacert.pem -cert cert.pem -key key.pem
```

Replace `epp.example.com` with your EPP server's hostname and adjust the paths to your certificate files (`cacert.pem`, `cert.pem`, and `key.pem`) as needed. These tests can help identify issues with SSL/TLS configurations or network connectivity.

#### Generating an SSL Certificate and Key

If you do not have an SSL certificate and private key for secure communication with the registry, you can generate one using OpenSSL.

```bash
openssl genrsa -out key.pem 2048
openssl req -new -x509 -key key.pem -out cert.pem -days 365
```

**Note:** For production environments, it's recommended to use a certificate signed by a trusted Certificate Authority (CA) instead of a self-signed certificate.

#### EPP-over-HTTPS Issues

If you experience login or other issues with EPP-over-HTTPS registries such as `.eu`, `.fi`, `.hr`, `.it`, or `.lv`, it might be caused by a corrupted or outdated cookie file. Follow these steps to fix it:

```bash
rm -f /tmp/eppcookie.txt
```

After deleting the cookie file, try logging in again. This will force the creation of a new cookie file and may resolve the issue.

#### Need More Help?

If the steps above don’t resolve your issue, refer to the EPP Client logs (`/path/to/tembo/log`) to identify the specific problem.

### Benchmarking an EPP Server

To run tests against an EPP server using the Tembo EPP client, follow these steps:

#### 1. Configure Your Connection

Edit the file `benchmark/Connection.php` - this file should contain the connection details for the server you want to test. It uses the same format as `examples/Connection.php`.

#### 2. Run the Benchmark

From the root directory, run `php benchmark/Benchmark.php` - this will execute a series of domain check commands to test your server’s response and performance.

#### 3. Customize the Benchmark

You can modify `benchmark/Benchmark.php` to:
- Add your own EPP commands
- Change the number of requests
- Adjust the test logic

Use this script as a starting point to test and tune your EPP server setup.

## Support

Your feedback and inquiries are invaluable to Namingo's evolutionary journey. If you need support, have questions, or want to contribute your thoughts:

- **Email**: Feel free to reach out directly at [help@namingo.org](mailto:help@namingo.org).

- **Discord**: Or chat with us on our [Discord](https://discord.gg/97R9VCrWgc) channel.
  
- **GitHub Issues**: For bug reports or feature requests, please use the [Issues](https://github.com/getnamingo/epp-client/issues) section of our GitHub repository.

- **GitHub Discussions**: For general discussions, ideas, or to connect with our community, visit the [Discussion](https://github.com/getnamingo/epp-client/discussions) page on our GitHub project.

We appreciate your involvement and patience as Namingo continues to grow and adapt.

## 💖 Support This Project

If you find Namingo EPP Client useful, consider donating:

- [Donate via Stripe](https://donate.stripe.com/7sI2aI4jV3Offn28ww)
- BTC: `bc1q9jhxjlnzv0x4wzxfp8xzc6w289ewggtds54uqa`
- ETH: `0x330c1b148368EE4B8756B176f1766d52132f0Ea8`

## Licensing

Namingo EPP Client is licensed under the MIT License.