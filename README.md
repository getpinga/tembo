# Tembo EPP Client

[![StandWithUkraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

Welcome to our open source PHP EPP client!

Our client allows you to easily connect to EPP servers and manage domain registration and other EPP services. Whether you are using Pinga or any other PHP framework, our client integrates seamlessly to provide a flexible and powerful solution for managing your EPP needs.

Some of the key features of our client include:

- Support for multiple EPP extensions: Connect to a wide range of EPP servers and take advantage of various EPP services

- Easy integration: Integrates smoothly with Pinga or any other PHP framework

- Customizable configuration: Adjust settings to meet your specific needs and easily modify the client to work with any domain registry

- Advanced security: Protect your data with TLS encryption

- Open source and freely available: Use and modify our client as you see fit

Whether you are a developer looking to enhance your application with EPP functionality or a domain registrar seeking a reliable EPP client, our open source solution is the perfect choice. Join us and revolutionize your EPP management today!

## Installation

To install the Pinga Tembo EPP client, follow these steps:

1. Navigate to your project directory and run the following command:

```composer require pinga/tembo```

2. In your PHP code, include the Composer autoloader and use the EppClient class from the Pinga Tembo package:

```
// Include the Composer autoloader
require_once 'vendor/autoload.php';

// Use the Epp class from the Pinga Tembo package
use Pinga\Tembo\Epp;
use Pinga\Tembo\EppClient;
use Pinga\Tembo\HttpsClient;
```

3. You can now use the EppClient class and its functions in your code. You can refer to the **examples** directory for examples of how the package can be used.

## Supported EPP Commands

| | domain | contact | host | others |
|----------|----------|----------|----------|----------|
| check | ✅ | ✅ | ✅ | login ✅ |
| info | ✅ | ✅ | ✅ | logout ✅ |
| create | ✅ | ✅ | ✅ | poll 🚧 |
| update | N/A | ✅ | 🚧 | hello 🚧 |
| updateNS | ✅ | N/A | N/A | keep-alive 🚧 |
| updateContact | ✅ | N/A | N/A | |
| updateStatus | 🚧 | ❌ | ❌| |
| updateDNSSEC | 🚧 | N/A | N/A | |
| renew | ✅ | N/A | N/A | |
| delete | ✅ | ✅ | ✅ |  |
| transferRequest | ✅ | ❌ | ❌ | |

## Supported EPP Types

| type | status |
|----------|----------|
| EPP over TLS/TCP | ✅ |
| EPP over HTTPS | ✅ |
| REGRR | ❌ |

## Registry Support

| Registry | TLDs | Status | TODO |
|----------|----------|----------|----------|
| Generic RFC EPP | any | ✅ | |
| IIS | .se, .nu | ✅ | transfer and domain contact update |
| FRED | .cz/any | 🚧 | all besides contact:create |
| FORTH-ICS | .gr, .ελ | 🚧 | |
| NASK | .pl | 🚧 | |
| Hostmaster | .ua | 🚧 | |
| CoCCA | .lviv.ua | 🚧 | |
