<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2013 César Rodas                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace crodas\ClassInfo;

use PhpParser;
use PhpParser\Node\Stmt;
use PhpParser\Node;

class Parser extends PhpParser\NodeVisitorAbstract
{
    protected $classes = array();
    protected $functions = array();
    protected $file;

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function parseFunction(Node $node, $name)
    {
        $function = new Definition\TFunction($name);
        $function->setParameters($node->params);
        $function->setPHPDoc($node->getDocComment());

        return $function;
    }

    public function setMods(Node $node, Definition\TBase $object)
    {
        $mods = array();
        foreach (array('isPublic', 'isPrivate', 'isProtected', 'isFinal', 'isStatic', 'isAbstract') as $check) {
            if (is_callable(array($node, $check)) && $node->$check()) {
                $mods[] = constant('T_' . substr(strtoupper($check), 2));
            }
        }
        $object->setMods($mods);
    }

    protected function getClass($name, $type = 'class')
    {
        if (empty($this->classes[strtolower($name)])) {
            $this->classes[strtolower($name)] = new Definition\TClass($name);
        }
        $this->classes[strtolower($name)]->setType($type);

        return $this->classes[strtolower($name)];
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Stmt\Class_
            || $node instanceof Stmt\Trait_
            || $node instanceof Stmt\Interface_) {
            $type = strtolower(get_class($node));
            $type = substr($type, strrpos($type, "\\")+1, -1);
            $class = $this->getClass($node->namespacedName->toString(), $type);
            $class->setFile($this->file);
            $class->setPHPDoc($node->getDocComment());
            if (!empty($node->extends)) {
                $class->addDependency('parent', $this->getClass($node->extends->toString()));
            }

            if (!empty($node->implements)) {
                foreach ($node->implements as $interface) {
                    $class->addDependency('interface', $this->getClass($interface->toString(), 'interface'));
                }
            }

            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Stmt\ClassMethod) {
                    $method = $this->parseFunction($stmt, $stmt->name);
                    $this->setMods($stmt, $method);
                    $class->addMethod($method);
                } else if ($stmt instanceof Stmt\TraitUse) {
                    $class->addDependency('trait', $this->getClass($node->extends->toString(), 'trait'));
                } else if ($stmt instanceof Stmt\Property) {
                    $property = new Definition\TProperty('$'. $stmt->props[0]->name);
                    $this->setMods($stmt, $property);
                    $property->setPHPDoc($stmt->getDocComment());
                    $class->addProperty($property);
                }
            }
        } else if ($node instanceof Stmt\Function_) {
            $function = $this->parseFunction($node, $node->namespacedName->toString());
            $this->functions[strtolower($function->getName())] = $function;
            $function->setFile($this->file);
        }
    }
}
