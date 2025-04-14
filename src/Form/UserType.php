<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'disabled' => true,
            ])
            ->add('nickname')
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
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Geschlecht',
                'required' => false,
                'choices' => [
                    'Weiblich' => 'f',
                    'Männlich' => 'm',
                    'Divers' => 'x',
                ],
            ])
            ->add('infoMails', CheckboxType::class, [
                'label' => 'Newsletter abonnieren',
                'required' => false,
            ])
            ->add('postcode', TextType::class, [
                'required' => false,
                'label' => 'PLZ',
            ])
            ->add('city', TextType::class, [
                'label' => 'Ort',
                'required' => false,
            ])
            ->add('street', TextType::class, [
                'label' => 'Straße',
                'required' => false,
            ])
            ->add('country', CountryType::class, [
                'label' => 'Land',
                'required' => false,
                'choice_translation_locale' => 'de',
                'preferred_choices' => ['AT', 'DE', 'CH'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefon',
                'required' => false,
            ])
            ->add('website', UrlType::class, [
                'required' => false,
            ])
            ->add('steamAccount', TextType::class, [
                'label' => 'Steam Account',
                'required' => false,
            ])
            ->add('battlenetAccount', TextType::class, [
                'label' => 'Battle.net Account',
                'required' => false,
            ])
            ->add('hardware', TextareaType::class, [
                'required' => false,
            ])
            ->add('statements', TextType::class, [
                'label' => 'Statement',
                'required' => false,
            ])
        ;

        if ($options['with_image']) {
            $builder->add('image', UserImageType::class, [
                'mapped' => false,
            ]);
        }

        if ($options['disable_on_lock']) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->onPreSetData(...));
        } else {
            $builder
                ->add('personalDataConfirmed', CheckboxType::class, [
                    'required' => false,
                    'label' => 'Daten überprüft',
                    ])
            ;
            $builder->addEventListener(FormEvents::POST_SUBMIT, $this->onPostSubmit(...));
        }
    }

    private const CONFIRMABLE_FIELDS = ['nickname', 'firstname', 'surname', 'birthdate', 'gender'];

    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!$data) {
            return;
        }

        $confirmed = $data->getPersonalDataConfirmed() ?? false;
        $locked = $data->getPersonalDataLocked() ?? false;

        if ($confirmed || $locked) {
            foreach (self::CONFIRMABLE_FIELDS as $field) {
                $item = $form->get($field);
                $options = $item->getConfig()->getOptions();
                $type = $item->getConfig()->getType()->getInnerType()::class;
                $options['disabled'] = true;
                $form->add($field, $type, $options);
            }
        }
    }

    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $confirmed = $form->get('personalDataConfirmed');
        if ($confirmed->getData()) {
            $missing = false;
            foreach (self::CONFIRMABLE_FIELDS as $field) {
                $item = $form->get($field);
                if ($item->isEmpty()) {
                    $item->addError(new FormError('Nicht vollständig ausgefüllt.'));
                    $missing = true;
                }
            }
            if ($missing) {
                $confirmed->addError(new FormError('Nicht alles ausgefüllt.'));
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'allow_extra_fields' => true,
            'disable_on_lock' => true,
            'with_image' => false,
        ]);

        $resolver
            ->setAllowedTypes('disable_on_lock', 'bool')
            ->setAllowedTypes('with_image', 'bool')
        ;
    }
}
