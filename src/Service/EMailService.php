<?php


namespace App\Service;


use App\Entity\EMail\EMailRecipient;
use App\Entity\EMail\EmailSending;
use App\Entity\EMail\EmailSendingTask;
use App\Entity\EMail\EMailTemplate;
use App\Repository\EMail\EmailSendingRepository;
use App\Repository\EMail\EmailSendingTaskRepository;
use App\Repository\EMail\EMailTemplateRepository;
use App\Security\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Class EMailService
 * @package App\Service
 */
//TODO: Exceptions abfangen
class EMailService
{
	const APPLICATIONHOOKS = [
		"-" => null,
		"REGISTRATION_CONFIRMATION" => "/email/REGISTRATION_CONFIRMATION.html.twig",
		"ADMIN_ERRORMAIL" => "/email/ADMIN_ERRORMAIL.html.twig",
		"CONTACT_TO_ADMIN" => "/email/CONTACT_TO_ADMIN.html.twig",
		"CONTACT_TO_USER" => "/email/CONTACT_TO_USER.html.twig",
	];
	const NEWSLETTER_DESIGNS = [
		"STANDARD" => "/email/STANDARD.html.twig",
	];

	protected $mailer;
	protected $senderAddress;
	protected $userService;
	protected $em;
	protected $templateRepository;
	protected $sendingRepository;
	protected $logger;
	protected $tasks;
	protected $twig;
	protected $systemMessageUser;

	public function __construct(MailerInterface $mailer,
	                            LoggerInterface $logger,
	                            EmailSendingTaskRepository $emailSendingTaskRepository,
	                            EMailTemplateRepository $templateRepository,
	                            EmailSendingRepository $sendingRepository,
	                            EntityManagerInterface $em,
	                            Environment $twig,
	                            UserService $userService)
	{
		$this->mailer = $mailer;
		$this->userService = $userService;
		$mailAddress = $_ENV['MAILER_DEFAULT_SENDER_EMAIL'];
		$mailName = $_ENV['MAILER_DEFAULT_SENDER_NAME'];
		$this->senderAddress = new Address($mailAddress, $mailName);
		$this->logger = $logger;
		$this->em = $em;
		//repos
		$this->tasks = $emailSendingTaskRepository;
		$this->templateRepository = $templateRepository;
		$this->sendingRepository = $sendingRepository;
		$this->twig = $twig;
		$this->systemMessageUser = $userService->getUsersInfoByUuid([$_ENV["MAILER_SYSTEM_MESSAGE_USER_GUID"]])[0];
	}

	public function sendByApplicationHook(string $applicationHook, User $user, string $stepname = null, array $payload = null)
	{
		//prüfen ob gültiger Applicationhook übergeben wurde
		if (in_array($applicationHook, self::APPLICATIONHOOKS)) {
			$hook = $applicationHook;
			//Template dazu suchen
			$template = $this->templateRepository->findOneBy(["applicationHook" => $hook]);
			if ($template != null) {
				$this->sendEMail($template, $user, $payload);
			} else {
				//payload für Fehlermeldung generieren

				//TODO Errorhandling fertigmachen
				$payload = $payload ?? [];
				$payload["ERROR_MESSAGE"] = "E-Mail Template für ApplicationHook $hook nicht gefunden";
				$payload["USER"] = $user->getUuid() . " => " . $user->getNickname() . " => " . $user->getEmail();
				if ($stepname != null) {
					$payload["STEP"] = "Fehler-Step : $stepname";
				}
				$this->logger->critical("Fehler in Mailversand: " . json_encode($payload));
				if ($stepname == "TemplateNotFoundMessage") {
					$errorTemplate = new  EMailTemplate();
					$errorTemplate->setSubject("Template für ErrorMail nicht gefunden");
					$errorTemplate->setBody("Template für $stepname nicht gefunden");
					$this->sendEMail($errorTemplate, $this->systemMessageUser);
				} else {
					$this->sendByApplicationHook(self::APPLICATIONHOOKS["ADMIN_ERRORMAIL"], $this->systemMessageUser, "TemplateNotFoundMessage", $payload);
				}

			}
		}
	}

