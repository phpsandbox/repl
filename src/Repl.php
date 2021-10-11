<?php

namespace PHPSandbox\Repl;

use Psy\Configuration;
use Psy\ExecutionLoopClosure;
use Psy\Shell;
use PHPSandbox\Repl\OutputModifiers\OutputModifier;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Repl
{
    protected OutputInterface $output;

    protected Shell $shell;

    protected OutputModifier $outputModifier;

    protected ?string $classMapRootPath = null;

    private array $casters = [];

    private array $commands = [];

    public function __construct(OutputModifier $outputModifier, string $rootPath, array $casters = [], array $commands = [])
    {
        $this->output = new BufferedOutput;
        $this->classMapRootPath = $rootPath;
        $this->casters = $casters;
        $this->commands = $commands;

        $this->shell = $this->createShell();

        $this->outputModifier = $outputModifier;
    }

    public function execute(string $phpCode): string
    {
        $phpCode = $this->removeComments($phpCode);

        $this->shell->addInput($phpCode);

        $closure = new ExecutionLoopClosure($this->shell);

        $closure->execute();

        $output = $this->cleanOutput($this->output->fetch());

        return $this->outputModifier->modify($output);
    }

    protected function createShell(): Shell
    {
        $config = new Configuration([
            'updateCheck' => 'never',
            'configFile' => null
        ]);

        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');

        $config->getPresenter()->addCasters($this->casters);

        $shell = new Shell($config);
        $shell->setOutput($this->output);
        $shell->addCommands($this->commands);

//        $composerClassMap = sprintf('%s/vendor/composer/autoload_classmap.php', $this->classMapRootPath);
//
//        if (file_exists($composerClassMap)) {
//            ClassAliasAutoloader::register($shell, $composerClassMap);
//        }

        return $shell;
    }

    public function removeComments(string $code): string
    {
        $tokens = token_get_all("<?php\n".$code.'?>');

        return array_reduce($tokens, function ($carry, $token) {
            if (is_string($token)) {
                return $carry.$token;
            }

            $text = $this->ignoreCommentsAndPhpTags($token);

            return $carry.$text;
        }, '');
    }

    protected function ignoreCommentsAndPhpTags(array $token)
    {
        [$id, $text] = $token;

        if ($id === T_COMMENT) {
            return '';
        }
        if ($id === T_DOC_COMMENT) {
            return '';
        }
        if ($id === T_OPEN_TAG) {
            return '';
        }
        if ($id === T_CLOSE_TAG) {
            return '';
        }

        return $text;
    }

    protected function cleanOutput(string $output): string
    {
        $output = preg_replace('/(?s)(<aside.*?<\/aside>)|Exit:  Ctrl\+D/ms', '$2', $output);

        return trim($output);
    }

    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    public function setCasters(array $casters): void
    {
        $this->casters = $casters;
    }
}
