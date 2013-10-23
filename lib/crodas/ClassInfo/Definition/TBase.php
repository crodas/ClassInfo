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

class TBase
{
    protected $is_user_defined = false;
    protected $file;
    protected $name;
    protected $mods = array();
    protected $phpDoc;

    public function __construct($name, $file = null)
    {
        $this->name = $name;
        if ($file) {
            $this->setFile($file);
        }
    }

    public function setPHPDoc($txt)
    {
        $this->phpDoc = $txt;
        return $this;
    }

    public function getPHPDoc()
    {
        return $this->phpDoc;
    }

    public function getName()
    {
        return $this->name;
    }

    public function isPublic()
    {
        return in_array(T_PUBLIC, $this->mods) || !(
            $this->isPrivate() || $this->isProtected()
        );
    }

    public function isFinal()
    {
        return in_array(T_FINAL, $this->mods);
    }

    public function isAbstract()
    {
        return in_array(T_ABSTRACT, $this->mods);
    }

    public function isPrivate()
    {
        return in_array(T_PRIVATE, $this->mods);
    }

    public function isStatic()
    {
        return in_array(T_STATIC, $this->mods);
    }

    public function isProtected()
    {
        return in_array(T_PROTECTED, $this->mods);
    }

    public function setFile($file)
    {
        $this->file = $file;
        $this->is_user_defined = true;
        return true;
    }

    public function setMods(Array $mods)
    {
        $this->mods = $mods;
        return $this;
    }

    public function isUserDefined()
    {
        return $this->is_user_defined;
    }
}


