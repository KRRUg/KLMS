<?php

namespace App\Form;

use App\Entity\ShopAddon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ShopAddonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('price', MoneyType::class, ['label' => 'Preis', 'divisor' => 100])
            ->add('active', CheckboxType::class, ['label' => 'Aktiv', 'required' => false])
            ->add('onlyOnce', CheckboxType::class, ['label' => 'Kann nur einmal pro User gekauft werden.', 'required' => false])
            ->add('maxQuantityGlobal', IntegerType::class, ['label' => 'Maximale Anzahl (global)', 'required' => false, 'attr' => ['min' => 1], 'constraints' => [new Assert\Positive()]])
            ->add('sortIndex', IntegerType::class, ['label' => 'Sortierung', 'required' => false, 'attr' => ['min' => 1], 'constraints' => [new Assert\Positive()]])
            ->add('description', TextAreaType::class, ['label' => 'Beschreibung', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShopAddon::class,
        ]);
    }
}
