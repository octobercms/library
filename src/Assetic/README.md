# Rain Assetic Resources

Assetic is a simple library that lets you compile and combine basic LESS and SCSS files.

## Basic usage

You may use the `parse` methods to parse LESS or SCSS respectively, the first argument is the asset paths and the second argument is the options. The file extension determines which compiler is used, either `.less` or `.scss`.

```php
$combiner = new October\Rain\Assetic\Combiner;

echo $combiner->parse([
    '/path/to/src/styles.less',
    '/path/to/src/theme.less'
], [
    'production' => true
]);
```

The following options are supported

Options | Usage
------- | ---------
`production` | Combine with production filters (eg: minification).
`targetPath` | Sets the target output path for rewriting asset locations.
`useCache` | Use a file based cache to speed up performance.
`deepHashKey` | Cache key used for busting deep hashing.
