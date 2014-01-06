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

class Parser
{
    protected $tokens = array();
    protected $offset = 0;
    protected $total  = 0;
    protected $events = array();
    protected $stack  = array();
    protected $line   = 1;
    
    protected $classAlias = array();
    protected $lastClass  = NULL;
    protected $namespace  = "";
    
    protected $level = 0;
    protected $customStack = array();

    public function reset()
    {
        $this->line   = 1;
        $this->offset = 0;
        $this->total  = 0;
        $this->tokens = array();
        $this->customStack = array();
    }

    public function pushStackObject($object, $offset=0)
    {
        $this->customStack[$this->level+$offset] = $object;
    }

    public function getStackObject()
    {
        if (empty($this->customStack[$this->level])) {
            return array();
        }
        return $this->customStack[$this->level];
    }

    public function setFile($file)
    {
        $this->reset();
        if (!is_readable($file)) {
            throw new \RuntimeException("{$file} is not readable");
        }
        $this->setTokens(token_get_all(file_get_contents($file)));
        $this->run();
    }

    public function WhileNot(Array $searchTokens)
    {
        $tokens = $this->tokens;
        for ($i = &$this->offset; $i < $this->total; $i++) {
            if (is_array($tokens[$i])) {
                if (in_array($tokens[$i][0], $searchTokens)) {
                    return $this;
                }
            } else if (in_array($tokens[$i], $searchTokens)) {
                return $this;
            }
        }
        throw new \RuntimeException("Cannot find any of " . print_r($searchTokens, true));
    }

    public function revWhileNot(Array $searchTokens)
    {
        $tokens = $this->tokens;
        for ($i = &$this->offset; $i >= 0; $i--) {
            if (is_array($tokens[$i])) {
                if (in_array($tokens[$i][0], $searchTokens)) {
                    return $this;
                }
            } else if (in_array($tokens[$i], $searchTokens)) {
                return $this;
            }
        }
        throw new \RuntimeException("{revWhileNot} Cannot find any of " . print_r($searchTokens, true));
    }

    public function revWhile(Array $searchTokens)
    {
        $tokens = $this->tokens;
        for ($i = &$this->offset; $i >= 0; $i--) {
            if (is_array($tokens[$i])) {
                if (!in_array($tokens[$i][0], $searchTokens)) {
                    return $this;
                }
            } else if (!in_array($tokens[$i], $searchTokens)) {
                return $this;
            }
        }
        throw new \RuntimeException("{revWhile} when to start " . print_r($searchTokens, true));
    }

    public function moveWhile(Array $searchTokens)
    {
        $tokens = $this->tokens;
        for ($i = &$this->offset; $i < $this->total; $i++) {
            if (is_array($tokens[$i])) {
                if (!in_array($tokens[$i][0], $searchTokens)) {
                    break;
                }
            } else if (!in_array($tokens[$i], $searchTokens)) {
                break;
            }
        }
        return $this;
    }

    public function moveWhileNot(Array $searchTokens)
    {
        $tokens = $this->tokens;
        for ($i = &$this->offset; $i < $this->total; $i++) {
            if (is_array($tokens[$i])) {
                if (in_array($tokens[$i][0], $searchTokens)) {
                    return $this;
                }
            } else if (in_array($tokens[$i], $searchTokens)) {
                return $this;
            }
        }
        throw new \RuntimeException("{moveWhileNot} Cannot find any of " . print_r($searchTokens, true));
    }

    public function move($inc = 1)
    {
        $this->offset += $inc;
        return $this;
    }

    public function setOffset($int)
    {
        $this->offset = $int;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getStack()
    {
        return $this->stack;
    }

    public function getTokenType()
    {
        return $this->tokens[$this->offset][0];
    }

    public function getToken()
    {
        if (!array_key_exists($this->offset, $this->tokens)) {
            return NULL;
        }
        return $this->tokens[$this->offset];
    }

    public function getTokens($start, $len)
    {
        return array_slice($this->tokens, $start, $len);
    }

    public function run()
    {
        $tokens = $this->tokens;
        $trait  = defined('T_TRAIT') ? T_TRAIT : -1; 
        $i = &$this->offset;
        for($i=0; $i < $this->total; $i++) {
            $value = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
            switch ($value) {
            case T_CURLY_OPEN:
            case T_DOLLAR_OPEN_CURLY_BRACES:
                $this->stack[] = T_VARIABLE;
                $this->level++;
                break;
            case '{':
                $x = $i;
                $this->revWhileNot(array(
                    T_FUNCTION, T_CLASS, T_NAMESPACE, T_IF, T_ELSE, 
                    T_WHILE, T_FOR, T_FOREACH, T_DO, T_ELSEIF, T_INTERFACE,
                    T_TRY, T_CATCH, $trait
                ));
                $this->stack[] = $tokens[$i][0];
                $this->level++;
                $i = $x;
                break;
            case '}':
                $tok = array_pop($this->stack);
                $this->level--;
                break;
            }

            $this->line += substr_count($value, "\n");

            if (isset($this->events[$value])) {
                $x = $i;
                foreach ($this->events[$value] as $callback) {
                    call_user_func($callback, $this, $tokens[$i]);
                }
                $i = $x;
            }
        }
    }

    public function getLine()
    {
        return $this->line;
    }

    public function setTokens(Array $tokens)
    {
        $this->tokens = $tokens;
        $this->total  = count($tokens);
    }

    public function on($event, $callback)
    {
        if (!is_callable($callback)) {
            throw new \RuntimeException("{$callback} is not callable");
        }
        if (empty($this->events[$event])) {
            $this->events[$event] = array();
        }
        $this->events[$event][] = $callback;
    }
}
