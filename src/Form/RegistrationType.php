<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Ime',
                'constraints' => [new NotBlank(message: 'Unesite ime.')],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Prezime',
                'constraints' => [new NotBlank(message: 'Unesite prezime.')],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email adresa',
                'constraints' => [
                    new NotBlank(message: 'Unesite email adresu.'),
                    new Email(message: 'Unesite ispravnu email adresu.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Šifra'],
                'second_options' => ['label' => 'Ponovite šifru'],
                'invalid_message' => 'Šifre se ne podudaraju.',
                'constraints' => [
                    new NotBlank(message: 'Unesite šifru.'),
                    new Length(min: 8, minMessage: 'Šifra mora imati najmanje {{ limit }} znakova.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
