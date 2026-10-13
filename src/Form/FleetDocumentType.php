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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var FleetDocument|null $document */
        $document = $options['data'] ?? null;

        $builder
            ->add('ownerType', ChoiceType::class, [
                'label' => 'Dokument se odnosi na',
                'mapped' => false,
                'choices' => [
                    'Vozilo' => FleetDocument::OWNER_VEHICLE,
                    'Vozača' => FleetDocument::OWNER_DRIVER,
                ],
                'data' => $document instanceof FleetDocument ? $document->getOwnerType() : FleetDocument::OWNER_VEHICLE,
            ])
            ->add('vehicle', EntityType::class, [
                'label' => 'Vozilo',
                'class' => Vehicle::class,
                'choices' => $options['vehicles'],
                'choice_label' => static fn (Vehicle $vehicle): string => trim($vehicle->getRegistrationNumber().' '.$vehicle->getManufacturer().' '.$vehicle->getModel()),
                'placeholder' => 'Odaberite vozilo',
                'required' => false,
            ])
            ->add('driver', EntityType::class, [
                'label' => 'Vozač',
                'class' => Driver::class,
                'choices' => $options['drivers'],
                'choice_label' => static fn (Driver $driver): string => $driver->getFullName(),
                'placeholder' => 'Odaberite vozača',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Tip dokumenta',
                'choices' => [
                    'Tehnički pregled' => 'registration',
                    'Osiguranje' => 'insurance',
                    'Vozačka dozvola' => 'driving_licence',
                    'Ljekarsko uvjerenje' => 'medical_certificate',
                    'Licenca' => 'licence',
                    'Protupožarni aparat' => 'fire_extinguisher',
                    'Prva pomoć' => 'first_aid',
                    'Servis' => 'service',
                    'Ugovor' => 'contract',
                    'Ostalo' => 'other',
                ],
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
        ]);
    }
}
