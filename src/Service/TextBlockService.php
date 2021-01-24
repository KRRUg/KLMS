<?php


namespace App\Service;

use App\Entity\TextBlock;
use App\Repository\TextBlockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TextBlockService
{
    ////////////////////////////////////////////////
    /// Text block names
    ///////////////////////////////////////////////
    const TEXT_BLOCK_KEYS = [
        "AGB" => "AGB",
        "ABOUT_US" => "Über uns, homepage links unten",
        // extend here
    ];

    private $repo;
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, TextBlockRepository $repo, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->logger = $logger;
    }

    public function getKeys(): array
    {
        return array_keys(self::TEXT_BLOCK_KEYS);
    }

    public function validKey(string $key): bool
    {
        $key = strtoupper($key);
        return array_key_exists($key, self::TEXT_BLOCK_KEYS);
    }

    public function get(string $key): ?string
    {
        $key = strtoupper($key);
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was requested by TextBlockService");
            return null;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            // valid key, but not yet crated
            return "";
        }
        return $block->getText();
    }

    public function set(string $key, string $value)
    {
        $key = strtoupper($key);
        if (!array_key_exists($key, self::TEXT_BLOCK_KEYS)) {
            $this->logger->error("Invalid key {$key} was to be set at TextBlockService");
            return;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            // create it
            $tb = new TextBlock($key);
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
        $key = strtoupper($key);
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be deleted by TextBlockService");
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
        $key = strtoupper($key);
        if (!$this->validKey($key)) {
            $this->logger->error("Invalid key {$key} was to be deleted by TextBlockService");
            return null;
        }
        $block = $this->repo->findByKey($key);
        if (empty($block)) {
            return null;
        }
        return $block->getLastModified();
    }

    public function getDescriptions(): array
    {
        return self::TEXT_BLOCK_KEYS;
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
}