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

class ClassInfo
{
    protected $parser;
    protected $traverser;
    protected $visitor;

    public function __construct($file = '')
    {
        $parser        = new PhpParser\Parser(new PhpParser\Lexer\Emulative);
        $traverser     = new PhpParser\NodeTraverser;
        $this->visitor = new Parser;

        $traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver); // we will need resolved names
        $traverser->addVisitor($this->visitor);

        $this->parser = $parser;
        $this->traverser = $traverser;

        if (!empty($file)) {
            $this->parse($file);
        }

    }

    public function getPHPDocs()
    {
        $elements = array();
        foreach ($this->getFunctions() as $function) {
            if ($function->getPHPDoc()) {
                $elements[] = $function;
            }
        }

        foreach ($this->getClasses() as $class) {
            if ($class->getPHPDoc()) {
                $elements[] = $class;
            }
            foreach (array_merge($class->getProperties(), $class->getMethods()) as $element) {
                if ($element->getPHPDoc()) {
                    $elements[] = $element;
                }
            }
        }

        return $elements;
    }

    public function getClasses()
    {
        return $this->visitor->getClasses();
    }

    public function getClass($name)
    {
        $classes = $this->visitor->GetClasses();
        $name = strtolower($name);
        if (empty($classes[$name])) {
            return NULL;
        }
        return $classes[$name];;
    }

    public function getFunctions()
    {
        return $this->visitor->getFunctions();
    }

    public function parse($file)
    {
        $this->visitor->setFile($file);
        $stmts = $this->parser->parse(file_get_contents($file));
        $stmts = $this->traverser->traverse($stmts);
    }
}
