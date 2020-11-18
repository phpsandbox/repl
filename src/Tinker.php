<?php

namespace PHPSandbox\Repl;

use Illuminate\Support\Collection;
use Psy\Configuration;
use Psy\ExecutionLoopClosure;
use Psy\Shell;
use PHPSandbox\Repl\OutputModifiers\OutputModifier;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Tinker
{
    protected OutputInterface $output;

    protected Shell $shell;

    protected OutputModifier $outputModifier;

    protected ?string $psyConfigFile = null;

    protected ?string $classMapRootPath = null;

    public function __construct(OutputModifier $outputModifier)
    {
        $this->output = new BufferedOutput;

        $this->shell = $this->createShell();

        $this->outputModifier = $outputModifier;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function setClassMapRootPath(string $classMapRootPath): self
    {
        $this->classMapRootPath = $classMapRootPath;

        return $this;
    }

    public function setPsyConfig(string $psyConfigFile = null): self
    {
        $this->psyConfigFile = $psyConfigFile;

        return $this;
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
            'configFile' => $this->psyConfigFile ?? null,
        ]);

        $config->setHistoryFile(defined('PHP_WINDOWS_VERSION_BUILD') ? 'null' : '/dev/null');

        $config->getPresenter()->addCasters([
            Collection::class => 'Laravel\Tinker\TinkerCaster::castCollection',
//            Model::class => 'Laravel\Tinker\TinkerCaster::castModel',
//            Application::class => 'Laravel\Tinker\TinkerCaster::castApplication',
        ]);

        $shell = new Shell($config);

        $shell->setOutput($this->output);

        $composerClassMap = sprintf('%s/vendor/composer/autoload_classmap.php', $this->classMapRootPath);

        if (file_exists($composerClassMap)) {
            ClassAliasAutoloader::register($shell, $composerClassMap);
        }

        return $shell;
    }

    public function removeComments(string $code): string
    {
        $tokens = collect(token_get_all("<?php\n".$code.'?>'));

        return $tokens->reduce(function ($carry, $token) {
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
}
