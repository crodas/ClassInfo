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

class ClassInfo
{
    protected $file;
    protected $parser;
    protected $trait;

    protected $alias;
    protected $namespace;
    protected $classes   = array();
    protected $functions = array();

    public function __construct($file = '')
    {
        $php = new Parser;
        $this->trait = defined('T_TRAIT') ? T_TRAIT : "";
        
        $php->on(T_NAMESPACE, array($this, 'parseNamespace'));
        $php->on(T_USE, array($this, 'parseUse'));
        $php->on(T_CLASS, array($this, 'parseClass'));
        $php->on(T_INTERFACE, array($this, 'parseClass'));
        $php->on(T_FUNCTION, array($this, 'parseFunction'));
        $php->on(T_VARIABLE, array($this, 'parseProperty'));
        $php->on($this->trait, array($this, 'parseClass'));

        $this->parser = $php;

        if (!empty($file)) {
            $this->parse($file);
        }
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getClass($name)
    {
        $name = strtolower($name);
        if (empty($this->classes[$name])) {
            return NULL;
        }
        return $this->classes[$name];;
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function parse($file)
    {
        $this->namespace    = "";
        $this->file         = $file;

        $this->parser->setFile($file);
        return $this;
    }

    protected function setAlias($alias, $real)
    {
        $this->alias[$alias] = $real;
        return true;
    }

    public function getClassObject($name)
    {
        if (empty($name)) {
            throw new \RuntimeException("Invalid class name");
        }
        if ($name[0] == "\\") {
            $className = substr($name, 1);
        } else if (isset($this->alias[$name])) {
            $className = $this->alias[$name];
        } else {
            $className = $this->namespace . $name;
        }
        $index = strtolower($className);
        if (!isset($this->classes[$index])) {
            $this->classes[$index] = new Definition\TClass($className);
        }
        return $this->classes[$index];
    }

    protected function getPHPDoc($php)
    {
        $allow = array(
            T_WHITESPACE, T_PUBLIC, T_PRIVATE, T_PROTECTED, 
            T_STATIC, T_ABSTRACT, T_FINAL, T_CLASS, T_FUNCTION, T_VARIABLE,
            T_VAR,
        );
        $start = $php->getOffset();
        $php->move(-1)->revWhile($allow);
        $token = $php->GetToken();
        $php->setOffset($start);
        if ($token[0] == T_DOC_COMMENT) {
            return $token[1];
        }
        return '';

    }

    protected function getNamespace($php)
    {
        $php->move()
            ->moveWhile(array(T_WHITESPACE));
        $start = $php->getOffset();
        $php->moveWhile(array(T_WHITESPACE, T_STRING, T_NS_SEPARATOR));
        $tokens = array_map(function($token) {
            return $token[0] == T_WHITESPACE ? "" : $token[1];
        }, $php->getTokens($start, $php->getOffset() - $start));
        return implode("", $tokens);
    }

    protected function getModifiers($php)
    {
        $allow = array(
            T_WHITESPACE, T_PUBLIC, T_PRIVATE, T_PROTECTED, 
            T_STATIC, T_ABSTRACT, T_FINAL,
        );
        $php->move(-1)->revWhile($allow)->move();

        $mods = [];
        while (true) {
            $token = $php->GetToken();
            $php->move();
            if ($token[0] == T_WHITESPACE) continue;
            if (!in_array($token[0], $allow)) break;
            $mods[] = $token[0];
        }
       
        return $mods;
    }

    public function parseProperty(Parser $php)
    {
        $parent = $php->getStackObject();
        if ($parent instanceof Definition\TClass) {
            $name = $php->getToken();
            $name = $name[1];
            $mods = $this->getModifiers($php->revWhileNot(array(T_WHITESPACE)));
            $property = new Definition\TProperty($name, $this->file);
            $property->setPHPDoc($this->getPHPDoc($php));
            $property->setMods($mods);
            $parent->addProperty($property);
        }
    }

    public function parseFunction(Parser $php)
    {
        $parent = $php->getStackObject();
        $php->move()
            ->moveWhile(array(T_WHITESPACE));

        $name = $php->GetToken();
        $name = $name[1];
        if (empty($parent)) {
            // no method
            $name = $this->namespace . $name;
        }

        $function = new Definition\TFunction($name, $this->file);
        $function->setPHPDoc($this->getPHPDoc($php));
        $php->pushStackObject($function, 1);

        if ($parent instanceof Definition\TClass) {
            $mods = $this->getModifiers($php->revWhileNot(array(T_FUNCTION)));
            $function->setMods($mods);
            $parent->addMethod($function);
            return;
        }

        $this->functions[$name] = $function;
    }

    public function parseNamespace(Parser $php)
    {
        $namespace = $this->getNamespace($php);
        $this->namespace = empty($namespace) ? "" : $namespace . "\\";
    }

    public function parseUse(Parser $php)
    {
        $stack = $php->getStack();
        if (count($stack) == 0 || (count($stack) == 1 && $stack[0] == T_NAMESPACE)) {
            do {
                $import = $this->getNamespace($php);
                if (empty($import)) return;
                $next = $php->moveWhile(array(T_WHITESPACE))
                    ->getToken();
                $alias = substr($import, strrpos($import, "\\")+1);
                if ($next[0] == T_AS) {
                    $alias = $this->getNamespace($php);
                }
                $this->setAlias($alias, $import);
            } while ($php->getToken() == ',');
        }  else {
            // traits
            $parent = $php->getStackObject();
            if ($parent instanceof Definition\TClass) {
                do {
                    $trait = $this->getClassObject($this->getNamespace($php));
                    $trait->setPHPDoc($this->getPHPDoc($php));
                    $trait->setType('trait');
                    $parent->addDependency('trait', $trait);
                } while ($php->getToken() == ',');
            }
        }
    }

    public function parseClass(Parser $php)
    {
        $type = $php->getToken();
        $token = $php->whileNot(array(T_STRING))
            ->getToken();
        $class = $this->getClassObject($token[1]);
        $class->setPHPDoc($this->getPHPDoc($php));
        $class->setFile($this->file);
        $class->setType($type[1]);

        $token = $php->move()
            ->whileNot(array(T_EXTENDS, T_IMPLEMENTS, '{'))
            ->getToken();

        while ($token != '{') {
            if ($token[0] != ',') {
                $type = $token[1];
            }
            $parentClass = $this->getClassObject($this->getNamespace($php));
            $parentClass->setType($type == 'implements' ? 'interface' : 'class');
            $class->addDependency($type, $parentClass);
            $token = $php->getToken();
        }

        $php->pushStackObject($class, 1);
    }
}
