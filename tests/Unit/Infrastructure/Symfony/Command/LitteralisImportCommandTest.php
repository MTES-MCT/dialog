<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\DateUtilsInterface;
use App\Application\Integration\Litteralis\DTO\LitteralisCredentials;
use App\Application\MattermostInterface;
use App\Infrastructure\Integration\IntegrationReport\Reporter;
use App\Infrastructure\Integration\Litteralis\LitteralisExecutor;
use App\Infrastructure\Symfony\Command\LitteralisImportCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class LitteralisImportCommandTest extends TestCase
{
    private $enabledOrgs;
    private $credentials;
    private $logger;
    private $reporter;
    private $executor;
    private $dateUtils;
    private $mattermost;

    protected function setUp(): void
    {
        $this->enabledOrgs = ['mel', 'fougeres', 'lonslesaunier'];

        $this->credentials = (new LitteralisCredentials())
            ->add('mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', 'melpassword')
            ->add('fougeres', 'fe8ef74d-c948-4782-b6dd-979ca9b8ca67', 'fougerespassword')
            ->add('lonslesaunier', '8ac986f1-1c7e-4131-a485-8cc258bda1f3', 'lonslesaunierpassword');

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = $this->createMock(Reporter::class);
        $this->executor = $this->createMock(LitteralisExecutor::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->mattermost = $this->createMock(MattermostInterface::class);
    }

    private function createCommand(): LitteralisImportCommand
    {
        return new LitteralisImportCommand(
            $this->logger,
            $this->enabledOrgs,
            $this->credentials,
            $this->reporter,
            $this->executor,
            $this->dateUtils,
            $this->mattermost,
        );
    }

    public function testExecute(): void
    {
        $now = new \DateTimeImmutable();

        $this->dateUtils
            ->expects(self::exactly(2))
            ->method('getNow')
            ->willReturn($now);

        $this->reporter
            ->expects(self::once())
            ->method('setLogger')
            ->with($this->logger);

        $this->executor
            ->expects(self::exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['mel', '3048af70-e3f6-49d9-a0ff-10579fd8bf14', $now, $this->reporter],
                ['fougeres', 'fe8ef74d-c948-4782-b6dd-979ca9b8ca67', $now, $this->reporter],
                ['lonslesaunier', '8ac986f1-1c7e-4131-a485-8cc258bda1f3', $now, $this->reporter],
            )
            ->willReturn('Rapport');

        $this->mattermost
            ->expects(self::once())
            ->method('post')
            ->with(self::callback(function (string $text): bool {
                return str_contains($text, 'Rapport d\'intégration Litteralis')
                    && str_contains($text, 'mel')
                    && str_contains($text, 'fougeres')
                    && str_contains($text, 'lonslesaunier')
                    && str_contains($text, 'Importé avec succès');
            }));

        $command = $this->createCommand();
        $this->assertSame('app:litteralis:import', $command->getName());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }

    public function testExecuteError(): void
    {
        $now = new \DateTimeImmutable();
        $this->dateUtils->method('getNow')->willReturn($now);

        $matcher = self::exactly(3);
        $this->executor
            ->expects($matcher)
            ->method('execute')
            ->willReturnCallback(
                fn () => match ($matcher->getInvocationCount()) {
                    1 => 'report1' . PHP_EOL,
                    2 => throw new \Exception('Failed'),
                    3 => 'report3' . PHP_EOL,
                },
            );

        $this->mattermost
            ->expects(self::once())
            ->method('post')
            ->with(self::callback(function (string $text): bool {
                return str_contains($text, ':x: **fougeres**')
                    && str_contains($text, 'Failed');
            }));

        $commandTester = new CommandTester($this->createCommand());
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame(['report1', 'Organization "fougeres": import failed: Failed (Exception)', 'report3', 'Sending support report...', ''], explode(PHP_EOL, $commandTester->getDisplay()));
    }

    public function testExecuteSendsSupportReportWithExpectedPayload(): void
    {
        $now = new \DateTimeImmutable();
        $this->dateUtils->method('getNow')->willReturn($now);
        $this->reporter->method('setLogger')->with($this->logger);

        $this->executor
            ->expects(self::exactly(3))
            ->method('execute')
            ->willReturn('Rapport org');

        $this->mattermost
            ->expects(self::once())
            ->method('post')
            ->with(self::callback(function (string $text): bool {
                return str_contains($text, 'Rapport d\'intégration Litteralis')
                    && substr_count($text, ':white_check_mark:') === 3;
            }));

        $commandTester = new CommandTester($this->createCommand());
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
    }
}
