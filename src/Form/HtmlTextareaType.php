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

    public const RELATIVE_URLS = 'fix_relative_urls';
    public const CLEAR_SCRIPTS = 'clear_scripts';

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver
            ->setDefault(self::RELATIVE_URLS, false)
            ->setDefault(self::CLEAR_SCRIPTS, true)
            ->setAllowedTypes(self::RELATIVE_URLS, 'bool')
            ->setAllowedTypes(self::CLEAR_SCRIPTS, 'bool')
        ;
    }
}