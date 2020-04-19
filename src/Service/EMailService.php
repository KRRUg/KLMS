<?php


namespace App\Service;


use App\Entity\EMail\EMailRecipient;
use App\Entity\EMail\EmailSendingTask;
use App\Entity\EMail\EMailTemplate;
use App\Repository\EMail\EmailSendingTaskRepository;
use App\Repository\EMail\EMailTemplateRepository;
use App\Security\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
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
		"ADMIN_ERRORMAIL" => "/email/ADMIN_ERRORMAIL.html.twig"
	];
	const NEWSLETTER_DESIGNS = [
		"STANDARD" => "/email/STANDARD.html.twig",
	];

	protected $mailer;
	protected $senderAddress;
	protected $userService;
	protected $usersForActiveSending;
	protected $em;
	protected $templateRepository;
	protected $logger;
	protected $tasks;
	protected $twig;
	protected $systemMessageUser;

	public function __construct(MailerInterface $mailer,
	                            LoggerInterface $logger,
	                            EmailSendingTaskRepository $emailSendingTaskRepository,
	                            EMailTemplateRepository $templateRepository,
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
		$this->twig = $twig;
		$this->usersForActiveSending = new ArrayCollection();
		$this->systemMessageUser = $userService->getUsersInfoByUuid([$_ENV["MAILER_SYSTEM_MESSAGE_USER_GUUID"]])[0];
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

	public function createSendingTasks(EMailTemplate $template)
	{
		$errors = [];
		$sentTaskUserIds = $template->getEmailSendingTasks()->map(function (EmailSendingTask $task) {
			return $task->getRecipientId();
		});
		$userIds = $this->usersForActiveSending->map(function (EMailRecipient $recipient) {
			return $recipient->getId();
		});
		$diff = array_diff($userIds->toArray(), $sentTaskUserIds->toArray());

		if (count($diff) == 0) {
			array_push($errors, "Sending Task for recipients already created, no more recipients without E-Mail sending");
		}

		try {
			foreach ($diff as $user) {
				$task = new EmailSendingTask();
				$task->setRecipientId($user);
				$task->setIsSendable(true);
				$this->em->persist($task);
				$template->addEmailSendingTask($task);
			}
			$this->em->flush();

		} catch (UniqueConstraintViolationException $e) {
			$error = $template->getName() . "[" . $template->getId() . "] => " . $task->getRecipientId() . " already exists for email,  Process was not successful!";
			array_push($errors, $error);
			$this->logger->warning($error);
		}
		return $errors;
	}

	public function getPossibleEmailRecipients($group = null): ArrayCollection
	{
		//TODO: Mockdaten gegen echte Daten austauschen
		$usersToFind = [
			'00000000-0000-0000-0000-000000000000',
			'00000000-0000-0000-0000-000000000001',
			'00000000-0000-0000-0000-000000000002',
			'00000000-0000-0000-0000-000000000003',
			'00000000-0000-0000-0000-000000000004',
		];
		$users = $this->userService->getUsersByUuid($usersToFind);

		foreach ($users as $user) {
			$this->addRecipient($user);
		}

		return $this->usersForActiveSending;
	}

	/*
	 * Sending methods
	 */

	private function addRecipient(User $user)
	{
		if ($user != null) {
			$this->usersForActiveSending->add(new EMailRecipient($user));
		}
	}

	public function getTemplateVariables()
	{
		$testRecipient = $this->getTestRecipient();
		return $testRecipient->getDataArray();
	}

	private function getTestRecipient()
	{
		$user = new User();
		$user->setUuid("0325a40e-a254-4c2f-be60-97e73307b720");
		$user->setNickname("Testie");
		$user->setFirstname("Testine");
		$user->setSurname("Tester");
		$user->setEmail("testine.tester@test.com");
		return new  EMailRecipient($user);
	}

	public function sendEmailTasks(EMailTemplate $template = null)
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
	private function getSendableEMailTasks(EMailTemplate $template = null)
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
}
