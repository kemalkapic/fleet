<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Naziv firme',
            ])
            ->add('logo', FileType::class, [
                'label' => 'Logo firme',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Odaberite PNG, JPG ili WebP sliku.',
                    ]),
                ],
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

        if ($options['data'] instanceof Company && $options['data']->getLogoPath() !== null) {
            $builder->add('removeLogo', CheckboxType::class, [
                'label' => 'Ukloni trenutni logo',
                'mapped' => false,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
