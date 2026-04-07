<?php

namespace App\Form\users\client;

use App\Entity\users\client\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ClientEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ── User fields ──
            ->add('name', TextType::class, [
                'label'    => 'Full Name',
                'mapped'   => false,
                'attr'     => ['placeholder' => 'Enter your full name'],
                'constraints' => [
                    new NotBlank(message: 'Name is required.'),
                    new Length(min: 2, max: 50,
                        minMessage: 'Name must be at least 2 characters.',
                        maxMessage: 'Name cannot exceed 50 characters.'
                    ),
                    new Regex(
                        pattern: '/^[\p{L}\s\-]+$/u',
                        message: 'Name can only contain letters, spaces, and hyphens.'
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label'    => 'Email Address',
                'mapped'   => false,
                'attr'     => ['placeholder' => 'Enter your email'],
                'constraints' => [
                    new NotBlank(message: 'Email is required.'),
                    new Email(message: 'Please enter a valid email address.'),
                    new Length(max: 50, maxMessage: 'Email cannot exceed 50 characters.'),
                ],
            ])

            // ── Client fields ──
            ->add('company', TextType::class, [
                'label' => 'Company / Organization',
                'attr'  => ['placeholder' => 'Enter your company name'],
                'constraints' => [
                    new NotBlank(message: 'Company name is required.'),
                    new Length(min: 2, max: 100,
                        minMessage: 'Company name must be at least 2 characters.',
                        maxMessage: 'Company name cannot exceed 100 characters.'
                    ),
                ],
            ])
            ->add('industry', ChoiceType::class, [
                'label'       => 'Industry / Category',
                'placeholder' => 'Select your industry',
                'constraints' => [
                    new NotBlank(message: 'Please select an industry.'),
                ],
                'choices' => [
                    'Technology & IT'         => 'Technology & IT',
                    'Marketing & Advertising' => 'Marketing & Advertising',
                    'Design & Creative'       => 'Design & Creative',
                    'Writing & Content'       => 'Writing & Content',
                    'Business & Consulting'   => 'Business & Consulting',
                    'Education & Training'    => 'Education & Training',
                    'Healthcare'              => 'Healthcare',
                    'Finance & Accounting'    => 'Finance & Accounting',
                    'Legal Services'          => 'Legal Services',
                    'Real Estate'             => 'Real Estate',
                    'E-commerce & Retail'     => 'E-commerce & Retail',
                    'Manufacturing'           => 'Manufacturing',
                    'Hospitality & Tourism'   => 'Hospitality & Tourism',
                    'Construction'            => 'Construction',
                    'Other'                   => 'Other',
                ],
            ])

            // ── Optional password change ──
            ->add('currentPassword', PasswordType::class, [
                'label'    => 'Current Password',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'Leave blank to keep current password'],
            ])
            ->add('newPassword', PasswordType::class, [
                'label'    => 'New Password',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'Min 8 characters'],
                'constraints' => [
                    new Length(min: 8,
                        minMessage: 'New password must be at least 8 characters.'
                    ),
                    new Regex(
                        pattern: '/[A-Z]/',
                        message: 'Password must contain at least one uppercase letter.'
                    ),
                    new Regex(
                        pattern: '/[0-9]/',
                        message: 'Password must contain at least one number.'
                    ),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label'    => 'Confirm New Password',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'Repeat new password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Client::class]);
    }
}