<?php

namespace App\Form\users\freelancer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class StudentCardFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('studentCardFile', FileType::class, [
            'label'    => 'Upload Student Card',
            'mapped'   => false,
            'required' => true,
            'constraints' => [
                new NotNull(message: 'Please upload your student card.'),
                new File([
                    'maxSize'   => '5M',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'application/pdf'],
                    'mimeTypesMessage' => 'Accepted formats: JPG, PNG, PDF.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}