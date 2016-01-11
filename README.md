![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoa\Consistency ![state](http://central.hoa-project.net/State/Consistency)

This library provides a thin layer between PHP VMs and libraries to ensure
consistency accross VM versions and library versions.

## Installation

With [Composer](http://getcomposer.org/), to include this library into your
dependencies, you need to require
[`hoa/consistency`](https://packagist.org/packages/hoa/consistency):

```json
{
    "require": {
        "hoa/consistency": "~1.0"
    }
}
```

Please, read the website to [get more informations about how to
install](http://hoa-project.net/Source.html).

## Quick usage

We propose a quick overview of how the consistency API ensures foreward and
backward compatibility, also an overview of the [PSR-4
autoloader](http://www.php-fig.org/psr/psr-4/) and the xcallable API.

### Foreward and backward compatibility

The `Hoa\Consistency\Consistency` class ensures foreward and backward
compatibility.

#### Example with traits

For instance, it provides a [`trait_exists`](http://php.net/trait_exists)
function if it does not exist. This way, you are able to anticipate this new
feature even if your current PHP version does not support it:

```php
if (trait_exists('Foo')) { …
```

Also, we are able to build a complete
`Hoa\Consistency\Consistency::entityExists` method that checks whether any kind
of entity (class, interface or trait) exists. Even if your current PHP version
does not support traits, it will work. And when you will upgrade, the new check
will be free:

```php
if (false === Hoa\Consistency\Consistency::entityExist('foo')) {
    // load it.
}
```

#### Example with keywords

The `Hoa\Consistency\Consistency::isKeyword` checks whether a specific word is
reserved by PHP or not. Let's say your current PHP version does not support the
`callable` keyword or type declarations such as `int`, `float`, `string` etc.,
the `isKeyword` method will tell you if they are reserved keywords: Not only
for your current PHP version, but maybe in an incoming version.

```php
$isKeyword = Hoa\Consistency\Consistency::isKeyword('yield');
```

It avoids to write algorithms that might break in the future or for your users
living on the edge.

#### Example with identifiers

PHP identifiers are defined by a regular expression. It might change in the
future. To prevent breaking your algorithms, you can use the
`Hoa\Consistency\Consistency::isIdentifier` method to check an identifier is
correct regarding current PHP version:

```php
$isValidIdentifier = Hoa\Consistency\Consistency::isIdentifier('foo');
```

#### Flexible entities

Flexible entities are very simple. If we declare `Foo\Bar\Bar` as a flexible
entity, we will be able to access it with the `Foo\Bar\Bar` name or `Foo\Bar`.
This is very useful if your architecture evolves but you want to keep the
backward compatibility. For instance, it often happens that you create a
`Foo\Bar\Exception` class in the `Foo/Bar/Exception.php` file. But after few
versions, you realise other exceptions need to be introduced, so you need an
`Exception` directory. In this case, `Foo\Bar\Exception` should move as
`Foo\Bar\Exception\Exception`. If this latter is declared as a flexible entity,
backward compatibility will be kept.

```php
Hoa\Consistency\Consistency::flexEntity('Foo\Bar\Exception\Exception');
```

Another example is the “entry-class” (informal naming).
`Hoa\Consistency\Consistency` is a good example. This is more convenient to
write `Hoa\Consistency` instead of `Hoa\Consistency\Consistency`. This is
possible because this is a flexible entity.

#### Throwable & co.

The `Throwable` interface has been introduced to represent a whole new exception
architecture in PHP. Thus, to be compatible with incoming PHP versions, you
might want to use this interface in some cases. Hopefully, the `Throwable`
interface will be created for you if it does not exists.

```php
try {
    …
} catch (Throwable $e) {
    …
}
```

### Autoloader

`Hoa\Consistency\Autoloader` is a [PSR-4
compatible](http://www.php-fig.org/psr/psr-4/) autoloader. It simply works as
follows:
  * `addNamespace` is used to map a namespace prefix to a directory,
  * `register` is used to register the autoloader.

The API also provides the `load` method to force the load of an entity,
`unregister` to unregister the autoloader, `getRegisteredAutoloaders` to get
a list of all registered autoloaders etc.

For instance, to map the `Foo\Bar` namespace to the `Source/` directory:

```php
$autoloader = new Hoa\Consistency\Autoloader();
$autoloader->addNamespace('Foo\Bar', 'Source');
$autoloader->register();

$baz = new Foo\Bar\Baz(); // automatically loaded!
```

### Xcallable

Xcallables are “extended callables”. It is a unified API to invoke callables of
any kinds, and also extends some Hoa's API (like
[`Hoa\Event`](http://central.hoa-project.net/Resource/Library/Event)
or
[`Hoa\Stream`](http://central.hoa-project.net/Resource/Library/Stream)). It
understands the following kinds:
  * `'function'` as a string,
  * `'class::method'` as a string,
  * `'class', 'method'` as 2 string arguments,
  * `$object, 'method'` as 2 arguments,
  * `$object, ''` as 2 arguments, the “able” is unknown,
  * `function (…) { … }` as a closure,
  * `['class', 'method']` as an array of strings,
  * `[$object, 'method']` as an array.

To use it, simply instanciate the `Hoa\Consistency\Xcallable` class and use it
as a function:

```php
$xcallable = new Hoa\Consistency\Xcallable('strtoupper');
var_dump($xcallable('foo'));

/**
 * Will output:
 *     string(3) "FOO"
 */
```

The `Hoa\Consistency\Xcallable::distributeArguments` method invokes the callable
but the arguments are passed as an array:

```php
$xcallable->distributeArguments(['foo']);
```

This is also possible to get a unique hash of the callable:

```php
var_dump($xcallable->getHash());

/**
 * Will output:
 *     string(19) "function#strtoupper"
 */
```

Finally, this is possible to get a reflection instance of the current callable
(can be of kind [`ReflectionFunction`](http://php.net/ReflectionFunction),
[`ReflectionClass`](http://php.net/ReflectionClass),
[`ReflectionMethod`](http://php.net/ReflectionMethod) or
[`ReflectionObject`](http://php.net/ReflectionObject)):

```php
var_dump($xcallable->getReflection());

/**
 * Will output:
 *     object(ReflectionFunction)#42 (1) {
 *       ["name"]=>
 *       string(10) "strtoupper"
 *     }
 */
```

When the object is set but not the method, the latter will be deduced if
possible. If the object is of kind
[`Hoa\Stream`](http://central.hoa-project.net/Resource/Library/Stream), then
according to the type of the arguments given to the callable, the
`writeInteger`, `writeString`, `writeArray` etc. method will be used. If the
argument is of kind `Hoa\Event\Bucket`, then the method name will be deduced
based on the data contained inside the event bucket. This is very handy. For
instance, the following example will work seamlessly:

```php
Hoa\Event\Event::getEvent('hoa://Event/Exception')
    ->attach(new Hoa\File\Write('Exceptions.log'));
```

The `attach` method on `Hoa\Event\Event` transforms its argument as an
xcallable. In this particular case, the method to call is unknown, we only have
an object (of kind `Hoa\File\Write`). However, because this is a stream, the
method will be deduced according to the data contained in the event bucket fired
on the `hoa://Event/Exception` event channel.

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
