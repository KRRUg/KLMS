<?php

namespace App\Form;

use App\Entity\User;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'E-Mail Adresse',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => ['label' => 'Kennwort'],
                'second_options' => ['label' => 'Kennwort bestätigen'],
                'invalid_message' => 'Die Kennwörter stimmen nicht überein.',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Vorname'
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nachname'
            ])
            ->add('nickname', TextType::class, [
                'label' => 'Nickname',
            ])
            // TODO add link to privacy information site (once content alias is done)
            ->add('infoMails', CheckboxType::class, [
                'label' => 'Newsletter abonnieren',
                'required' => false,
            ]);
            $recaptchaSiteKey = !empty($_ENV['EWZ_RECAPTCHA_SITE_KEY']) && $_ENV['EWZ_RECAPTCHA_SITE_KEY'];
            $recaptchaSecret = !empty($_ENV['EWZ_RECAPTCHA_SECRET']) && $_ENV['EWZ_RECAPTCHA_SECRET'];
            if($recaptchaSiteKey && $recaptchaSecret) {
                $builder->add('recaptcha', EWZRecaptchaType::class, array(
                    'label' => false,
                    'attr' => array(
                        'options' => array(
                            'theme' => 'light',
                            'type' => 'image',
                            'size' => 'normal',
                            'defer' => true,
                            'async' => true,
                        )
                    ),
                    'mapped' => false,
                    'required' => true,
                    'constraints' => array(
                        new RecaptchaTrue()
                    )
                ));
            }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
