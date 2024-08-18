<?php

namespace App\Form;

use App\Entity\Sponsor;
use App\Entity\SponsorCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class SponsorType extends AbstractType
{
    private readonly AuthorInsertSubscriber $userInsertSubscriber;
    private readonly EntityManagerInterface $em;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber, EntityManagerInterface $em)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('isVisible', null, ['label' => 'Sponsor anzeigen'])
            ->add('url', null, ['label' => 'URL'])
            ->add('text', HtmlTextareaType::class, [
                'label' => 'Text',
                'fix_urls' => 'relative',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Kategorie',
                'choices' => $this->em->getRepository(SponsorCategory::class)->findAll(),
                'choice_label' => fn (?SponsorCategory $content) => $content ? $content->getName() : '',
                'multiple' => false,
                'expanded' => false,
            ])
        ;
        $builder->add('logoFile', VichImageType::class, [
            'label' => 'Logo',
            'required' => !$options['edit'],
            'allow_delete' => false,
            'download_uri' => false,
            'image_uri' => false,
            'asset_helper' => false,
            'imagine_pattern' => 'sponsor_logo',
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
