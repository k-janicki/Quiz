<?php

namespace AppBundle\Form;


use AppBundle\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Ldap\Adapter\ExtLdap\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class QuestionType
 *
 */
class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'text',
                TextType::class
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'required'   => true,
                    'choices' => [
                        'checkbox' => 'checkbox',
                        'sortable' => 'sortable',
                    ]
                ]
            )
            ->add('answers', CollectionType::class,[
                'required' => true,
                'label' => false,
                'entry_type' => AnswerType::class,
                'entry_options' => [
                    'error_bubbling' => true,
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'error_bubbling' => true,
                'prototype_name' => '__options_name__',
                'constraints' => [
                    new NotBlank(),
                    new Valid(),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
            'label_format' => 'quiz.form.question.%name%',
        ]);
    }
}
