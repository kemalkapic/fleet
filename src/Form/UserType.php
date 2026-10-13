<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = (bool) $options['is_edit'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email adresa',
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Ime',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Prezime',
            ])
            ->add('company', EntityType::class, [
                'label' => 'Firma',
                'class' => Company::class,
                'choice_label' => 'name',
                'placeholder' => 'Platformski korisnik bez firme',
                'required' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Role',
                'property_path' => 'assignedRoles',
                'choices' => [
                    'Super admin' => 'ROLE_SUPER_ADMIN',
                    'Administrator firme' => 'ROLE_COMPANY_ADMIN',
                    'Vozač' => 'ROLE_DRIVER',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Nova šifra' : 'Šifra',
                'mapped' => false,
                'required' => !$isEdit,
                'help' => $isEdit ? 'Ostavite prazno ako ne mijenjate šifru.' : null,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
