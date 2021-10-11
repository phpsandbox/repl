<?php

require "vendor/autoload.php";

use PHPSandbox\Repl\OutputModifiers\PrefixDateTime;
use PHPSandbox\Repl\Repl;

$tinker = new Repl(getcwd(), [new PrefixDateTime]);

echo $tinker->execute("[1, 2, 3, 4]");

exit;
