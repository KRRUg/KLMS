<?php

namespace App\Form;

use App\Entity\PollOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PollOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Antwort',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'Bitte gib eine Antwort ein.'),
                    new Length(max: 255, maxMessage: 'Die Antwort darf maximal {{ limit }} Zeichen enthalten.'),
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new GreaterThanOrEqual(value: 0, message: 'Die Position muss größer oder gleich 0 sein.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PollOption::class,
        ]);
    }
}
