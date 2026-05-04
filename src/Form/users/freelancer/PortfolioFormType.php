<?php

namespace App\Form\users\freelancer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<array<string, mixed>> */
class PortfolioFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ── Portfolio ──────────────────────────────────────────────
            ->add('portfolioTitle', TextType::class, [
                'label'    => 'Portfolio Title',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'e.g., My Web Development Projects'],
            ])
            ->add('portfolioDescription', TextareaType::class, [
                'label'    => 'Portfolio Description',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'Describe your portfolio...', 'rows' => 3],
            ])
            // ── Portfolio Item (optional) ──────────────────────────────
            ->add('projectTitle', TextType::class, [
                'label'    => 'Project Title',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'e.g., E-commerce Website'],
            ])
            ->add('projectDescription', TextareaType::class, [
                'label'    => 'Project Description',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'What did you build?', 'rows' => 3],
            ])
            ->add('technologies', TextType::class, [
                'label'    => 'Technologies Used',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['placeholder' => 'e.g., React, Node.js, MongoDB (comma-separated)'],
            ])
            ->add('projectUrl', UrlType::class, [
                'label'         => 'Project URL (Optional)',
                'mapped'        => false,
                'required'      => false,
                'default_protocol' => 'https',
                'attr'          => ['placeholder' => 'https://...'],
            ])
            ->add('githubUrl', UrlType::class, [
                'label'         => 'GitHub URL (Optional)',
                'mapped'        => false,
                'required'      => false,
                'default_protocol' => 'https',
                'attr'          => ['placeholder' => 'https://github.com/...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}