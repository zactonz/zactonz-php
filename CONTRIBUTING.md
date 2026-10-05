# Contributing

Bug reports and pull requests are welcome.

## Setup

```bash
composer install
composer check
```

`composer check` runs the generator in check mode, PHP_CodeSniffer (PSR-12), PHPStan (level 8) and the unit tests. All four must pass.

## Layout

- `src/` is hand-written, except `src/Service/`.
- `src/Service/` and `docs/reference.md` are generated. Do not edit them.
- `specs/` holds one OpenAPI document per endpoint. They are the source for the generated code and are copied from the API's documentation, so a change to an endpoint's parameters belongs upstream. Open an issue for those.
- `tools/endpoints.php` decides how endpoints are named in PHP: service, method, hidden and added parameters.

After changing `tools/` or `specs/`:

```bash
composer generate
```

## Tests

Unit tests need no network apart from a server the suite starts on localhost:

```bash
composer test
```

The live suite calls the real API and spends quota. It skips products you have no key for:

```bash
ZACTONZ_API_KEYS="zk_qr_…,zk_domain_…" vendor/bin/phpunit --testsuite live
```

## Style

PSR-12. Public classes and methods carry a docblock that says what they do and what they throw. Comments explain why, where the code alone does not.
