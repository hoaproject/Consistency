<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2015, Hoa community. All rights reserved.
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

namespace Hoa\Consistency\Test\Unit;

use Hoa\Consistency\Consistency as SUT;
use Hoa\Test;

/**
 * Class \Hoa\Consistency\Test\Unit\Consistency.
 *
 * Test suite of the consistency class.
 *
 * @copyright  Copyright © 2007-2015 Hoa community
 * @license    New BSD License
 */
class Consistency extends Test\Unit\Suite
{
    protected function _entity_exists_with_xxx($class, $interface, $trait)
    {
        $this
            ->given(
                $this->function->class_exists     = $class,
                $this->function->interface_exists = $interface,
                $this->function->trait_exists     = $trait
            )
            ->when($result = SUT::entityExists('foo'))
            ->then
                ->boolean($result)
                    ->isTrue();
    }

    public function case_entity_exists_with_class()
    {
        return $this->_entity_exists_with_xxx(true, false, false);
    }

    public function case_entity_exists_with_interface()
    {
        return $this->_entity_exists_with_xxx(false, true, false);
    }

    public function case_entity_exists_with_trait()
    {
        return $this->_entity_exists_with_xxx(false, false, true);
    }

    public function case_entity_does_not_exists()
    {
        $this
            ->given(
                $this->function->class_exists     = false,
                $this->function->interface_exists = false,
                $this->function->trait_exists     = false
            )
            ->when($result = SUT::entityExists('foo'))
            ->then
                ->boolean($result)
                    ->isFalse();
    }

    public function case_get_entity_shortest_name()
    {
        $this
            ->when($result = SUT::getEntityShortestName('Foo\Bar\Bar'))
            ->then
                ->string($result)
                    ->isEqualTo('Foo\Bar');
    }

    public function case_get_entity_shortest_name_with_already_the_shortest()
    {
        $this
            ->when($result = SUT::getEntityShortestName('Foo\Bar'))
            ->then
                ->string($result)
                    ->isEqualTo('Foo\Bar');
    }

    public function case_get_entity_shortest_name_with_no_namespace()
    {
        $this
            ->when($result = SUT::getEntityShortestName('Foo'))
            ->then
                ->string($result)
                    ->isEqualTo('Foo');
    }

    public function case_flex_entity()
    {
        $self = $this;

        $this
            ->given(
                $this->function->class_alias = function ($name, $alias) use ($self, &$called) {
                    $called = true;

                    $self
                        ->string($alias)
                            ->isEqualTo(SUT::getEntityShortestName($name));

                    return true;
                }
            )
            ->when($result = SUT::flexEntity('Foo\Bar\Bar'))
            ->then
                ->boolean($result)
                    ->isTrue()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_flex_entity_fails()
    {
        $self = $this;

        $this
            ->given(
                $this->function->class_alias = function ($name, $alias) use ($self, &$called) {
                    $called = true;

                    $self
                        ->string($alias)
                            ->isEqualTo(SUT::getEntityShortestName($name));

                    return false;
                }
            )
            ->when($result = SUT::flexEntity('Foo'))
            ->then
                ->boolean($result)
                    ->isFalse()
                ->boolean($called)
                    ->isTrue();
    }

    public function case_is_keyword()
    {
        $this
            ->given(
                $keywords = [
                    '__HALT_COMPILER',
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
                    'while',
                    'xor',
                    'yield',
                    '__CLASS__',
                    '__DIR__',
                    '__FILE__',
                    '__FUNCTION__',
                    '__LINE__',
                    '__METHOD__',
                    '__NAMESPACE__',
                    '__TRAIT__'
                ]
            )
            ->when(function () use ($keywords) {
                foreach ($keywords as $keyword) {
                    $this
                        ->boolean(SUT::isKeyword($keyword))
                            ->isTrue();
                }
            });
    }

    public function case_is_identifier()
    {
        $this
            ->given($_identifier = $this->realdom->regex('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$#'))
            ->when(function () use ($_identifier) {
                foreach ($this->sampleMany($_identifier, 1000) as $identifier) {
                    $this
                        ->boolean(SUT::isIdentifier($identifier))
                            ->isTrue();
                }
            });
    }
}
