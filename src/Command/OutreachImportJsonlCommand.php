<?php

declare(strict_types=1);

namespace Survos\OutreachBundle\Command;

use Survos\JsonlBundle\IO\JsonlReader;
use Survos\OutreachBundle\Service\ConferenceRegistrantUpserter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('outreach:import:jsonl', 'Import normalized outreach JSONL into organizations and contacts')]
final class OutreachImportJsonlCommand
{
    public function __construct(
        private readonly ConferenceRegistrantUpserter $upserter,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Path to a normalized outreach .jsonl or .jsonl.gz file')]
        string $input,
        #[Option('Apply one or more tags to every imported row')]
        array $tag = [],
        #[Option('Maximum rows to import')]
        ?int $limit = null,
        #[Option('Flush every N rows')]
        int $batchSize = 200,
        #[Option('Validate and count rows without writing to the database')]
        bool $dryRun = false,
    ): int {
        if (!is_file($input)) {
            $io->error(sprintf('File not found: %s', $input));
            return Command::FAILURE;
        }

        if ($batchSize < 1) {
            $io->error('--batch-size must be >= 1');
            return Command::FAILURE;
        }

        $io->title('Outreach JSONL Import');
        $io->text(sprintf('Input: %s', $input));
        if ($tag !== []) {
            $io->text(sprintf('Tags: %s', implode(', ', $tag)));
        }
        if ($dryRun) {
            $io->note('Dry-run mode: rows will be normalized and validated but not persisted.');
        }

        $reader = JsonlReader::open($input);
        $processed = 0;
        $orgCreated = 0;
        $contactCreated = 0;

        foreach ($reader as $row) {
            if (!is_array($row)) {
                continue;
            }

            $processed++;
            $shouldFlush = !$dryRun && ($processed % $batchSize === 0);
            $result = $this->upserter->upsert($row, $tag, $shouldFlush && !$dryRun, $dryRun);

            $orgCreated += $result->organizationCreated ? 1 : 0;
            $contactCreated += $result->contactCreated ? 1 : 0;

            if ($limit !== null && $processed >= $limit) {
                break;
            }
        }

        if (!$dryRun && $processed > 0 && ($processed % $batchSize) !== 0) {
            $this->upserter->flush();
        }

        $io->success(sprintf(
            'Processed %d row(s); organizations created=%d, contacts created=%d%s',
            $processed,
            $orgCreated,
            $contactCreated,
            $dryRun ? ' [dry-run]' : ''
        ));

        return Command::SUCCESS;
    }
}