	private function sendEMail(EMailTemplate $template, User $user, array $payload = null): ?string
	{
		$recipient = new EMailRecipient($user);
		$error = null;

		if ($recipient == null || empty($recipient->getEmailAddress())) {
			$error = "No email adress was given or user object was null";
		}
		if ($error != null)
			return $error;

		try {
			$template = $this->renderTemplate($template, $user, $payload);
			$email = (new Email())->from($this->senderAddress)
			                      ->to($recipient->getAddressObject())
			                      ->subject($template->getSubject())
			                      ->text(strip_tags($template->getBody()))// TODO Lösung für Text-only finden
			                      ->html($template->getBody());
			$this->mailer->send($email);
		} catch (Exception $e) {
			$this->logger->error($e);
			$error = $e;
		} catch (TransportExceptionInterface $e) {
			$this->logger->error($e);
			$error = $e;
		} finally {
			return $error;
		}
	}

	public function renderTemplate(EMailTemplate $template, User $user, array $payload = null): EMailTemplate
	{
		$recipient = new EMailRecipient($user);
		$text = $template->getBody();
		$subject = $template->getSubject();

		$text = $this->replaceVariableTokens($text, $recipient);
		$subject = $this->replaceVariableTokens($subject, $recipient);

		//template auf E-Mail clonen, damit es verändert werden kann
		$email = clone $template;
		$email->setBody($text);
		$email->setSubject($subject);

		//TODO twig render eventuell mit email render tauschen
		$designFile = $this->getDesignFile($email);
		if ($designFile == "TEXT") {
			$html = $text;
		} else {
			$html = $this->twig->render($designFile, ["template" => $email, "user" => $user, "payload" => $payload]);
		}

		$email->setBody($html);

		return $email;
	}

	private function replaceVariableTokens($text, EMailRecipient $mailRecipient)
	{
		$errors = [];
		$recipientData = $mailRecipient->getDataArray();
		preg_match_all('/({{2}([^}]+)}{2})/', $text, $matches);
		if (isset($matches[0]) && count($matches[0])) {
			foreach ($matches[0] as $match) {
				$variableName = str_replace('{', '', $match);
				$variableName = str_replace('}', '', $variableName);
				$variableName = trim(strtolower($variableName));
				$filled = $recipientData[$variableName];
				if (empty($filled)) {
					$error = "Variable $variableName not valid: " . sprintf($mailRecipient);
					$this->logger->error($error);
					array_push($errors, $error);
				}
				if (count($errors) > 0)
					throw  new  Exception("MailData is not valid: " . implode(',', $errors));
				$text = str_replace($match, $filled, $text);
			}
		}
		return $text;
	}

	private function getDesignFile(EMailTemplate $template): string
	{
		$design = $template->getDesignFile();
		if ($template->isApplicationHooked()) {
			$design = $template->getApplicationHook();
		}
		if ($design == null || empty($design)) {
			$design = 'TEXT';
		}

		return $design;
	}

	public function createSending(EMailTemplate $template, string $userGroupName = null)
	{
		$userGroupName = $userGroupName ?? 'TEST'; //TODO ausbauen, wenn mal Gruppen verfügbar sind

		$sending = new  EmailSending();
		$sending->setEMailTemplate(clone $template)
		        ->setRecipientGroup($userGroupName)
		        ->setStatus("Sendung erstellt");

		$this->em->persist($sending);
		$this->em->flush();

	}

	public function createSendingTasksAllSendings(SymfonyStyle $io = null)
	{

		$sendings = $this->sendingRepository->findNewsletterSendable();
		if (count($sendings) == 0) {
			$io->note("No sendable newsletters found");
		}
		foreach ($sendings as $sending) {
			if ($sending->getStartTime() <= new  DateTime()) {
				$sendingName = $sending->getEMailTemplate()->getName() . ' for UserGroup ' . $sending->getRecipientGroup();
				$io->note("Starting: $sendingName");
				$countGenerated = $this->createSendingTasks($sending);
				$io->note("Finished: $sendingName $countGenerated Mails generated");
				$sending->setStatus('E-Mail Jobs generiert, beginne mit Versendung.');
				$this->em->persist($sending);
				$this->em->flush();
			}
		}
	}

	public function createSendingTasks(EmailSending $sending): int
	{
		//User holen
		$generatedCount = 0;
		$errorCount = 0;
		$users = $this->getPossibleEmailRecipients($sending->getRecipientGroup());
		$template = $sending->getEMailTemplate();
		$sending->setStatus('Empfänger werden geladen');
		$sending->setIsInSending(true);
		$sending->setRecipientCount($users->count());
		$this->em->persist($sending);
		$this->em->flush();

		foreach ($users as $user) {
			if ($this->sendEMail($template, $user) == null) {
				$generatedCount++;
			} else {
				$errorCount++;
			};
		}
		$sending->setRecipientCountGenerated($generatedCount);
		$sending->setErrorCount($errorCount);
		$this->em->persist($sending);
		$this->em->flush();

		return $generatedCount;
	}


