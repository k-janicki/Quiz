<?php

namespace AppBundle\Form;


use AppBundle\Form\DataTransformer\DateTimePickerTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DateTimePickerType
 *
 */
class DateTimePickerType extends AbstractType
{
    private $attrDefaults = [
        'autocomplete' => 'off',
        'class' => 'datetimepicker',
        'data-date-locale' => 'pl',
        'data-date-format' => 'YYYY-MM-DD HH:mm',
        'data-mask' => '9999-99-99 99:99',
        'data-date-calendar-weeks' => 'true',
        'data-date-side-by-side' => 'true',
    ];
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addModelTransformer(new DateTimePickerTransformer());
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($view->vars['attr'])) {
            $view->vars['attr'] = array_merge(
                $this->attrDefaults,
                $view->vars['attr']
            );
        }

        parent::buildView($view, $form, $options);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'format' => 'YYYY-MM-DD HH:mm:ss',
                'attr' => $this->attrDefaults,
            ]
        );
    }

    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'datetimepicker';
    }
}
