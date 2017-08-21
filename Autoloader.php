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
     * @param   string  $prefix           Namespace prefix.
     * @param   string  $baseDirectory    Base directory for this prefix.
     * @param   bool    $prepend          Whether the prefix is prepended or
     *                                    appended to the prefix' stack.
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
     * @param   string  $entity    Entity name to load.
     * @return  bool
     */
    public function load(string $entity)
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
     * Check whether at least one base directory exists for a namespace prefix.
     *
     * @param   string  $namespacePrefix    Namespace prefix.
     * @return  bool
     */
    public function hasBaseDirectory(string $namespacePrefix): bool
    {
        return isset($this->_namespacePrefixesToBaseDirectories[$namespacePrefix]);
    }

    /**
     * Get declared base directories for a namespace prefix.
     *
     * @param   string  $namespacePrefix    Namespace prefix.
     * @return  array
     */
    public function getBaseDirectories(string $namespacePrefix): array
    {
        if (false === $this->hasBaseDirectory($namespacePrefix)) {
            return [];
        }

        return $this->_namespacePrefixesToBaseDirectories[$namespacePrefix];
    }

    /**
     * Get loaded classes.
     */
    public static function getLoadedClasses(): array
    {
        return get_declared_classes();
    }

    /**
     * Run the entire autoloader stack with a specific entity.
     */
    public function runAutoloaderStack(string $entity): void
    {
        spl_autoload_call($entity);
    }

    /**
     * Register the autoloader.
     *
     * @param   bool  $prepend    Prepend this autoloader to the stack or not.
     * @return  bool
     */
    public function register(bool $prepend = false): bool
    {
        return spl_autoload_register([$this, 'load'], true, $prepend);
    }

    /**
     * Unregister the current instance of this autoloader.
     *
     * # Examples
     *
     * ```php
     * $autoloader = new Hoa\Consistency\Autoloader();
     * $autoloader->register();
     *
     * asser($autoloader->unregister());
     * ```
     */
    public function unregister(): bool
    {
        return spl_autoload_unregister([$this, 'load']);
    }

    /**
     * Returns a collection of all the registered autoloader names, including
     * those from other libraries.
     */
    public function getRegisteredAutoloaders(): array
    {
        return spl_autoload_functions();
    }

    /**
     * Dynamic new, a simple factory.
     * It loads and constructs a class, with provided arguments.
     *
     * @param   string   $classname    Classname.
     * @param   array    $arguments    Arguments for the constructor.
     * @return  object
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
$autoloader->addNamespace('Hoa', dirname(__DIR__));
$autoloader->register();
