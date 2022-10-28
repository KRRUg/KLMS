<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HtmlTextareaType extends TextareaType
{
    private HtmlHandlingSubscriber $htmlHandlingSubscriber;

    public function __construct(HtmlHandlingSubscriber $htmlHandlingSubscriber)
    {
        $this->htmlHandlingSubscriber = $htmlHandlingSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addEventSubscriber($this->htmlHandlingSubscriber);
    }

    public const FIX_URLS = 'fix_urls';
    public const CLEAR_SCRIPTS = 'clear_scripts';
    public const FIX_HEADLINES = 'fix_headlines';

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver
            ->setDefault(self::FIX_URLS, false)
            ->setAllowedTypes(self::FIX_URLS, ['bool', 'string'])
            ->setAllowedValues(self::FIX_URLS, [false, 'relative', 'absolute'])
        ;
        $resolver
            ->setDefault(self::CLEAR_SCRIPTS, true)
            ->setAllowedTypes(self::CLEAR_SCRIPTS, 'bool')
        ;
        $resolver
            ->setDefault(self::FIX_HEADLINES, true)
            ->setAllowedTypes(self::FIX_HEADLINES, 'bool')
        ;
        $resolver->setDefault('attr', ['class' => 'wysiwyg']);
    }
}