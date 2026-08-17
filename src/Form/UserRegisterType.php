<?php

namespace App\Form;

use App\Entity\User;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-Mail Adresse',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Kennwort'],
                'second_options' => ['label' => 'Kennwort bestätigen'],
                'invalid_message' => 'Die Kennwörter stimmen nicht überein.',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Vorname',
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nachname',
            ])
            ->add('birthdate', BirthdayType::class, [
                'label' => 'Geburtsdatum',
                'widget' => 'single_text',
                'attr' => ['class' => 'datepicker'],
                'required' => true,
            ])
            ->add('nickname', TextType::class, [
                'label' => 'Nickname',
            ])
            // TODO add link to privacy information site (once content alias is done)
            ->add('infoMails', CheckboxType::class, [
                'label' => 'Ich möchte Informationen zum Event per Mail erhalten.',
                'required' => false,
            ]);
        $recaptchaSiteKey = !empty($_ENV['RECAPTCHA3_KEY']) && $_ENV['RECAPTCHA3_KEY'];
        $recaptchaSecret = !empty($_ENV['RECAPTCHA3_SECRET']) && $_ENV['RECAPTCHA3_SECRET'];
        $recaptchaEnabled = !isset($_ENV['RECAPTCHA3_ENABLED']) || $_ENV['RECAPTCHA3_ENABLED'] !== '0';

        if ($options['captcha'] && $recaptchaEnabled && $recaptchaSiteKey && $recaptchaSecret) {
            $builder->add('recaptcha', Recaptcha3Type::class, [
                'label' => false,
                'action_name' => 'register',
                'locale' => 'de',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Recaptcha3(),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'captcha' => true,
        ]);

        $resolver
            ->setAllowedTypes('captcha', 'bool')
        ;
    }
}
