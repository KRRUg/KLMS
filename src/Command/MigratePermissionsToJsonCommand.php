<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-permissions-to-json',
    description: 'Migrate UserAdmin permissions from serialized to JSON'
)]
class MigratePermissionsToJsonCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Migrating permissions from serialized array to JSON');

        // Spalte zu JSON ändern
        $io->section('Step 1: Add temporary column');
        $this->connection->executeStatement('ALTER TABLE user_admin ADD COLUMN IF NOT EXISTS permissions_json JSONB');

        // Daten konvertieren
        $io->section('Step 2: Convert data');
        $rows = $this->connection->fetchAllAssociative('SELECT uuid, permissions FROM user_admin');
        
        $io->progressStart(count($rows));
        
        foreach ($rows as $row) {
            $uuid = $row['uuid'];
            $serialized = $row['permissions'];
            
            $array = @unserialize($serialized);
            if ($array === false && $serialized !== 'b:0;') {
                $array = [];
            }
            
            $json = json_encode($array ?: []);
            
            $this->connection->executeStatement(
                'UPDATE user_admin SET permissions_json = :json::jsonb WHERE uuid = :uuid',
                ['json' => $json, 'uuid' => $uuid]
            );
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();

        // Alte Spalte löschen, neue umbenennen
        $io->section('Step 3: Swap columns');
        $this->connection->executeStatement('ALTER TABLE user_admin DROP COLUMN permissions');
        $this->connection->executeStatement('ALTER TABLE user_admin RENAME COLUMN permissions_json TO permissions');
        $this->connection->executeStatement('ALTER TABLE user_admin ALTER COLUMN permissions SET NOT NULL');
        $this->connection->executeStatement('ALTER TABLE user_admin ALTER COLUMN permissions SET DEFAULT \'[]\'::jsonb');

        $io->success('Migration completed successfully!');

        return Command::SUCCESS;
    }
}
