<?php

namespace App\Form\candidature;

use App\Entity\candidature\Evaluation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'label' => 'Rating',
                'choices' => [
                    '1 Star' => 1,
                    '2 Stars' => 2,
                    '3 Stars' => 3,
                    '4 Stars' => 4,
                    '5 Stars' => 5,
                ],
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Comment',
                'attr' => ['rows' => 4, 'placeholder' => 'Leave your comment (minimum 15 characters)...']
            ])
            ->add('projectId', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, [
                'required' => false,
            ])
            ->add('evaluatedId', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, [
                'mapped' => false,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
        ]);
    }
}
