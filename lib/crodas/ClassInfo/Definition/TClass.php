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
namespace crodas\ClassInfo\Definition;

class TClass extends TBase
{
    protected $type;
    protected $deps = array();
    protected $methods = array();
    protected $properties = array();

    public function addProperty(TProperty $var)
    {
        $var->setFile($this->file);
        $var->class = $this;
        $this->properties[$var->name] = $var;
        return $this;
    }

    public function addMethod(TFunction $function)
    {
        $function->setFile($this->file);
        $function->class = $this;
        $this->methods[strtolower($function->name)] = $function;
        return $this;
    }

    public function setType($type)
    {
        $this->type = strtolower($type);
    }

    public function getType()
    {
        return $this->type;
    }

    public function addDependency(TClass $name, $type = NULL)
    {
        $type = $type ? $type : strtolower($name->getType());
        if (empty($this->deps[$type])) {
            $this->deps[$type] = array();
        }
        $this->deps[$type][] = $name;
        return $this;
    }

    public function getInterfaces()
    {
        if (empty($this->deps['implements'])) {
            return array();
        }

        return $this->deps['implements'];
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getMethod($name)
    {
        if (empty($this->methods[$name])) {
            return NULL;
        }
        return $this->methods[$name];
    }

    public function getProperty($name)
    {
        if (empty($this->properties[$name])) {
            return NULL;
        }
        return $this->properties[$name];
    }


    public function getMethods()
    {
        return $this->methods;
    }

    public function getTraits()
    {
        if (empty($this->deps['trait'])) {
            return array();
        }

        return $this->deps['trait'];
    }

    public function getParent()
    {
        if (empty($this->deps['extends'])) {
            return NULL;
        }

        return $this->deps['extends'][0];
    }

    public function __toString()
    {
        return $this->name;
    }

}
