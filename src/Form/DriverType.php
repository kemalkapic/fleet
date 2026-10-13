<?php

namespace App\Form;

use App\Entity\Driver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DriverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Ime',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Prezime',
            ])
            ->add('phone', TextType::class, [
                'label' => 'Telefon',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email adresa',
                'required' => false,
            ])
            ->add('licenceNumber', TextType::class, [
                'label' => 'Broj vozačke dozvole',
                'required' => false,
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Datum rođenja',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('birthPlace', TextType::class, [
                'label' => 'Mjesto rođenja',
                'required' => false,
            ])
            ->add('licenceIssuedAt', DateType::class, [
                'label' => 'Datum izdavanja',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('licenceExpiresAt', DateType::class, [
                'label' => 'Datum isteka',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('licenceIssuedBy', TextType::class, [
                'label' => 'Izdano od',
                'required' => false,
            ])
            ->add('residenceMunicipality', TextType::class, [
                'label' => 'Općina prebivališta',
                'required' => false,
            ])
            ->add('licenceCategories', TextType::class, [
                'label' => 'Kategorije',
                'required' => false,
                'getter' => static fn (Driver $driver): string => implode(', ', $driver->getLicenceCategories()),
                'setter' => static function (Driver $driver, ?string $categories): void {
                    $parts = $categories !== null ? preg_split('/[\s,;\/]+/', $categories, -1, PREG_SPLIT_NO_EMPTY) : [];
                    $driver->setLicenceCategories($parts !== false ? $parts : []);
                },
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Aktivan' => 'active',
                    'Neaktivan' => 'inactive',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Napomene',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Driver::class,
        ]);
    }
}
