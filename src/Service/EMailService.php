<?php


namespace App\Service;


use App\Entity\EMail\EmailSendingTask;
use App\Entity\EMail\EMailTemplate;
use App\Entity\EMail\EMailRecipient;
use App\Repository\EMail\EmailSendingTaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use App\Security\User;
use Twig\Environment;

/**
 * Class EMailService
 * @package App\Service
 */
//TODO: Exceptions abfangen
class EMailService
{
	protected $mailer;
	protected $senderAddress;
	protected $userService;
	protected $users = [];
	protected $em;
	protected $logger;
	protected $tasks;
	protected $twig;

	const APPLICATIONHOOK_DESIGNS = ["REGISTRATION_CONFIRMATION" => "REGISTRATION_CONFIRMATION.html.twig"];
	const DESIGNS = ["REGISTRATION_CONFIRMATION" => "REGISTRATION_CONFIRMATION.html.twig"];

	public function __construct(MailerInterface $mailer,
	                            LoggerInterface $logger,
	                            EmailSendingTaskRepository $emailSendingTaskRepository,
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
		$this->twig = $twig;
	}

	public function createSendingTasks(EMailTemplate $template)
	{
		foreach ($this->users as $user) {
			$task = new EmailSendingTask();
			$task->setRecipientId($user);
			$task->setIsSendable(true);
			$this->em->persist($task);
			$template->addEmailSendingTask($task);
		}
		$this->em->flush();
	}

	public function getPossibleEmailRecipients($group = null)
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

		return $this->users;
	}

	/*
	 * Helper Methods
	 */

	public function renderTemplate(EMailTemplate $template, User $user): EMailTemplate
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

		if (!empty($template->getDesignFile())) {
			//TODO twig render eventuell mit email render tauschen
			$html = $this->twig->render($email->getDesignFile(), ["template" => $email, "user" => $user]);
			$email->setBody($html);
		}

		return $email;
	}

	private function replaceVariableTokens($text, EMailRecipient $mailRecipient)
	{
		$errors = [];
		$recipientData = $mailRecipient->getDataArray();
		preg_match_all('/{{2}.*}{2}/', $text, $matches);
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

	private function addRecipient(User $user = null)
	{
		if ($user == null) {
			$recipient = $this->generateTestRecipient();
		} else {
			$recipient = new EMailRecipient($user);
		}
		array_push($this->users, $recipient);
	}

	public function getTemplateVariables()
	{
		$testRecipient = $this->generateTestRecipient();
		return $testRecipient->getDataArray();
	}

	private function generateTestRecipient()
	{
		$user = new User();
		$user->setUuid("0325a40e-a254-4c2f-be60-97e73307b720");
		$user->setNickname("Testie");
		$user->setFirstname("Testine");
		$user->setSurname("Tester");
		$user->setEmail("testine.tester@test.com");
		return new  EMailRecipient($user);
	}

	/*
	 * Sending methods
	 */
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

//TODO Application Hooking fertig machen
	public
	function sendByApplicationHook(string $applicationHook, User $user)
	{
	}

		public function sendSingleEmail(EMailTemplate $template, User $user)
	{
		$this->sendEMail($template, $user);
	}

	private function sendEMail(EMailTemplate $template, User $user)
	{
		$recipient = new EMailRecipient($user);
		$error = null;

		if ($recipient == null || empty($recipient->getEmailAddress())) {
			$error = "No email adress was given or user object was null";
		}
		if ($error != null)
			return $error;
		try {
			$template = $this->renderTemplate($template, $user);
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
