<?php

namespace PHPSandbox\Repl\OutputModifiers;

class PrefixDateTime implements OutputModifier
{
    public function modify(string $output = ''): string
    {
        return '<span class="text-dimmed">'.date('Y-m-d H:i:s', time()).'</span><br>'.$output;
    }
}
