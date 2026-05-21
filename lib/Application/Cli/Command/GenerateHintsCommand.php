<?php

declare(strict_types=1);

namespace Kosmosafive\Commandline\Application\Cli\Command;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Web\Json;
use FilesystemIterator;
use Kosmosafive\Bitrix\Diag\Profiler;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'kosmosafive.commandline:generate-hints',
    description: 'Generate hints for autocompletion'
)]
class GenerateHintsCommand extends Command
{
    use LockableTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return Command::SUCCESS;
        }

        try {
            $profiler = new Profiler();

            $parser = new ParserFactory()->createForHostVersion();
            $nodeFinder = new NodeFinder();

            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());

            $documentRoot = Application::getDocumentRoot();

            $dirs = [
                $documentRoot . '/local/modules',
                $documentRoot . '/bitrix/modules',
            ];

            $moduleId = 'kosmosafive.commandline';
            $moduleConfiguration = Configuration::getValue($moduleId);
            $configurationDirs = $moduleConfiguration['dirs'] ?? [];

            if (is_array($configurationDirs)) {
                $dirs = $configurationDirs;
            }

            $suggestions = [];

            $progressBar = $this->createProgressBarWithMessage($output, 6);
            $progressBar->setMessage('Let\'s start collecting classes');
            $progressBar->start();

            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    continue;
                }

                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($files as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $code = file_get_contents($file->getRealPath());
                    $stmts = $parser->parse($code);
                    if (!$stmts) {
                        continue;
                    }

                    $stmts = $traverser->traverse($stmts);

                    $classes = $nodeFinder->findInstanceOf($stmts, Class_::class);
                    foreach ($classes as $class) {
                        $shortName = $class->name?->toString();
                        $fullName = $class->namespacedName ? '\\' . $class->namespacedName->toString() : $shortName;

                        if (!$fullName) {
                            continue;
                        }

                        $suggestions[] = [
                            'label' => $fullName,
                            'kind' => 6,
                            'filterText' => $fullName,
                            'insertText' => $fullName,
                            'documentation' => "Full Path: $fullName",
                        ];

                        foreach ($class->getMethods() as $method) {
                            if ($method->isPublic()) {
                                $methodName = $method->name?->toString();
                                $params = $method->getParams();

                                if (is_object($params)) {
                                    $paramsStr = $params->map(function ($param) {
                                        return $param->name?->toString();
                                    })->join(', ');
                                } elseif (is_array($params)) {
                                    $paramsStr = implode(', ', array_map(static function ($param) {
                                        return $param->name?->toString();
                                    }, $params));
                                } else {
                                    $paramsStr = '';
                                }

                                $suggestions[] = [
                                    'label' => "{$fullName}::{$methodName}",
                                    'shortLabel' => "{$shortName}::{$methodName}", // Добавляем это!
                                    'kind' => 1,
                                    'insertText' => "{$methodName}($paramsStr)", // Только имя метода
                                    'className' => $fullName,
                                    'shortClassName' => $shortName,
                                    'documentation' => "Метод класса $fullName",
                                ];
                            }
                        }

                        $progressBar->advance();
                    }
                }
            }

            $progressBar->finish();
            $progressBar->clear();

            $filename = $documentRoot . '/upload/monaco_hints.json';

            file_put_contents($filename, Json::encode($suggestions, JSON_UNESCAPED_UNICODE));

            $profiler->end();

            $duration = round($profiler->timer->duration, 2) . 's';

            $output->writeln("Done in {$duration}");
            $output->writeln("Total suggestions: " . count($suggestions));
        } catch (Throwable $throwable) {
            $output->writeln('<error>' . $throwable->getMessage() . ': ' . $throwable->getLine() . '</error>');
            return Command::FAILURE;
        } finally {
            $this->release();
        }

        return Command::SUCCESS;
    }

    protected function createProgressBar(OutputInterface $output, int $totalCount): ProgressBar
    {
        $progressBar = new ProgressBar($output, $totalCount);
        $progressBar->setEmptyBarCharacter("\033[31m█\033[0m");
        $progressBar->setProgressCharacter('');
        $progressBar->setBarCharacter("\033[32m█\033[0m");
        $progressBar->setFormat("%current%/%max% %bar% %percent:3s%% \n⚐ %elapsed:6s%/%estimated:-19s% 📈 %memory:6s%");

        return $progressBar;
    }

    protected function createProgressBarWithMessage(OutputInterface $output, int $totalCount): ProgressBar
    {
        $progressBar = $this->createProgressBar($output, $totalCount);
        $progressBar->setFormat(
            "%current%/%max% %bar% %percent:3s%% \n⚐ %elapsed:6s%/%estimated:-19s% 📈 %memory:6s% \n%message%"
        );

        return $progressBar;
    }
}
