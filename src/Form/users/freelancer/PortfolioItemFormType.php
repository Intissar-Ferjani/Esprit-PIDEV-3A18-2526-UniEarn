<?php

namespace App\Form\users\freelancer;

use App\Entity\users\freelancer\PortfolioItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PortfolioItemFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required'   => false,
                'empty_data' => '', // Prevents null SQL errors
                'attr'       => ['class' => 'custom-field', 'placeholder' => 'e.g. E-commerce Website']
            ])
            ->add('description', TextareaType::class, [
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['class' => 'custom-field', 'rows' => 4, 'placeholder' => 'Describe your project...']
            ])
            ->add('technologiesInput', TextType::class, [
                'mapped'     => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['class' => 'custom-field', 'placeholder' => 'e.g. React, Node.js, MongoDB']
            ])
            ->add('projectUrl', TextType::class, [
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['class' => 'custom-field', 'placeholder' => 'https://...']
            ])
            ->add('githubUrl', TextType::class, [
                'required'   => false,
                'empty_data' => '',
                'attr'       => ['class' => 'custom-field', 'placeholder' => 'https://github.com/...']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PortfolioItem::class,
        ]);
    }
}
