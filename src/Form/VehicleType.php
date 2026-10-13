<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('registrationNumber', TextType::class, [
                'label' => 'Registracija',
            ])
            ->add('vin', TextType::class, [
                'label' => 'VIN',
                'required' => false,
            ])
            ->add('manufacturer', TextType::class, [
                'label' => 'Proizvođač',
                'required' => false,
            ])
            ->add('model', TextType::class, [
                'label' => 'Model',
                'required' => false,
            ])
            ->add('vehicleType', TextType::class, [
                'label' => 'Tip vozila',
                'required' => false,
            ])
            ->add('productionYear', IntegerType::class, [
                'label' => 'Godina proizvodnje',
                'required' => false,
            ])
            ->add('registrationValidUntil', DateType::class, [
                'label' => 'Registracija važi do',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('engineDisplacement', IntegerType::class, [
                'label' => 'Radni obujam',
                'required' => false,
            ])
            ->add('enginePowerKw', IntegerType::class, [
                'label' => 'Snaga kW',
                'required' => false,
            ])
            ->add('fuelType', TextType::class, [
                'label' => 'Gorivo',
                'required' => false,
            ])
            ->add('euroStandard', TextType::class, [
                'label' => 'EURO norma',
                'required' => false,
            ])
            ->add('currentMileage', IntegerType::class, [
                'label' => 'Trenutna kilometraža',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Aktivno' => 'active',
                    'Neaktivno' => 'inactive',
                    'Prodano' => 'sold',
                    'Arhivirano' => 'archived',
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
            'data_class' => Vehicle::class,
        ]);
    }
}
