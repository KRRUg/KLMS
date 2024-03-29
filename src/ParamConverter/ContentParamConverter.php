<?php

namespace App\ParamConverter;

use App\Entity\Content;
use App\Repository\ContentRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentParamConverter implements ParamConverterInterface
{
    private readonly ContentRepository $contentRepository;

    public function __construct(ContentRepository $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $class = $configuration->getClass();
        $id = $request->attributes->get('id');

        $content = $this->contentRepository->createQueryBuilder('u')
            ->andWhere('u.id = :id OR u.alias = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$content) {
            return false;
        }

        $param = $configuration->getName();
        $request->attributes->set($param, $content);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration): bool
    {
        return Content::class === $configuration->getClass();
    }
}
