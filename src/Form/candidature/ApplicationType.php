<?php

namespace App\Form\candidature;

use App\Entity\candidature\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('projectId', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class)
            ->add('coverLetter', TextareaType::class, [
                'label' => '✉️ Professional Cover Letter',
                'attr' => [
                    'rows' => 6, 
                    'placeholder' => 'Sell your skills! Highlight your experience and why you are the best fit for this project...',
                    'class' => 'premium-input'
                ]
            ])
            ->add('proposedBudget', MoneyType::class, [
                'label' => 'Proposed Budget',
                'currency' => 'TND', // Assuming Tunisian Dinar or similar based on origin
            ])
            ->add('estimatedDuration', IntegerType::class, [
                'label' => 'Estimated Duration (in days)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }
}
