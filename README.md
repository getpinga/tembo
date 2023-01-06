# Tembo EPP Client

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

2. In your PHP code, include the Composer autoloader and use the Epp class from the Pinga Tembo package:

```
// Include the Composer autoloader
require_once 'vendor/autoload.php';

// Use the Epp class from the Pinga Tembo package
use Pinga\Tembo\Epp;
```

3. You can now use the Epp class and its functions in your code. You can refer to the **test.php** file for examples of how the package can be used. Additional EPP functions will be added to the package on a weekly basis.

## Supported EPP Commands

| | domain | contact | host | others |
|----------|----------|----------|----------|----------|
| check | ✅ | ✅  | ✅ | login |
| info | ✅ | 🚧 | 🚧 | logout |
| create | ✅ | ✅ | ✅ | |
| update | N/A |🚧  | 🚧| |
| updateNS | ✅ | N/A | N/A | |
| updateContact | ✅ | N/A | N/A | |
| updateStatus | 🚧 | ❌ | ❌| |
| updateDNSSEC | 🚧 | N/A | N/A | |
| renew | ✅ | N/A | N/A | |
| delete | ✅ | ✅ | ✅ |  |
| transferRequest | ✅ | ❌ | ❌ | |

## Registry Support

| Registry | TLDs | Status |
|----------|----------|----------|
| IIS | .se, .nu | all above |
| Registrio | X | all above |
| NASK | .pl | all above |
| FORTH-ICS | .gr, .ελ |  all above|
