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
use Symfony\Component\Validator\Constraints\Regex;
use App\Form\Type\RoleCardType;


/** @extends AbstractType<User> */
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
                'type'           => PasswordType::class,
                'mapped'         => false,
                'first_options'  => [
                    'label' => 'Password',
                    'attr'  => ['placeholder' => 'Min 8 characters'],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr'  => ['placeholder' => 'Re-enter your password'],
                ],
                'invalid_message' => 'Passwords do not match.',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password.']),
                    new Length([
                        'min'        => 8,
                        'minMessage' => 'Password must be at least 8 characters.',
                        'max'        => 255,
                    ]),
                    new Regex([
                        'pattern' => '/[A-Z]/',
                        'message' => 'Password must contain at least one uppercase letter.',
                    ]),
                    new Regex([
                        'pattern' => '/[0-9]/',
                        'message' => 'Password must contain at least one number.',
                    ]),
                ],
            ])
            ->add('role', RoleCardType::class, [
                'label'   => 'I want to',
                'choices' => [
                    'Client'     => 'CLIENT',
                    'Freelancer' => 'FREELANCER',
                    'Admin'      => 'ADMIN',
                ],
            ])
            ->add('profilePictureFile', FileType::class, [
                'label'    => 'Profile Photo',
                'mapped'   => false,
                'required' => false,
                'constraints' => [
                    new File(['maxSize' => '2M']),
                ],
            ])
            ->add('agreeTerms', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'mapped'      => false,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\IsTrue([
                        'message' => 'You must agree to our terms and conditions.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}