	private function getPossibleEmailRecipients($group = null): ArrayCollection
	{
		//TODO: Mockdaten gegen echte Daten austauschen
		//$usersToFind = hatasSuperMethodeZumAbholenDerUserAusDenUsergruppen();
		$usersToFind = [
			'00000000-0000-0000-0000-000000000000',
			'00000000-0000-0000-0000-000000000001',
			'00000000-0000-0000-0000-000000000002',
			'00000000-0000-0000-0000-000000000003',
			'00000000-0000-0000-0000-000000000004',
		];
		$users = $this->userService->getUsersByUuid($usersToFind);
		return new ArrayCollection($users);
	}

	/*
	 * Sending methods
	 */

	public function sendEmailTasks(EMailTemplate $template = null) //DEPRECATED
	{
		$tasks = $this->getSendableEMailTasks($template);

		//UserIds von IDM holen
		$userIds = array_map(function (EmailSendingTask $task) {
			return $task->getRecipientId();
		}, $tasks);
		$users = $this->userService->getUsersByUuid($userIds);

		//lookup bauen, damit nachher schnell gesucht werden kann
		$userLookup = [];
		foreach ($users as $user) {
			$userLookup[$user->getUuid()] = $user;
		}

		foreach ($tasks as $task) {
			//wenn template mitgegeben, kommen nur Tasks aus dem Template, wenn nicht(Multi Template sending), dann sucht sich der Task sein Template
			if ($template == null)
				$template = $task->getEMailTemplate();

			if ($template->getIsManualSendable()) {
				$recipientId = $task->getRecipientId();
				$recipient = $userLookup[$recipientId];

				//email Versand versuchen
				$sendingError = $this->sendEMail($template, $recipient);
				if ($sendingError == null) { //TODO auf Exceptions umbauen
					$task->setIsSent();
					$this->em->persist($task);
					$this->em->flush();
				}
			}
		}
	}

	/**
	 * @param EMailTemplate|null $template
	 * @param null $limit
	 *
	 * @return EmailSendingTask[]
	 */
	private function getSendableEMailTasks(EMailTemplate $template = null) //DEPRECATED
	{
		if ($template != null && $template->getIsPublished()) {
			$tasks = $this->tasks->findBy(['EMailTemplate' => $template, 'isSent' => false, 'isSendable' => true], ['created' => 'ASC']);
		} else {
			$tasks = $this->tasks->findBy(['isSent' => false, 'isSendable' => true], ['created' => 'ASC']);
		}
		return $tasks;
	}

	public function sendSingleEmail(EMailTemplate $template, User $user)
	{
		$this->sendEMail($template, $user);
	}

	public function deleteTemplate(EMailTemplate $template)
	{
		if ($template->getIsDeletable()) {
			//Alle Tasks löschen
			$template->getEmailSendingTasks()->forAll(function (EmailSendingTask $task) {
				$this->em->remove($task);
			});
			$this->em->remove($template);
			$this->em->flush();
		}
	}

	public function deleteSending(EmailSending $sending)
	{
		if ($sending->getIsDeletable()) {
			$this->em->remove($sending);
			$this->em->flush();
		}
	}


	public function sendContactEMail($data): ?string
	{
		$error = null;
		$text = "";
		$text .= "<b>Name:</b>" . $data["firstname"] . $data["surname"] . "<br>";
		$text .= "<b>E-Mail:</b> " . $data["email"] . "<br>";
		$text .= "<b>Subject: </b>" . $data["subject"] . "<br>";
		$text .= "<b>Message: </b>" . $data["message"] . "<br>";

		try {
			$email = (new Email())->from($this->senderAddress)
			                      ->to($this->senderAddress)
			                      ->subject('Kontaktanfrage')
			                      ->html($text);
			$this->mailer->send($email);
		} catch (Exception $e) {
			$this->logger->error($e);
			$error = $e;
		} catch (TransportExceptionInterface $e) {
			$this->logger->error($e);
			$error = $e;
		} finally {
			return $error;
		}
	}


}
