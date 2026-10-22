<?php

namespace App\Form;

use App\Entity\Driver;
use App\Entity\FleetDocument;
use App\Entity\Vehicle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class FleetDocumentType extends AbstractType
{
    private const VEHICLE_TYPE_CHOICES = [
        'Registracija' => 'registration',
        'Periodički tehnički 3 mjeseca' => 'periodic_inspection_3_months',
        'Periodični tehnički 4 mjeseca' => 'periodic_inspection_4_months',
        'Periodični tehnički 6 mjeseci' => 'periodic_inspection_6_months',
        'Licenca' => 'licence',
        'Atest' => 'attest',
        'Protupožarni aparat' => 'fire_extinguisher',
        'Prva pomoć' => 'first_aid',
        'Servis' => 'service',
        'Ostalo' => 'other',
    ];

    private const DRIVER_TYPE_CHOICES = [
        'Vozačka dozvola' => 'driving_licence',
        'Kartica vozača - taho kartica' => 'driver_card',
        'Ljekarsko uvjerenje' => 'medical_certificate',
        'Licenca' => 'licence',
        'Ugovor' => 'contract',
        'Ostalo' => 'other',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var FleetDocument|null $document */
        $document = $options['data'] ?? null;
        $ownerType = $document instanceof FleetDocument ? $document->getOwnerType() : FleetDocument::OWNER_VEHICLE;
        $typeChoices = $options['lock_owner']
            ? ($ownerType === FleetDocument::OWNER_DRIVER ? self::DRIVER_TYPE_CHOICES : self::VEHICLE_TYPE_CHOICES)
            : array_merge(self::VEHICLE_TYPE_CHOICES, self::DRIVER_TYPE_CHOICES);

        if ($options['lock_owner'] && $ownerType === FleetDocument::OWNER_VEHICLE && $document?->getType() === 'insurance') {
            $typeChoices['Osiguranje'] = 'insurance';
        }

        $builder
            ->add('ownerType', ChoiceType::class, [
                'label' => 'Dokument se odnosi na',
                'mapped' => false,
                'disabled' => $options['lock_owner'],
                'choices' => [
                    'Vozilo' => FleetDocument::OWNER_VEHICLE,
                    'Vozača' => FleetDocument::OWNER_DRIVER,
                ],
                'data' => $ownerType,
            ])
            ->add('vehicle', EntityType::class, [
                'label' => 'Vozilo',
                'class' => Vehicle::class,
                'choices' => $options['vehicles'],
                'choice_label' => static fn (Vehicle $vehicle): string => trim($vehicle->getRegistrationNumber().' '.$vehicle->getManufacturer().' '.$vehicle->getModel()),
                'placeholder' => 'Odaberite vozilo',
                'required' => false,
                'disabled' => $options['lock_owner'],
            ])
            ->add('driver', EntityType::class, [
                'label' => 'Vozač',
                'class' => Driver::class,
                'choices' => $options['drivers'],
                'choice_label' => static fn (Driver $driver): string => $driver->getFullName(),
                'placeholder' => 'Odaberite vozača',
                'required' => false,
                'disabled' => $options['lock_owner'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Tip dokumenta',
                'choices' => $typeChoices,
            ])
            ->add('documentNumber', TextType::class, [
                'label' => 'Broj dokumenta',
                'required' => false,
            ])
            ->add('validFrom', DateType::class, [
                'label' => 'Važi od',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('validUntil', DateType::class, [
                'label' => 'Datum isteka',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('file', FileType::class, [
                'label' => 'Prilog',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '8M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Dozvoljeni su PDF, JPG, PNG i WEBP fajlovi.',
                    ]),
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
            'data_class' => FleetDocument::class,
            'vehicles' => [],
            'drivers' => [],
            'lock_owner' => false,
        ]);

        $resolver->setAllowedTypes('lock_owner', 'bool');
    }
}
