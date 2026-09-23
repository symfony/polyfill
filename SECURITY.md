# Security Policy

## Reporting a vulnerability

This project is part of the Symfony ecosystem and follows the
[Symfony security process](https://symfony.com/doc/current/contributing/code/security.html).

If you discover a vulnerability in a polyfill implementation, do not open a
public issue or pull request. Report it privately by emailing
**security@symfony.com**, and include the affected polyfill and PHP
versions, a minimal reproducer, and the expected security impact.

## Scope

A polyfill bug is a vulnerability only if it would be classified as one by the
[PHP security classification policy](https://github.com/php/policies/blob/main/security-classification.rst)
had the native implementation behaved the same way. In particular, the
following are not vulnerabilities:

- behavioral differences from the native implementation, such as parsing
  differentials, when the difference is the only problem;
- issues that require running malicious PHP code;
- issues that require passing untrusted input to a function not intended to
  receive it, such as `deepclone_from_array()`;
- denial of service, such as excessive CPU or memory use, which the Symfony
  process treats as hardening.

Report them in a public [issue](https://github.com/symfony/polyfill/issues) or
[pull request](https://github.com/symfony/polyfill/pulls). They are handled as
regular bugs or hardening opportunities and do not receive CVEs.

If you are unsure whether an issue has security impact, report it privately.

## Issues shared with PHP

If a vulnerability also affects native PHP, report it privately to the
[PHP project](https://github.com/php/php-src/security/advisories/new) first,
then send the same report to **security@symfony.com** and mention the PHP
report. Polyfills run on PHP versions that do not get the native fix, so the
polyfill fix is released in coordination with PHP's disclosure.

## Supported versions

Only the latest 1.x release receives security fixes.
