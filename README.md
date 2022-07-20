# Response headers component

This component, made for Symfony, allows to set response headers defining them in configuration.

[![Latest Stable Version](https://poser.pugx.org/softspring/response-headers/v/stable.svg)](https://packagist.org/packages/softspring/response-headers)
[![Latest Unstable Version](https://poser.pugx.org/softspring/response-headers/v/unstable.svg)](https://packagist.org/packages/softspring/response-headers)
[![License](https://poser.pugx.org/softspring/response-headers/license.svg)](https://packagist.org/packages/softspring/response-headers)
[![Total Downloads](https://poser.pugx.org/softspring/response-headers/downloads)](https://packagist.org/packages/softspring/response-headers)
[![Build status](https://github.com/softspring/response-headers/actions/workflows/php.yml/badge.svg?branch=5.0)](https://github.com/softspring/response-headers/actions/workflows/php.yml)

## Installation

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require softspring/response-headers
```

## Basic configuration

Create a configuration file:

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers:
        X-Frame-Options: "SAMEORIGIN"
        X-Content-Type-Options: "nosniff"

services:
    Softspring\Component\ResponseHeaders\EventListener\ResponseHeadersListener:
        tags: ['kernel.event_subscriber']
        arguments:
            $headers: '%response_headers%'
```

## Use conditions 

You can set some conditions to match before applying response headers.

### Configure services

For this feature expression language component is required:

```console
$ composer require symfony/expression-language
```

Then you must configure expression language service:

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers_global_conditions: []
    response_headers:
        ...

services:
    softspring.response_headers.expression_language:
        class: Symfony\Component\ExpressionLanguage\ExpressionLanguage
        arguments:
            - '@?Psr\Cache\CacheItemPoolInterface'

    Softspring\Component\ResponseHeaders\EventListener\ResponseHeadersListener:
        tags: ['kernel.event_subscriber']
        arguments:
            $headers: '%response_headers%'
            $expressionLanguage: '@softspring.response_headers.expression_language'
            $globalConditions: '%response_headers_global_conditions%'
```

### Define conditions

Now you can set a condition to be matched before applying a response header:

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers:
        X-Frame-Options: 
            value: "SAMEORIGIN"
            condition: "request.getPathInfo() matches '^/admin'"
        Access-Control-Allow-Origin:
            value: "*"
            condition: "request.getPathInfo() matches '^/api'"
```

### Define global conditions

Also you can set global conditions to be matched for every headers:

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers_global_conditions:
        - 'isMainRequest'
```

This global condition is recommended, to avoid setting headers for sub-requests, but it's not mandatory.

### Build conditions

For the conditions, **request** and **response** objects are available. Also a **isMainRequest** variable is defined.

Check Symfony [expression-language documentation](https://symfony.com/doc/current/components/expression_language/syntax.html).

## Headers configuration reference

There are several ways to define headers:

**Single value header**

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers:
        X-Frame-Options: "SAMEORIGIN" 
```

This code generates a *x-frame-options: "SAMEORIGIN"* header.

**Multiple value header**

Multiple value headers, will be merged to a single string delimited by semicolons

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers:
        Feature-Policy:
            - "geolocation 'self'"
            - "vibrate 'none'" 
```

This code generates a *feature-policy: "geolocation 'self'; vibrate 'none'"* header.

**Value field**

Also you can define the values into a *value* field:

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers:
        X-Frame-Options: 
            value: "SAMEORIGIN" 
        Feature-Policy:
            value:
                - "geolocation 'self'"
                - "vibrate 'none'" 
```

This *value* field is mandatory if you want to set a condition or a replace behaviour.

**Condition**

As said before, headers could be restricted with conditions:

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers:
        X-Frame-Options: 
            value: "SAMEORIGIN"
            condition: "request.getHost() == 'api.mydomain.com"
```

**Replace behaviour**

Symfony response allows to define if a header must replace a previous defined header value. 

By default, this replace behaviour is defined as true. But you can disable it using:

```yaml
# config/packages/response_headers.yaml
parameters:
    response_headers:
        X-Frame-Options: 
            value: "SAMEORIGIN"
            replace: false
```

## License

This bundle is under the MIT license. See the complete license in the bundle LICENSE file.
