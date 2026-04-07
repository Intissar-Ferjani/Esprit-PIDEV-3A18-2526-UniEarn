<?php

namespace App\Form\project;

use App\Entity\project\Project;
use App\Enum\Projectstatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('budget')
            ->add('status', EnumType::class, [
                'class' => Projectstatus::class,
                'choice_label' => static fn (Projectstatus $status): string => $status->getDisplayName(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
