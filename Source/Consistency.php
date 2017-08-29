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
 * A collection of tools to ensure foreward and backward compatibility between
 * different Hoa versions and PHP versions.
 */
class Consistency
{
    /**
     * Returns `true` if an entity exists (a class, an interface, a trait…),
     * otherwise returns `false`.
     *
     * By default, the autoloaders will not run if the entity does not exist.
     *
     * # Examples
     *
     * ```php
     * assert(true  === Hoa\Consistency\Consistency::entityExists(Hoa\Consistency\Consistency::class));
     * assert(false === Hoa\Consistency\Consistency::entityExists(FooBar::class));
     * ```
     */
    public static function entityExists(string $entityName, bool $autoloader = false): bool
    {
        return
            class_exists($entityName, $autoloader) ||
            interface_exists($entityName, false) ||
            trait_exists($entityName, false);
    }

    /**
     * An entity has a short name if the two last parts of its fully-qualified
     * name are equal. Then the latest part can be removed.
     *
     * # Examples
     *
     * ```php
     * assert('Foo\Bar'     === Hoa\Consistency\Consistency::getEntityShortestName('Foo\Bar\Bar'));
     * assert('Foo\Bar\Baz' === Hoa\Consistency\Consistency::getEntityShortestName('Foo\Bar\Baz'));
     * assert('Foo'         === Hoa\Consistency\Consistency::getEntityShortestName('Foo'));
     * ```
     */
    public static function getEntityShortestName(string $entityName): string
    {
        $parts = explode('\\', $entityName);
        $count = count($parts);

        if (1 >= $count) {
            return $entityName;
        }

        if ($parts[$count - 2] === $parts[$count - 1]) {
            return implode('\\', array_slice($parts, 0, -1));
        }

        return $entityName;
    }

    /**
     * Declares a flexible entity.
     *
     * A flexible entity can be referenced with 2 names: Its normal name, and
     * its shortest name (see `getEntityShortestName`).
     *
     * # Examples
     *
     * ```php,ignore
     * Hoa\Consistency\Consistency::flexEntity(Foo\Bar\Bar::class);
     *
     * // `new Foo\Bar()` will work!
     * ```
     */
    public static function flexEntity(string $entityName): bool
    {
        return class_alias(
            $entityName,
            static::getEntityShortestName($entityName),
            false
        );
    }

    /**
     * Returns `true` if the given word is a reserved keyword of PHP (based on
     * the latest version), otherwise returns `false`.
     *
     * # Examples
     *
     * ```php
     * assert(true  === Hoa\Consistency\Consistency::isKeyword('else'));
     * assert(false === Hoa\Consistency\Consistency::isKeyword('otherwise'));
     * ```
     */
    public static function isKeyword(string $word): bool
    {
        static $_list = [
            // PHP keywords.
            '__halt_compiler',
            'abstract',
            'and',
            'array',
            'as',
            'bool',
            'break',
            'callable',
            'case',
            'catch',
            'class',
            'clone',
            'const',
            'continue',
            'declare',
            'default',
            'die',
            'do',
            'echo',
            'else',
            'elseif',
            'empty',
            'enddeclare',
            'endfor',
            'endforeach',
            'endif',
            'endswitch',
            'endwhile',
            'eval',
            'exit',
            'extends',
            'false',
            'final',
            'float',
            'for',
            'foreach',
            'function',
            'global',
            'goto',
            'if',
            'implements',
            'include',
            'include_once',
            'instanceof',
            'insteadof',
            'int',
            'interface',
            'isset',
            'list',
            'mixed',
            'namespace',
            'new',
            'null',
            'numeric',
            'object',
            'or',
            'print',
            'private',
            'protected',
            'public',
            'require',
            'require_once',
            'resource',
            'return',
            'static',
            'string',
            'switch',
            'throw',
            'trait',
            'true',
            'try',
            'unset',
            'use',
            'var',
            'void',
            'while',
            'xor',
            'yield',

            // Compile-time constants.
            '__class__',
            '__dir__',
            '__file__',
            '__function__',
            '__line__',
            '__method__',
            '__namespace__',
            '__trait__'
        ];

        return in_array(strtolower($word), $_list);
    }

    /**
     * Returns `true` if the given identifier is a valid PHP identifier (based on
     * the latest version), otherwise returns `false`.
     *
     * # Examples
     *
     * ```php
     * assert(true  === Hoa\Consistency\Consistency::isIdentifier('hello'));
     * assert(false === Hoa\Consistency\Consistency::isIdentifier('world!'));
     * ```
     */
    public static function isIdentifier(string $id): bool
    {
        return 0 !== preg_match(
            '#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x80-\xff]*$#',
            $id
        );
    }

    /**
     * Registers a [register shutdown function](http://php.net/register_shutdown_function).
     *
     * It may be analogous to a super static destructor.
     *
     * # Examples
     *
     * ```php
     * Hoa\Consistency\Consistency::registerShutdownFunction(
     *     function (): void {
     *         echo 'Bye bye!', "\n";
     *     }
     * );
     * ```
     */
    public static function registerShutdownFunction(callable $callable): void
    {
        register_shutdown_function($callable);
    }

    /**
     * Returns the absolute path to the PHP binary, or `null` if the method is
     * not able to find it.
     */
    public static function getPHPBinary(): ?string
    {
        if (defined('PHP_BINARY')) {
            return PHP_BINARY;
        }

        if (isset($_SERVER['_'])) {
            return $_SERVER['_'];
        }

        foreach (['', '.exe'] as $extension) {
            if (file_exists($_ = PHP_BINDIR . DS . 'php' . $extension)) {
                return realpath($_);
            }
        }

        return null;
    }

    /**
     * Generates a [Universally Unique
     * Identifier](https://en.wikipedia.org/wiki/Universally_unique_identifier)
     * (UUID).
     *
     * # Examples
     *
     * ```php
     * $uuid = Hoa\Consistency\Consistency::uuid();
     *
     * assert(preg_match('/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}/', $uuid));
     * ```
     */
    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

/**
 * Curry a function with the “…” character (HORIZONTAL ELLIPSIS Unicode
 * character [unicode: 2026, UTF-8: E2 80 A6]).
 *
 * Obviously, because the first argument is a callable, it is possible to combine it with
 * `Hoa\Consistency\Xcallable`.
 *
 * # Examples
 *
 * ```php
 * $replaceInFoobar   = curry('str_replace', …, …, 'foobar');
 * $replaceFooByBazIn = curry('str_replace', 'foo', 'baz', …);
 *
 * assert('bazbar'    === $replaceInFoobar('foo', 'baz'));
 * assert('bazbarbaz' === $replaceFooByBazIn('foobarbaz'));
 * ```
 *
 * Nested curries also work:
 *
 * ```php
 * $replaceInFoobar = curry('str_replace', …, …, 'foobar');
 * $replaceFooInFoobarBy = curry($replaceInFoobar, 'foo', …);
 *
 * assert('bazbar' === $replaceFooInFoobarBy('baz'));
 * ```
 */
function curry(callable $callable, ...$arguments): Closure
{
    $ii = array_keys($arguments, …, true);

    return function (...$subArguments) use ($callable, $arguments, $ii) {
        return $callable(...array_replace($arguments, array_combine($ii, $subArguments)));
    };
}

/**
 * Flex entity.
 */
Consistency::flexEntity(Consistency::class);
