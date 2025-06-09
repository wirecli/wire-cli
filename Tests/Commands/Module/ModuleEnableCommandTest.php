<?php

declare(strict_types=1);

namespace Tests\Commands\Module;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Wirecli\Commands\Module\ModuleEnableCommand;

/**
 * Class ModuleEnableCommandTest
 *
 * @package Tests\Commands\Module
 */
class ModuleEnableCommandTest extends TestCase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->application->add(new ModuleEnableCommand());
        
        $command = $this->application->find('module:enable');
        $this->commandTester = new CommandTester($command);
    }

    public function testConfigureSetsCorrectNameAndDescription()
    {
        $command = $this->application->find('module:enable');
        
        $this->assertEquals('module:enable', $command->getName());
        $this->assertEquals('Enables provided module(s)', $command->getDescription());
    }

    public function testConfigureHasRequiredArguments()
    {
        $command = $this->application->find('module:enable');
        $definition = $command->getDefinition();
        
        $this->assertTrue($definition->hasArgument('modules'));
        $this->assertTrue($definition->hasOption('github'));
        $this->assertTrue($definition->hasOption('branch'));
    }

    public function testGithubOptionIsOptional()
    {
        $command = $this->application->find('module:enable');
        $definition = $command->getDefinition();
        $githubOption = $definition->getOption('github');
        
        $this->assertFalse($githubOption->isValueRequired());
        $this->assertTrue($githubOption->isValueOptional());
    }

    public function testBranchOptionIsOptional()
    {
        $command = $this->application->find('module:enable');
        $definition = $command->getDefinition();
        $branchOption = $definition->getOption('branch');
        
        $this->assertFalse($branchOption->isValueRequired());
        $this->assertTrue($branchOption->isValueOptional());
    }

    public function testCommandHandlesCommaSeparatedModules()
    {
        $command = $this->application->find('module:enable');
        $definition = $command->getDefinition();
        $modulesArgument = $definition->getArgument('modules');
        
        $this->assertEquals('modules', $modulesArgument->getName());
        $this->assertStringContainsString('comma separated', $modulesArgument->getDescription());
    }
} 