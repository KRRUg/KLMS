<?php

namespace App\Command;

use App\Repository\EMail\EmailSendingRepository;
use App\Service\EMailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendmailCommand extends Command
{
	protected static $defaultName = 'klms:service:sendmail';
	protected $mailService;
	protected $sendingRepository;

	public function __construct(EMailService $mailService, EmailSendingRepository $sendingRepository)
	{
		$this->mailService = $mailService;
		$this->sendingRepository = $sendingRepository;
		parent::__construct();
	}

	protected function configure()
	{
		$this
			->setDescription('Fills the message queue with newsletter emails')
			->addArgument('sendingId', InputArgument::OPTIONAL, 'ID OF THE SENDING OPTIONAL, IF NULL ALL WHICH HAS TO BE RENDERED WILL BE RENDERED')//->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$returnCode = 0;
		$io = new SymfonyStyle($input, $output);
		$sendingId = $input->getArgument('sendingId');

		if ($sendingId) {
			$io->note(sprintf('You passed an SENDING ID: %s', $sendingId));

			$sending = $this->sendingRepository->find($sendingId);
			if ($sending != null) {
				$io->note(sprintf('Generating E-Mails for SENDING ID: %s', $sendingId));
				$this->mailService->createSendingTasks($sending);
				$io->success('E-Mail queue has been filled with sending jobs');

			} else {
				$io->error('SENDING ID was not found');
				$returnCode = 1;
			}
		} else {
			$this->mailService->createSendingTasksAllSendings($io);
		}
		return $returnCode;
	}
}
