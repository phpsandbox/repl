<?php

namespace PHPSandbox\Repl\OutputModifiers;

interface OutputModifier
{
    public function modify(string $output = ''): string;
}
