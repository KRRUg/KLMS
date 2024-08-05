<?php

namespace App\Service;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class SettingService implements WipeInterface
{
    private const TB_TYPE = 'type';
    private const TB_DESCRIPTION = 'description';
    final public const TB_TYPE_STRING = 'string';
    final public const TB_TYPE_HTML = 'html';
    final public const TB_TYPE_URL = 'url';
    final public const TB_TYPE_FILE = 'file';
    final public const TB_TYPE_BOOL = 'bool';

    // //////////////////////////////////////////////
    // / Text block names
    // //////////////////////////////////////////////

    private const TEXT_BLOCK_KEYS = [
        'site.title' => [self::TB_DESCRIPTION => 'Titel der Seite', self::TB_TYPE => self::TB_TYPE_STRING],
        'site.title.show' => [self::TB_DESCRIPTION => 'Titel der Seite anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'site.subtitle' => [self::TB_DESCRIPTION => 'Untertitel der Seite', self::TB_TYPE => self::TB_TYPE_STRING],
        'site.subtitle.show' => [self::TB_DESCRIPTION => 'Untertitel der Seite anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'site.about' => [self::TB_DESCRIPTION => 'Über uns, Homepage links unten', self::TB_TYPE => self::TB_TYPE_HTML],
        'site.organisation' => [self::TB_DESCRIPTION => 'Organisationsname / Vereinsname', self::TB_TYPE => self::TB_TYPE_STRING],

        'sponsor.enabled' => [self::TB_DESCRIPTION => 'Sponsorenbanner einschalten', self::TB_TYPE => self::TB_TYPE_BOOL],
        'sponsor.banner.show' => [self::TB_DESCRIPTION => 'Sponsoren-Banner anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'sponsor.banner.title' => [self::TB_DESCRIPTION => 'Titel des Sponsorenbanner', self::TB_TYPE => self::TB_TYPE_STRING],
        'sponsor.banner.show_title' => [self::TB_DESCRIPTION => 'Titel des Sponsoren-Banner anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'sponsor.banner.show_name' => [self::TB_DESCRIPTION => 'Sponsorname im Sponsoren-Banner anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'sponsor.banner.show_text' => [self::TB_DESCRIPTION => 'Detailtext im Sponsoren-Banner anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'sponsor.page.title' => [self::TB_DESCRIPTION => 'Titel der Sponsoren-Seite', self::TB_TYPE => self::TB_TYPE_STRING],
        'sponsor.page.text' => [self::TB_DESCRIPTION => 'Einleitungstext der Sponsoren-Seite', self::TB_TYPE => self::TB_TYPE_HTML],
        'sponsor.page.site_links' => [self::TB_DESCRIPTION => 'Links zu den Kategorien anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'sponsor.page.show_header' => [self::TB_DESCRIPTION => 'Kategorie überschriften anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],
        'sponsor.page.show_empty' => [self::TB_DESCRIPTION => 'Leere Sponsor Kategorien anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],

        'community.enabled' => [self::TB_DESCRIPTION => 'Community Sektion einschalten', self::TB_TYPE => self::TB_TYPE_BOOL],
        'community.all' => [self::TB_DESCRIPTION => 'Alle IDM User in Community anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],

        'lan.signup.enabled' => [self::TB_DESCRIPTION => 'LAN-Anmeldung erlauben', self::TB_TYPE => self::TB_TYPE_BOOL],
        'lan.signup.info' => [self::TB_DESCRIPTION => 'Inhalt der beim "Anmelden" zu einer LAN angezeigt wird', self::TB_TYPE => self::TB_TYPE_HTML],

        'lan.seatmap.enabled' => [self::TB_DESCRIPTION => 'Sitzplanbuchungen einschalten', self::TB_TYPE => self::TB_TYPE_BOOL],
        'lan.seatmap.allow_booking_for_non_paid' => [self::TB_DESCRIPTION => 'Sitzplanbuchungen für nicht bezahlte Gamer erlauben', self::TB_TYPE => self::TB_TYPE_BOOL],
        'lan.seatmap.locked' => [self::TB_DESCRIPTION => 'Sitzplanbuchungen sperren (Kein Reservieren/Freigeben für User)', self::TB_TYPE => self::TB_TYPE_BOOL],
        'lan.seatmap.bg_image' => [self::TB_DESCRIPTION => 'Sitzplan Hintergrundbild', self::TB_TYPE => self::TB_TYPE_FILE],

        'lan.stats.show' => [self::TB_DESCRIPTION => 'Statistiken zur Anmeldung anzeigen', self::TB_TYPE => self::TB_TYPE_BOOL],

        'lan.tourney.enabled' => [self::TB_DESCRIPTION => 'Tourney einschalten', self::TB_TYPE => self::TB_TYPE_BOOL],
        'lan.tourney.text' => [self::TB_DESCRIPTION => 'Tourney Einleitungstext', self::TB_TYPE => self::TB_TYPE_HTML],
        'lan.tourney.registration_open' => [self::TB_DESCRIPTION => 'Registrierung freigeschalten', self::TB_TYPE => self::TB_TYPE_BOOL],

        'style.logo' => [self::TB_DESCRIPTION => 'Logo', self::TB_TYPE => self::TB_TYPE_FILE],
        'style.bg_image' => [self::TB_DESCRIPTION => 'Hintergrundbild', self::TB_TYPE => self::TB_TYPE_FILE],

        'email.register.subject' => [self::TB_DESCRIPTION => 'Betreff der Registrierungsmail', self::TB_TYPE => self::TB_TYPE_STRING],
        'email.register.text' => [self::TB_DESCRIPTION => 'Text der Registrierungsmail', self::TB_TYPE => self::TB_TYPE_HTML],
        'email.reset.subject' => [self::TB_DESCRIPTION => 'Betreff der Passwort-Zurücksetzen Email', self::TB_TYPE => self::TB_TYPE_STRING],
        'email.reset.text' => [self::TB_DESCRIPTION => 'Text der Passwort-Zurücksetzen Email', self::TB_TYPE => self::TB_TYPE_HTML],
        'email.notify.subject' => [self::TB_DESCRIPTION => 'Betreff der Benachrichtigungs-Email', self::TB_TYPE => self::TB_TYPE_STRING],

        'link.fb' => [self::TB_DESCRIPTION => 'Link zur Facebook Seite', self::TB_TYPE => self::TB_TYPE_URL],
        'link.insta' => [self::TB_DESCRIPTION => 'Link zur Instagram Seite', self::TB_TYPE => self::TB_TYPE_URL],
        'link.steam' => [self::TB_DESCRIPTION => 'Link zur Steam Gruppe', self::TB_TYPE => self::TB_TYPE_URL],
        'link.yt' => [self::TB_DESCRIPTION => 'Link zur YouTube Seite', self::TB_TYPE => self::TB_TYPE_URL],
        'link.twitter' => [self::TB_DESCRIPTION => 'Link zur Twitter Seite', self::TB_TYPE => self::TB_TYPE_URL],
        'link.discord' => [self::TB_DESCRIPTION => 'Link zur Discord Server', self::TB_TYPE => self::TB_TYPE_URL],
        'link.teamspeak' => [self::TB_DESCRIPTION => 'Teamspeak Invite Link', self::TB_TYPE => self::TB_TYPE_URL],
        'link.twitch' => [self::TB_DESCRIPTION => 'Link zum Twitchkanal', self::TB_TYPE => self::TB_TYPE_URL],

        // extend here
    ];

    private readonly LoggerInterface $logger;
    private readonly EntityManagerInterface $em;
    private readonly SettingRepository $repo;
    private readonly UploaderHelper $uploaderHelper;

    /** @var array|null local cache to avoid single key database queries and load all settings at once */
    private ?array $cache = null;

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

    public static function validKeys(array $keys): bool
    {
        $keys = array_map(strtolower(...), $keys);
        return array_intersect($keys, array_keys(self::TEXT_BLOCK_KEYS)) == $keys;
    }

    public static function getType(string $key): string
    {
        $key = strtolower($key);
        return self::validKey($key) ? self::TEXT_BLOCK_KEYS[$key][self::TB_TYPE] : '';
    }

    public static function getDescription(string $key): string
    {
        $key = strtolower($key);
        return self::validKey($key) ? self::TEXT_BLOCK_KEYS[$key][self::TB_DESCRIPTION] : '';
    }

    public function isSet(string $key): bool
    {
        $key = strtolower($key);
        return self::validKey($key) && !empty($this->get($key));
    }

    public function getSettingObject(string $key): ?Setting
    {
        $key = strtolower($key);
        if (!static::validKey($key)) {
            $this->logger->error("Invalid key {$key} was requested by SettingService");

            return null;
        }

        return $this->repo->findByKey($key) ?? new Setting($key);
    }

    public function get(string $key, $default = '')
    {
        $key = strtolower($key);
        if (!static::validKey($key)) {
            $this->logger->error("Invalid key {$key} was requested by SettingService");
            return null;
        }

        // load cache if empty
        if (is_null($this->cache)) {
            $this->cache = array();
            foreach ($this->repo->findAll() as $item) {
                $this->cache[$item->getKey()] = $item;
            }
        }

        if (!isset($this->cache[$key])) {
            // valid key, but not yet crated
            return $default;
        }
        if (self::getType($key) == self::TB_TYPE_FILE) {
            return $this->uploaderHelper->asset($this->cache[$key], 'file', Setting::class);
        } else {
            return $this->cache[$key]->getText() ?? '';
        }
    }

    public function set(string $key, string $value): bool
    {
        $key = strtolower($key);
        if (!array_key_exists($key, self::TEXT_BLOCK_KEYS)) {
            $this->logger->error("Invalid key {$key} was to be set at SettingService");
            return false;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            // create it
            $tb = new Setting($key);
            $tb->setText($value);
            $this->em->persist($tb);
        } else {
            // update it if necessary
            if ($block->getText() !== $value) {
                $block->setText($value);
                $this->em->persist($block);
            }
        }
        $this->em->flush();
        $this->cache = null;
        return true;
    }

    public function clear(string $key): bool
    {
        $key = strtolower($key);
        if (!static::validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be deleted by SettingService");
            return false;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            return false;
        }
        $this->em->remove($block);
        $this->em->flush();
        $this->cache = null;

        return true;
    }

    /** @var string[] $keys */
    public function clearMultiple(array $keys): bool
    {
        $keys = array_map(strtolower(...), $keys);

        $result = false;
        foreach ($keys as $key) {
            if (!self::validKey($key)) {
                $this->logger->error("Invalid key {$key} was to be deleted by SettingService");
                continue;
            }
            $block = $this->repo->findByKey($key);
            if (empty($block)) {
                continue;
            }
            $result = true;
            $this->em->remove($block);
        }
        $this->em->flush();
        $this->cache = null;

        return $result;
    }

    public function clearStartWith(string $pattern): bool
    {
        $pattern = strtolower($pattern);
        $keys = array_filter(self::getKeys(), fn ($k) => str_starts_with($k, $pattern));
        return $this->clearMultiple($keys);
    }

    public function lastModification(string $key): ?DateTimeInterface
    {
        $key = strtolower($key);
        if (!static::validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be queried by SettingService");
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

    public function setSettingsObject(Setting $data): void
    {
        $key = $data->getKey();
        if (!static::validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be set by SettingService");
            return;
        }
        $data->setKey(strtolower($key));
        $this->em->persist($data);
        $this->em->flush();
        $this->cache = null;
    }

    public function wipe(WipeMode $mode): void
    {
        $keys = [
            'site.title', 'site.title.show', 'site.subtitle', 'site.subtitle.show', 'site.about', 'site.organisation',
            'link.fb', 'link.insta', 'link.steam', 'link.yt', 'link.twitter', 'link.discord', 'link.teamspeak', 'link.twitch',
            'community.enabled', 'community.all', 'lan.signup.enabled', 'lan.signup.info', 'lan.stats.show',
            'style.logo', 'style.bg_image'
        ];
        $this->clearMultiple($keys);
    }

    public function wipeBefore(WipeMode $mode): array
    {
        return [];
    }
}
