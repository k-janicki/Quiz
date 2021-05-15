<?php

namespace AppBundle\Form;


use AppBundle\Entity\Quiz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class QuizType
 *
 */
class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('name', TextType::class)
                ->add('description', TextareaType::class, [
                    'required' => false,
                ])
                ->add('dateStart', DateTimePickerType::class)
                ->add('dateEnd', DateTimePickerType::class)
                ->add('tries', NumberType::class)
                ->add('questions', CollectionType::class,[
                    'required' => true,
                    'label' => false,
                    'entry_type' => QuestionType::class,
                    'entry_options' => [
                        'error_bubbling' => true,
                        'label' => false,
                    ],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'by_reference' => false,
                    'error_bubbling' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Count([
                            'min' => 1,
                            'minMessage' => 'Quiz musi zawierać jedno lub więcej pytań',
                        ]),
                        new Valid(),
                    ]
                ]);
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();

                $label = 'Zapisz';
                $form->add(
                    'submit',
                        SubmitType::class,
                    [
                        'label' => $label
                    ]
                );
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Quiz::class,
            'label_format' => 'quiz.form.%name%',
        ]);
    }
}
