<?php

declare(strict_types=1);

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Consistency;

/**
 * An [autoloader](http://php.net/autoload) is responsible to load symbols at
 * runtime. This autoloader is [PSR-4](http://www.php-fig.org/psr/psr-4/)
 * compliant.
 */
class Autoloader
{
    /**
     * Namespace prefixes to base directories.
     */
    protected $_namespacePrefixesToBaseDirectories = [];

    /**
     * Adds a base directory for a namespace prefix.
     *
     * A namespace prefix must be suffixed by a backslack, otherwise it will
     * be added. A base directory must suffixed by a directory separator,
     * otherwise it will be added.
     *
     * The base directory is appened to the existing one by default. The last
     * parameter can be used to prepend it.
     *
     * # Examples
     *
     * ```php
     * $prefix         = 'Foo\Bar\\';
     * $baseDirectoryA = 'Source/Foo/Bar/';
     * $baseDirectoryB = 'Source/Foo/Baz/';
     *
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->addNamespace($prefix, $baseDirectoryA);       // append
     * $autoloader->addNamespace($prefix, $baseDirectoryB, true); // prepend
     *
     * assert($autoloader->hasBaseDirectory($prefix));
     * assert($autoloader->getBaseDirectories($prefix) === [$baseDirectoryB, $baseDirectoryA]);
     * ```
     *
     * Note the position of the base directories: First `B`, second `A`.
     */
    public function addNamespace(string $prefix, string $baseDirectory, bool $prepend = false): void
    {
        $prefix        = trim($prefix, '\\') . '\\';
        $baseDirectory = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (false === isset($this->_namespacePrefixesToBaseDirectories[$prefix])) {
            $this->_namespacePrefixesToBaseDirectories[$prefix] = [];
        }

        if (true === $prepend) {
            array_unshift(
                $this->_namespacePrefixesToBaseDirectories[$prefix],
                $baseDirectory
            );
        } else {
            array_push(
                $this->_namespacePrefixesToBaseDirectories[$prefix],
                $baseDirectory
            );
        }
    }

    /**
     * Try to load the entity file for a given entity name.
     *
     * For a given entity `E`, this method will try to find at least one base
     * directory for its longest to shortest namespace prefix (if `E` is
     * `X\Y\Z`, then from `X\Y\Z`, to `X\Y`, to `X`). If one is found, then
     * the entity name `E` is mapped into a filename. If it exists, it is
     * immediately loaded.
     *
     * # Examples
     *
     * The `Foo\Bar\Baz\Qux` entity is expected to be found in the
     * `Source/Foo/Bar/Baz/Qux.php` file. This file will be loaded if it exists.
     *
     * ```php,ignore
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->addNamespace('Foo\Bar\\', 'Source/Foo/Bar/');
     *
     * $autoloader->load('Foo\Bar\Baz\Qux');
     * ```
     */
    public function load(string $entity): ?string
    {
        $entityPrefix     = $entity;
        $hasBaseDirectory = false;

        while (false !== $pos = strrpos($entityPrefix, '\\')) {
            $currentEntityPrefix = substr($entity, 0, $pos + 1);
            $entityPrefix        = rtrim($currentEntityPrefix, '\\');
            $entitySuffix        = substr($entity, $pos + 1);
            $entitySuffixAsPath  = str_replace('\\', '/', $entitySuffix);

            if (false === $this->hasBaseDirectory($currentEntityPrefix)) {
                continue;
            }

            $hasBaseDirectory = true;

            foreach ($this->getBaseDirectories($currentEntityPrefix) as $baseDirectory) {
                $file = $baseDirectory . $entitySuffixAsPath . '.php';

                if (false !== $this->requireFile($file)) {
                    return $file;
                }
            }
        }

        if (true === $hasBaseDirectory &&
            $entity === Consistency::getEntityShortestName($entity) &&
            false !== $pos = strrpos($entity, '\\')) {
            return $this->runAutoloaderStack(
                $entity . '\\' . substr($entity, $pos + 1)
            );
        }

        return null;
    }

    /**
     * Requires a file and returns `true` if it exists, otherwise returns
     * `false`.
     *
     * # Examples
     *
     * ```php,ignore
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->requireFile('Source/Foo/Bar/Baz/Qux.php');
     * ```
     */
    public function requireFile(string $filename): bool
    {
        if (false === file_exists($filename)) {
            return false;
        }

        require $filename;

        return true;
    }

