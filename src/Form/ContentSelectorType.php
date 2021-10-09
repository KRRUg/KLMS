<?php

namespace App\Form;

use App\Repository\ContentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentSelectorType extends AbstractType
{
    private ContentRepository $contentRepository;

    public function __construct(ContentRepository $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [];
        foreach ($this->contentRepository->findAll() as $content) {
            $choices[$content->getTitle()] = $content->getId();
        }

        $resolver->setDefaults([
            'label' => 'Content',
            'choices' => $choices,
            'multiple' => false,
            'expanded' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
