<?php

namespace App\Form\users\client;

use App\Entity\users\client\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ClientProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', NumberType::class, [
                'label'       => 'Initial Budget (Optional)',
                'required'    => false,
                'scale'       => 2,
                'attr'        => ['placeholder' => '0.00'],
                'constraints' => [new PositiveOrZero(message: 'Amount cannot be negative.')],
            ])
            ->add('company', TextType::class, [
                'label' => 'Company / Organization',
                'attr'  => ['placeholder' => 'Enter your company name'],
            ])
            ->add('industry', ChoiceType::class, [
                'label'       => 'Industry / Category',
                'placeholder' => 'Select your industry',
                'choices'     => [
                    'Technology & IT'        => 'Technology & IT',
                    'Marketing & Advertising'=> 'Marketing & Advertising',
                    'Design & Creative'      => 'Design & Creative',
                    'Writing & Content'      => 'Writing & Content',
                    'Business & Consulting'  => 'Business & Consulting',
                    'Education & Training'   => 'Education & Training',
                    'Healthcare'             => 'Healthcare',
                    'Finance & Accounting'   => 'Finance & Accounting',
                    'Legal Services'         => 'Legal Services',
                    'Real Estate'            => 'Real Estate',
                    'E-commerce & Retail'    => 'E-commerce & Retail',
                    'Manufacturing'          => 'Manufacturing',
                    'Hospitality & Tourism'  => 'Hospitality & Tourism',
                    'Construction'           => 'Construction',
                    'Other'                  => 'Other',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Client::class]);
    }
}