    /**
     * A namespace prefix has a base directory if at least one has been
     * declared with the `addNamespace` method.
     *
     * # Examples
     *
     * ```php
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->addNamespace('Foo\Bar\\', 'Source/Foo/Bar/');
     *
     * assert(true  === $autoloader->hasBaseDirectory('Foo\Bar\\'));
     * assert(false === $autoloader->hasBaseDirectory('Baz\Qux\\'));
     * ```
     */
    public function hasBaseDirectory(string $namespacePrefix): bool
    {
        return isset($this->_namespacePrefixesToBaseDirectories[$namespacePrefix]);
    }

    /**
     * Returns the collection of declared base directories for a namespace
     * prefix.
     *
     * # Examples
     *
     * ```php
     * $prefix         = 'Foo\Bar\\';
     * $baseDirectoryA = 'Source/Foo/Bar/';
     * $baseDirectoryB = 'Source/Foo/Baz/';
     *
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->addNamespace($prefix, $baseDirectoryA);
     * $autoloader->addNamespace($prefix, $baseDirectoryB);
     *
     * assert($autoloader->getBaseDirectories($prefix) === [$baseDirectoryA, $baseDirectoryB]);
     * ```
     */
    public function getBaseDirectories(string $namespacePrefix): array
    {
        if (false === $this->hasBaseDirectory($namespacePrefix)) {
            return [];
        }

        return $this->_namespacePrefixesToBaseDirectories[$namespacePrefix];
    }

    /**
     * Returns all the classes that are loaded for this runtime.
     *
     * # Examples
     *
     * ```php
     * assert(in_array(__CLASS__, Hoa\Consistency\Autoloader::getLoadedClasses()));
     * ```
     */
    public static function getLoadedClasses(): array
    {
        return get_declared_classes();
    }

    /**
     * Calls *all* the registered autoloaders with a specific entity.
     *
     * In other words, try to load an entity by using all the registered
     * autoloaders, not only instances of `Hoa\Consistency\Autoloaders`.
     *
     * # Examples
     *
     * ```php
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->runAutoloaderStack('Foo\Bar\Baz');
     * ```
     */
    public function runAutoloaderStack(string $entity): void
    {
        spl_autoload_call($entity);
    }

    /**
     * Registers this autoloader instance.
     *
     * Because the autoloader register is a stack, a new autoloader is
     * appended. It is possible to prepend it by using the last paramter.
     *
     * # Examples
     *
     * ```php
     * assert((new Hoa\Consistency\Autoloader())->register());
     * ```
     *
     * The `addNamespace` method can be called before or after the
     * registration, it does not matter.
     */
    public function register(bool $prepend = false): bool
    {
        return spl_autoload_register([$this, 'load'], true, $prepend);
    }

    /**
     * Unregisters this autoloader instance.
     *
     * # Examples
     *
     * ```php
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->register();
     *
     * assert($autoloader->unregister());
     * ```
     */
    public function unregister(): bool
    {
        return spl_autoload_unregister([$this, 'load']);
    }

    /**
     * Returns a copy of the autoloader stack.
     *
     * Each autoloader in this copy has the form of a callback, i.e. a pair `[instance, method name]`.
     *
     * # Examples
     *
     * ```php
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->register();
     *
     * assert(in_array([$autoloader, 'load'], $autoloader->getRegisteredAutoloaders()));
     * ```
     */
    public function getRegisteredAutoloaders(): array
    {
        return spl_autoload_functions();
    }

    /**
     * Allocates a new entity based on its name and a list of arguments.
     *
     * The entity will be automatically loaded if needed. If a constructor is
     * present, it will be called with the list of arguments.
     *
     * # Examples
     *
     * ```php
     * $name      = 'ArrayIterator';
     * $arguments = [['a', 'b', 'c']];
     * $iterator  = Hoa\Consistency\Autoloader::dnew($name, $arguments);
     *
     * assert($iterator instanceof $name);
     * ```
     */
    public static function dnew(string $classname, array $arguments = [])
    {
        $classname = ltrim($classname, '\\');

        if (false === Consistency::entityExists($classname, false)) {
            spl_autoload_call($classname);
        }

        $class = new \ReflectionClass($classname);

        if (empty($arguments) || false === $class->hasMethod('__construct')) {
            return $class->newInstance();
        }

        return $class->newInstanceArgs($arguments);
    }
}

/**
 * Autoloader.
 */
$autoloader = new Autoloader();
$autoloader->addNamespace('Hoa', dirname(__DIR__, 2));
$autoloader->register();
