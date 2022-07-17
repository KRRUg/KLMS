<?php

namespace App\Form;

use App\Entity\Sponsor;
use App\Helper\AuthorInsertSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class SponsorType extends AbstractType
{
    private AuthorInsertSubscriber $userInsertSubscriber;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('url')
            ->add('text')
        ;
        $builder->add('logoFile', VichImageType::class, [
            'required' => !$options['edit'],
            'allow_delete' => false,
            'download_uri' => false,
            'image_uri' => false,
            'asset_helper' => false,
            'imagine_pattern' => 'sponsor_logo',
            'label' => 'Logo',
        ]);
        $builder->addEventSubscriber($this->userInsertSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
            'edit' => false,
        ]);
        $resolver->setAllowedTypes('edit', 'bool');
    }
}
