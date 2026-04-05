<?php

namespace App\Form\users\user;

use App\Entity\users\user\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr'  => ['placeholder' => 'Enter your full name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr'  => ['placeholder' => 'Enter your email'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'first_options'   => ['label' => 'Password',         'attr' => ['placeholder' => 'Create a password']],
                'second_options'  => ['label' => 'Confirm Password', 'attr' => ['placeholder' => 'Re-enter your password']],
                'constraints'     => [
                    new NotBlank(['message' => 'Please enter a password.']),
                    new Length(['min' => 6, 'minMessage' => 'Password must be at least {{ limit }} characters.']),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label'   => 'I am a',
                'choices' => [
                    'Client'     => 'CLIENT',
                    'Freelancer' => 'FREELANCER',
                    'Admin'      => 'ADMIN',
                ],
                'placeholder' => 'Select your role',
            ])
            ->add('profilePictureFile', FileType::class, [
                'label'    => 'Profile Photo',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File(['maxSize' => '2M']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}