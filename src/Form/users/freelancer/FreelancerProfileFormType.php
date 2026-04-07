<?php

namespace App\Form\users\freelancer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class FreelancerProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pricePerHour', NumberType::class, [
                'label' => 'Hourly Rate (TND/hour)',
                'scale' => 2,
                'attr'  => ['placeholder' => '0.00'],
            ])
            ->add('skills', TextType::class, [
                'label'      => 'Skills',
                'required'   => true,
                'empty_data' => '',
                'attr'       => [
                    'placeholder' => 'e.g. PHP, JavaScript, Python (comma-separated)',
                    'id'          => 'skillsInput',
                ],
                'constraints' => [new NotBlank(message: 'Please add at least one skill.')],
            ])
            ->add('bio', TextareaType::class, [
                'label'      => 'Professional Bio',
                'empty_data' => '',
                'attr'       => [
                    'placeholder' => 'Tell clients about yourself...',
                    'rows'        => 6,
                ],
            ])

            ->add('cvFile', FileType::class, [
                'label'    => 'Upload Your CV (PDF, max 5MB)',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Please upload a PDF file.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\users\freelancer\Freelancer::class,
        ]);
    }
}
