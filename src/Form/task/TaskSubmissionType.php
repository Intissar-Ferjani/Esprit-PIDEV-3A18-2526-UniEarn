<?php

namespace App\Form\task;

use App\Entity\task\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Url;

class TaskSubmissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('submissionLink', UrlType::class, [
                'required' => false,
                'label' => 'Submission link',
                'default_protocol' => 'https',
                'constraints' => [
                    new Url([
                        'message' => 'Please enter a valid URL.',
                    ]),
                ],
            ])
            ->add('submissionFile', FileType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Submission file',
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $link = trim((string) $form->get('submissionLink')->getData());
            $file = $form->get('submissionFile')->getData();

            if ($link === '' && $file === null) {
                $form->addError(new FormError('Provide a submission link or upload a file.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'validation_groups' => false,
        ]);
    }
}
