# Polyfill differential fuzzing

This directory contains small, coverage-guided differential targets for
[nikic/PHP-Fuzzer](https://github.com/nikic/PHP-Fuzzer). Each campaign calls a
native PHP API and its namespaced polyfill implementation with the same bytes.

Install PHP-Fuzzer and verify all native-oracle and timeout extensions:

```sh
composer --working-dir=fuzz install
```

List available campaigns with:

```sh
composer --working-dir=fuzz fuzz
```

Start one campaign per parser through the Composer wrapper:

```sh
composer --working-dir=fuzz fuzz -- idn
composer --working-dir=fuzz fuzz -- mb --max-runs=100000
composer --working-dir=fuzz fuzz -- date --max-runs=100000
composer --working-dir=fuzz fuzz -- normalizer_validation --max-runs=100000
```

Each file under `campaigns/` is an independently runnable PHP-Fuzzer target.
Closely related entry points can share a file and use `FuzzDataProvider` to
consume a scenario selector from the end of the testcase before passing the
remaining bytes to the parser. For example, `idn` selects either `idn_to_ascii`
or `idn_to_utf8`, while `mb` selects an mbstring operation. Each family learns
its related paths in one corpus. To add another parser, add one campaign file
and, when useful, a dictionary.
Keep native and polyfill setup identical, create stateful formatter objects
on every invocation, and return by-reference outputs and parser error codes
as part of the observed value.

The runner initializes separate live corpora under `fuzz/corpus/<target>` from
the immutable inputs in `fuzz/seeds/<target>`. Crash files and other campaign
output go to `fuzz/runs/<target>`. Generated corpus and run files are ignored by
Git.

Some exact binary seed files use a `hex:` representation so their input does
not acquire the source file's final newline. Mutated inputs without that prefix
are used as raw bytes.
