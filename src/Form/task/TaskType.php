<?php

namespace App\Form\task;

use App\Entity\project\Project;
use App\Entity\task\Task;
use App\Enum\TaskStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('deadline', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('taskStatus', EnumType::class, [
                'class' => TaskStatus::class,
                'choice_label' => static fn (TaskStatus $status): string => $status->getDisplayName(),
            ])
            ->add('role')
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'High' => 'High',
                    'Medium' => 'Medium',
                    'Low' => 'Low',
                ],
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'title',
                'choices' => $options['projects'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'projects' => [],
        ]);
    }
}
