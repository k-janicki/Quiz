<?php

namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class LoginType
 *
 */
class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'login',
                TextareaType::class,
                [
                    'required'      => true,
                ]
            )
            ->add(
                'password',
                TextareaType::class,
                [
                    'required'   => true,
                ]
            );
    }
}
