<?php

namespace App\Form\contract;

use App\Entity\contract\ContractTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<ContractTemplate> */
class ContractTemplateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Template Title',
                'attr'  => ['placeholder' => 'Enter template title'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['placeholder' => 'Brief description of this template', 'rows' => 3],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contract Content',
                'attr'  => ['placeholder' => 'Write the contract template clauses here...', 'rows' => 10],
            ])
            ->add('contractType', ChoiceType::class, [
                'label'   => 'Contract Type',
                'choices' => [
                    'Fixed Price'  => 'fixed_price',
                    'Hourly Rate'  => 'hourly',
                    'Milestone'    => 'milestone',
                    'Retainer'     => 'retainer',
                ],
                'placeholder' => 'Select contract type',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContractTemplate::class]);
    }
}
