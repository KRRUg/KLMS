<?php

namespace App\Form;

use App\Entity\Media;
use App\Helper\AuthorInsertSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class MediaType extends AbstractType
{
    private $userInsertSubscriber;

    public function __construct(AuthorInsertSubscriber $userInsertSubscriber)
    {
        $this->userInsertSubscriber = $userInsertSubscriber;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('mediaFile', VichFileType::class, [
            'label' => false,
            'required' => true,
            'allow_delete' => false,
            'download_uri' => false,
            'asset_helper' => true,
        ]);
        $builder->addEventSubscriber($this->userInsertSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
        ]);
    }
}
