<?php

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class SettingService
{
    private const TB_TYPE = 'type';
    private const TB_DESCRIPTION = 'description';
    public const TB_TYPE_STRING = 'string';
    public const TB_TYPE_HTML = 'html';
    public const TB_TYPE_URL = 'url';
    public const TB_TYPE_FILE = 'file';
    public const TB_TYPE_CONTENT = 'content';
    public const TB_TYPE_BOOL = 'bool';

    ////////////////////////////////////////////////
    /// Text block names
    ///////////////////////////////////////////////
    private const TEXT_BLOCK_KEYS = [
        "site.title" => [self::TB_DESCRIPTION => "Titel der Seite", self::TB_TYPE => self::TB_TYPE_STRING],
        "site.subtitle" => [self::TB_DESCRIPTION => "Untertitel der Seite", self::TB_TYPE => self::TB_TYPE_STRING],
        "site.about" => [self::TB_DESCRIPTION => "Über uns, Homepage links unten", self::TB_TYPE => self::TB_TYPE_HTML],
        "site.organisation" => [self::TB_DESCRIPTION => "Organisationsname / Vereinsname", self::TB_TYPE => self::TB_TYPE_STRING],

        "lan.page.paymentinfo" => [self::TB_DESCRIPTION => "Link zu den Einzahlungsinformationen", self::TB_TYPE => self::TB_TYPE_CONTENT],
        "lan.page.terms" => [self::TB_DESCRIPTION => "Link zu den AGBs", self::TB_TYPE => self::TB_TYPE_CONTENT],
        "lan.signupinfo" => [self::TB_DESCRIPTION => "Inhalt der beim \"Anmelden\" zu einer LAN angezeigt wird", self::TB_TYPE => self::TB_TYPE_HTML],
        "lan.signup.enabled" => [self::TB_DESCRIPTION => "LAN-Anmeldung erlauben", self::TB_TYPE => self::TB_TYPE_BOOL],
        "lan.seatmap.bg_image" => [self::TB_DESCRIPTION => "Sitzplan Hintergrundbild", self::TB_TYPE => self::TB_TYPE_FILE],

        "style.logo" => [self::TB_DESCRIPTION => "Logo", self::TB_TYPE => self::TB_TYPE_FILE],
        "style.bg_image" => [self::TB_DESCRIPTION => "Hintergrundbild", self::TB_TYPE => self::TB_TYPE_FILE],

        "email.register.subject" => [self::TB_DESCRIPTION => "Betreff der Registrierungsmail", self::TB_TYPE => self::TB_TYPE_STRING],
        "email.register.text" => [self::TB_DESCRIPTION => "Text der Registrierungsmail", self::TB_TYPE => self::TB_TYPE_HTML],
        "email.reset.subject" => [self::TB_DESCRIPTION => "Betreff der Passwort-Zurücksetzen Email", self::TB_TYPE => self::TB_TYPE_STRING],
        "email.reset.text" => [self::TB_DESCRIPTION => "Text der Passwort-Zurücksetzen Email", self::TB_TYPE => self::TB_TYPE_HTML],
        "email.notify.subject" => [self::TB_DESCRIPTION => "Betreff der Benachrichtigungs-Email", self::TB_TYPE => self::TB_TYPE_STRING],

        "link.fb" => [self::TB_DESCRIPTION => "Link zur Facebook Seite", self::TB_TYPE => self::TB_TYPE_URL],
        "link.insta" => [self::TB_DESCRIPTION => "Link zur Instagram Seite", self::TB_TYPE => self::TB_TYPE_URL],
        "link.steam" => [self::TB_DESCRIPTION => "Link zur Steam Gruppe", self::TB_TYPE => self::TB_TYPE_URL],
        "link.yt" => [self::TB_DESCRIPTION => "Link zur YouTube Seite", self::TB_TYPE => self::TB_TYPE_URL],
        "link.twitter" => [self::TB_DESCRIPTION => "Link zur Twitter Seite", self::TB_TYPE => self::TB_TYPE_URL],
        "link.discord" => [self::TB_DESCRIPTION => "Link zur Discord Server", self::TB_TYPE => self::TB_TYPE_URL],
        "link.teamspeak" => [self::TB_DESCRIPTION => "Teamspeak Invite Link", self::TB_TYPE => self::TB_TYPE_URL],

        // extend here
    ];

    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private SettingRepository $repo;
    private UploaderHelper $uploaderHelper;

    public function __construct(EntityManagerInterface $em, SettingRepository $repo, UploaderHelper $uploaderHelper, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->uploaderHelper = $uploaderHelper;
        $this->logger = $logger;
    }

    public static function getKeys(): array
    {
        return array_keys(self::TEXT_BLOCK_KEYS);
    }

    public static function validKey(string $key): bool
    {
        $key = strtolower($key);
        return array_key_exists($key, self::TEXT_BLOCK_KEYS);
    }

    public static function getType(string $key): string
    {
        return self::validKey($key) ? self::TEXT_BLOCK_KEYS[$key][self::TB_TYPE] : "";
    }

    public static function getDescription(string $key): string
    {
        return self::validKey($key) ? self::TEXT_BLOCK_KEYS[$key][self::TB_DESCRIPTION] : "";
    }

    public function isSet(string $key): bool
    {
        return self::validKey($key) && !empty($this->get($key));
    }

    public function getSettingObject(string $key): ?Setting
    {
        $key = strtolower($key);
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was requested by SettingService");
            return null;
        }
        return $this->repo->findByKey($key) ?? new Setting($key);
    }

    public function get(string $key): ?string
    {
        $key = strtolower($key);
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was requested by SettingService");
            return null;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            // valid key, but not yet crated
            return "";
        }
        if (self::getType($key) == self::TB_TYPE_FILE) {
            return $this->uploaderHelper->asset($block, 'file', Setting::class);
        } else {
            return $block->getText() ?? '';
        }
    }

    public function set(string $key, string $value)
    {
        $key = strtolower($key);
        if (!array_key_exists($key, self::TEXT_BLOCK_KEYS)) {
            $this->logger->error("Invalid key {$key} was to be set at SettingService");
            return;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            // create it
            $tb = new Setting($key);
            $tb->setText($value);
            $this->em->persist($tb);
            $this->em->flush();
        } else {
            // update it if necessary
            if ($block->getText() !== $value) {
                $block->setText($value);
                $this->em->persist($block);
                $this->em->flush();
            }
        }
    }

    public function remove(string $key): bool
    {
        $key = strtolower($key);
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be deleted by SettingService");
            return false;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            $this->logger->warning("Tried to delete non-existing key {$key}");
            return false;
        }
        $this->em->remove($block);
        $this->em->flush();
        return true;
    }

    public function lastModification(string $key): ?\DateTimeInterface
    {
        $key = strtolower($key);
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be deleted by SettingService");
            return null;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            return null;
        }
        return $block->getLastModified();
    }

    public static function getDescriptions(): array
    {
        $result = [];
        foreach (self::TEXT_BLOCK_KEYS as $key => $value) {
            $result[$key] = $value[self::TB_DESCRIPTION];
        }
        return $result;
    }

    public function getModificationDates(): array
    {
        $ret = [];
        $db = $this->repo->findAll();
        foreach ($db as $v) {
            $ret[$v->getKey()] = $v->getLastModified();
        }
        foreach (array_keys(self::TEXT_BLOCK_KEYS) as $key) {
            if (!array_key_exists($key, $ret)) {
                $ret[$key] = null;
            }
        }
        return $ret;
    }

    public function setSettingsObject(Setting $data)
    {
        $key = $data->getKey();
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be deleted by SettingService");
            return;
        }
        $this->em->persist($data);
        $this->em->flush();
    }
}
