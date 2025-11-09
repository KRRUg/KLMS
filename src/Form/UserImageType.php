<?php

namespace App\Form;

use App\Entity\UserImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class UserImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('imageFile', VichImageType::class, [
            'required' => false,
            'allow_delete' => true,
            'download_uri' => false,
            'image_uri' => false,
            'asset_helper' => true,
            'label' => 'Userbild',
            'delete_label' => 'Bild löschen',
            'attr' => [
                'accept' => 'image/*',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserImage::class,
        ]);
    }
}
