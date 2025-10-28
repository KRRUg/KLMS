<?php

namespace App\Service;

use App\Entity\Faq;
use App\Repository\FaqRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class FaqService
{
    private readonly FaqRepository $faqRepository;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;

    public function __construct(
        FaqRepository $faqRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->faqRepository = $faqRepository;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Get all FAQs for admin interface
     *
     * @return Faq[]
     */
    public function getAllFaqs(): array
    {
        return $this->faqRepository->findAllOrderedByPriority();
    }

    /**
     * Get active FAQs for frontend
     *
     * @return Faq[]
     */
    public function getActiveFaqs(): array
    {
        return $this->faqRepository->findActiveOrderedByPriority();
    }

    /**
     * Search FAQs by term
     *
     * @param string $searchTerm
     * @return Faq[]
     */
    public function searchFaqs(string $searchTerm): array
    {
        if (empty(trim($searchTerm))) {
            return $this->getActiveFaqs();
        }

        return $this->faqRepository->searchByQuestion($searchTerm);
    }

    /**
     * Create a new FAQ
     */
    public function createFaq(): Faq
    {
        return new Faq();
    }

    /**
     * Save FAQ
     */
    public function saveFaq(Faq $faq): void
    {
        try {
            $this->em->persist($faq);
            $this->em->flush();
            
            $this->logger->info("FAQ saved successfully", [
                'faq_id' => $faq->getId(),
                'question' => substr($faq->getQuestion(), 0, 50)
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to save FAQ", [
                'error' => $e->getMessage(),
                'question' => substr($faq->getQuestion(), 0, 50)
            ]);
            throw $e;
        }
    }

    /**
     * Delete FAQ
     */
    public function deleteFaq(Faq $faq): void
    {
        try {
            $this->em->remove($faq);
            $this->em->flush();
            
            $this->logger->info("FAQ deleted successfully", [
                'faq_id' => $faq->getId(),
                'question' => substr($faq->getQuestion(), 0, 50)
            ]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete FAQ", [
                'error' => $e->getMessage(),
                'faq_id' => $faq->getId()
            ]);
            throw $e;
        }
    }

    /**
     * Toggle FAQ active status
     */
    public function toggleActive(Faq $faq): void
    {
        $faq->setActive(!$faq->isActive());
        $this->saveFaq($faq);
    }
}