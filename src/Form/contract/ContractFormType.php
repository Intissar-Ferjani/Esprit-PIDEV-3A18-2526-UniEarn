<?php

namespace App\Form\contract;

use App\Entity\contract\Contract;
use App\Entity\users\freelancer\Freelancer;
use App\Repository\contract\ContractTemplateRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Contract Title',
                'attr'  => ['placeholder' => 'Enter contract title'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contract Content',
                'attr'  => ['placeholder' => 'Contract details and clauses...', 'rows' => 10],
            ])
            ->add('amount', MoneyType::class, [
                'label'    => 'Amount',
                'currency' => 'USD',
                'attr'     => ['placeholder' => '0.00'],
            ])
            ->add('startDate', DateType::class, [
                'label'  => 'Start Date',
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label'  => 'End Date',
                'widget' => 'single_text',
            ])
            ->add('freelancer', EntityType::class, [
                'class'        => Freelancer::class,
                'choice_label' => fn(Freelancer $f) => $f->getName(),
                'label'        => 'Freelancer',
                'placeholder'  => 'Select a freelancer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Contract::class]);
    }
}
