# Ext-SOAP powered SOAP engine

This package is a [SOAP engine backport](https://github.com/php-soap-backport/engine) that leverages the built-in functions from  PHP's `ext-soap` extension compatible for php 7.1.

It basically flips the `SoapClient` inside out: All the built-in functions for encoding, decoding and HTTP transport can be used in a standalone way.

If your package contains a `SoapClient`, you might consider using this package as an alternative:

* It gives you full control over the HTTP layer.
* It validates the `$options` you pass to the `SoapClient` and gives you meaningful errors.
* It transforms the types and methods into real objects so that you can actually use that information.
* It makes it possible to use the encoding / decoding logic without doing any SOAP calls to a server.
* ...

# Want to help out? 💚

- [Become a Sponsor of Project author](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#sponsor)
- [Become a Sponsor of Backport author](https://github.com/php-soap-backports/.github/blob/main/HELPING_OUT.md#sponsor)
- [Contribute to project](https://github.com/php-soap/.github/blob/main/HELPING_OUT.md#contribute)
- [Contribute to backport project](https://github.com/php-soap-backports/.github/blob/main/HELPING_OUT.md#contribute)

# Installation

```shell
composer install php-soap-backports/ext-soap-engine
```

## Example usage:

This example contains an advanced setup for creating a flexible ext-soap based engine.
It shows you the main components that you can use for configuring PHP's `SoapClient` and to transform it into a SOAP engine:

```php
use Soap\Engine\SimpleEngine;
use Soap\ExtSoapEngine\AbusedClient;
use Soap\ExtSoapEngine\Configuration\ClassMap\ClassMapCollection;
use Soap\ExtSoapEngine\Configuration\TypeConverter\TypeConverterCollection;
use Soap\ExtSoapEngine\ExtSoapDriver;
use Soap\ExtSoapEngine\ExtSoapOptions;
use Soap\ExtSoapEngine\Transport\ExtSoapClientTransport;
use Soap\ExtSoapEngine\Transport\TraceableTransport;

$engine = new SimpleEngine(
    ExtSoapDriver::createFromClient(
        $client = AbusedClient::createFromOptions(
            ExtSoapOptions::defaults($wsdl, [
                'soap_version' => SOAP_1_2,
            ])
                ->disableWsdlCache()
                ->withClassMap(new ClassMapCollection())
                ->withTypeMap(new TypeConverterCollection())
        )
    ),
    $transport = new TraceableTransport(
        $client,
        new ExtSoapClientTransport($client)
    )
);
```

Fetching a SOAP Resource:

```php
$result = $engine->request('SomeMethod', [(object)['param1' => true]]);

// Collecting last soap call:
var_dump($transport->collectLastRequestInfo());
```

You can still set advanced configuration on the actual SOAP client:

```php
$client->__setLocation(...);
$client->__setSoapHeaders(...);
$client->__setCookie(...);
```

Reading / Parsing metadata

```php
var_dump(
    $engine->getMetadata()->getMethods(),
    $engine->getMetadata()->getTypes()
);

$methodInfo = $engine->getMetadata()->getMethods()->fetchByName('SomeMethod');
```

## Engine

This package provides following engine components:

* **ExtSoapEncoder:** Uses PHP's `SoapClient` in order to encode a mixed request body into a SOAP request.
* **ExtSoapDecoder:** Uses PHP's `SoapClient` in order to decode a SOAP Response into mixed data.
* **ExtSoapMetadata:** Parses the methods and types from PHP's `SoapClient` into something more usable.
* **ExtSoapDriver:** Combines the ext-soap encoder, decoder and metadata tools into a usable `ext-soap` preset.

### Transports

* **ExtSoapClientTransport:** Uses PHP's `SoapClient` to handle SOAP requests.
* **ExtSoapServerTransport:** Uses PHP's `SoapServer` to handle SOAP requests. It can e.g. be used during Unit tests.
* **TraceableTransport:** Can be used to decorate another transport and keeps track of the last request and response. It should be used as an alternative for fetching it on the SoapClient.

## Configuration options

### ExtSoapOptions

This package provides a little wrapper around all available `\SoapClient` [options](https://www.php.net/manual/en/soapclient.construct.php).
It provides sensible default options. If you want to set specific options, you can do so in a sane way:
It will validate the options before they are passed to the `\SoapClient`.
This way, you'll spend less time browsing the official PHP documentation.

### ClassMap

By providing a class map, you let `ext-soap` know how data of specific SOAP types can be converted to actual classes.

**Usage:**

```php
use Soap\ExtSoapEngine\Configuration\ClassMap\ClassMap;
use Soap\ExtSoapEngine\ExtSoapOptions;

$options = ExtSoapOptions::defaults($wsdl);
$classmap = $options->getClassMap();
$classmap->set(new ClassMap('WsdlType', 'PhpClassName'));
```

### TypeConverter

Some exotic XSD types are hard to transform to PHP objects.
A typical example are dates: some people like it as a timestamp, some want it as a DateTime, ...
By adding custom TypeConverters, it is possible to convert a WSDL type to / from a PHP type.

These TypeConverters are added by default:

- DateTimeTypeConverter
- DateTypeConverter
- DoubleTypeConverter
- DecimalTypeConverter

You can also create your own converter by implementing the `TypeConverterInterface`.

**Usage:**

```php
use Soap\ExtSoapEngine\Configuration\TypeConverter;
use Soap\ExtSoapEngine\ExtSoapOptions;

$options = ExtSoapOptions::defaults($wsdl);
$typemap = $options->getTypeMap();
$typemap->add(new TypeCOnverter\DateTimeTypeConverter());
$typemap->add(new TypeConverter\DecimalTypeConverter());
$typemap->add(new TypeConverter\DoubleTypeConverter());
```

