# Response Headers Features

Functional definition for `softspring/response-headers`.

This file defines the expected behavior and functional scope of the component.

## Purpose

- Provide a reusable way to add HTTP response headers in Symfony applications.
- Keep header rules in one place instead of repeating them across controllers and listeners.
- Make it easier to apply security, caching, and integration headers consistently.

## Main Features

- Expose a listener that can subscribe to the Symfony response event.
- Accept a predefined list of headers to apply to responses.
- Support simple string values for single-value headers.
- Support arrays of values that are merged into a single header string using `; `.
- Support object-style header definitions with:
  - `value`
  - `replace`
  - `condition`
- Support global conditions evaluated before any configured header is applied.
- Support per-header conditions evaluated independently.
- Provide expression context with:
  - `request`
  - `response`
  - `isMainRequest`
- Work without `symfony/expression-language` when no conditions are configured.

## Expected Usage

- Register the listener as a Symfony event subscriber.
- Pass the configured header rules through service arguments or container parameters.
- Use the component to centralize headers such as:
  - `X-Frame-Options`
  - `X-Content-Type-Options`
  - `Referrer-Policy`
  - `Strict-Transport-Security`
  - `Permissions-Policy`
  - `Content-Security-Policy`
  - API or integration-specific headers

## Header Definition Expectations

- A header may be defined as a plain string.
- A header may be defined as an array of string fragments.
- A header may be defined with a `value` field when `replace` or `condition` is also needed.
- `replace` should default to `true`.
- `condition` should only be evaluated when an expression language instance is available.

## Condition Expectations

- Global conditions should apply before any individual header condition.
- If a global condition returns `false`, the component should skip all configured headers for that response.
- If a header condition returns `false`, only that header should be skipped.
- If conditions are configured without an expression language instance, the component should fail explicitly.

## Extension Expectations

- Applications should be able to wire their own `ExpressionLanguage` instance.
- Applications should be able to add custom expression functions and providers.
- Applications should be able to decorate or replace the listener when they need a different rule source or context.
- Applications should be able to register more than one listener instance if they want different header groups.

## Current Limits

- This package is a component, not a full bundle with automatic Symfony configuration.
- Applications must wire the listener themselves.
- Array values are joined with `; `, so the component is best suited to headers that use directive-style values.
- The component does not include built-in configuration validation beyond the PHP types it uses at runtime.
