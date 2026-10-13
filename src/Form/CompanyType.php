<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Naziv firme',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Aktivna' => 'active',
                    'Probni period' => 'trial',
                    'Suspendirana' => 'suspended',
                    'Neaktivna' => 'inactive',
                ],
            ])
            ->add('vehicleLimit', IntegerType::class, [
                'label' => 'Limit broja vozila',
                'required' => false,
                'help' => 'Ostavite prazno za neograničeno.',
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('driverLimit', IntegerType::class, [
                'label' => 'Limit broja vozača',
                'required' => false,
                'help' => 'Ostavite prazno za neograničeno.',
                'attr' => [
                    'min' => 0,
                ],
            ])
            ->add('documentLimit', IntegerType::class, [
                'label' => 'Limit broja dokumenata',
                'required' => false,
                'help' => 'Ostavite prazno za neograničeno.',
                'attr' => [
                    'min' => 0,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
