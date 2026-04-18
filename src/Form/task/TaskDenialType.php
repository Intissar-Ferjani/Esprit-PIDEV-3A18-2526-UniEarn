<?php

namespace App\Form\task;

use App\Entity\task\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TaskDenialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('clientFeedback', TextareaType::class, [
            'label' => 'Feedback for freelancer',
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Client feedback is required when denying a task.',
                ]),
            ],
            'attr' => [
                'rows' => 6,
                'placeholder' => 'Explain what should be fixed before the task can be accepted.',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
