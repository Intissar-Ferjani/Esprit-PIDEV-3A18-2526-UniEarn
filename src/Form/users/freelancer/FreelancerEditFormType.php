<?php

namespace App\Form\users\freelancer;

use App\Entity\users\freelancer\Freelancer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;

class FreelancerEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ── User fields (mapped=false, handled manually in controller) ──
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

            // ── Freelancer fields (mapped to entity) ──
            ->add('pricePerHour', NumberType::class, [
                'label' => 'Hourly Rate (TND/hour)',
                'scale' => 2,
                'attr'  => ['placeholder' => '0.00'],
                'constraints' => [
                    new NotBlank(message: 'Hourly rate is required.'),
                    new Positive(message: 'Hourly rate must be greater than 0.'),
                    new LessThanOrEqual(value: 9999,
                        message: 'Hourly rate cannot exceed 9999 TND.'
                    ),
                ],
            ])
            ->add('skillsInput', TextType::class, [
                'label'    => 'Skills',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'e.g. PHP, JavaScript, Python'],
                'constraints' => [
                    new NotBlank(message: 'Please add at least one skill.'),
                ],
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Professional Bio',
                'attr'  => [
                    'placeholder' => 'Tell clients about yourself...',
                    'rows'        => 5,
                ],
                'constraints' => [
                    new NotBlank(message: 'Bio is required.'),
                    new Length(min: 10, max: 500,
                        minMessage: 'Bio must be at least 10 characters.',
                        maxMessage: 'Bio cannot exceed 500 characters.'
                    ),
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
        $resolver->setDefaults([
            'data_class' => Freelancer::class,
        ]);
    }
}