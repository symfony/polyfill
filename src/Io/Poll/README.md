Symfony Polyfill / Io / Poll
============================

This component provides the [`Io\Poll` API](https://wiki.php.net/rfc/poll_api)
added to PHP 8.6 core, for PHP >= 8.1:

- `Io\Poll\Context`, `Io\Poll\Watcher` and `StreamPollHandle`
- `Io\Poll\Backend` and `Io\Poll\Event` enums
- `Io\IoException` and the `Io\Poll\*Exception` hierarchy

The polyfill is backed by `stream_select()`, so only the `Poll` backend is
available and edge-triggering is not supported. Event detection follows the
semantics of the native `poll` backend as closely as `select()` allows:
`Event::Error` is only reported for connection resets, `Event::ReadHangUp` is
never reported, and a TCP half-close reports `Read|HangUp` where native
reports plain `Read`. The phpt tests of the native implementation, borrowed
from php-src, run against the polyfill as part of the test suite.

`Context::wait()` takes its timeout as a `Time\Duration`. That class is not
required to call `wait()` without a timeout; install `symfony/polyfill-time`
to build one on PHP < 8.6.

More information can be found in the
[main Polyfill README](https://github.com/symfony/polyfill/blob/main/README.md).

License
=======

This library is released under the [MIT license](LICENSE).
