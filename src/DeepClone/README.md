Symfony Polyfill / DeepClone
============================

This package provides a pure-PHP implementation of the functions and exception
classes from the [deepclone extension](https://github.com/symfony/php-ext-deepclone):

- `deepclone_to_array(mixed $value, ?array $allowedClasses = null, bool $allowNamedClosures = false): array`
  — converts any serializable PHP value graph into a pure array (only scalars
  and nested arrays, no objects).
- `deepclone_from_array(array $data, ?array $allowedClasses = null, bool $allowNamedClosures = false): mixed`
  — rebuilds the value graph from the array, preserving object identity,
  references, cycles, and private property state.
- `DeepClone\NotInstantiableException` and `DeepClone\ClassNotFoundException`

`$allowNamedClosures` (default `false`, required on both ends) gates the
by-name encoding of closures over named callables (first-class callables such
as `strlen(...)` or `Cls::method(...)`, and `Closure::fromCallable()`): a
by-name payload can mint a `Closure` over any function or method of that name,
so it should only travel between ends that trust each other. Closures declared
in constant expressions — anonymous static closures and first-class callables
over a method of their own declaring class, as found in attribute arguments —
serialize as a reference to their declaration site and round-trip without this
option.

When the native `deepclone` extension is loaded, this polyfill does nothing —
the extension provides the same functions 4–5× faster.

The array representation preserves PHP's copy-on-write for strings and scalar
arrays, making repeated cloning of a prototype significantly more memory
efficient than `unserialize(serialize())`. It also serves as a cache-friendly
serialization format: when dumped via `var_export()` into a `.php` file,
OPcache maps the array into shared memory, making deserialization essentially
free.

This is the same wire format used by Symfony's `VarExporter\DeepCloner`.

More information can be found in the
[main Polyfill README](https://github.com/symfony/polyfill/blob/main/README.md).

License
=======

This library is released under the [MIT license](LICENSE).
