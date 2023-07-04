<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Символьный код',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(message: 'Символьный код не может быть пустым'),
                    new Length(max: 255, maxMessage: 'Символьный код должен быть не более 255 символов')
                ]
            ])
            ->add('title', TextType::class, [
                'label' => 'Название',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(message: 'Название не может быть пустым'),
                    new Length(max: 255, maxMessage: 'Название должно быть не более 255 символов')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'constraints' => [
                    new Length(max: 1000, maxMessage: 'Описание должно быть не более 1000 символов')
                ]
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Полный курс' => 'buy',
                    'Аренда курса' => 'rent',
                    'Бесплатный курс' => 'free'
                ],
                'invalid_message' => 'Выберите правильный тип оплаты!',
                'required' => true,
                'mapped' => false,
                'label' => 'Тип оплаты',
                'constraints' => [new NotBlank(['message' => 'Поле не должно быть пустым!']),
                    new Choice(['message' => 'Выберите правильный тип оплаты!',
                        'choices' => ['buy', 'rent', 'free']
                    ])]
            ])
            ->add('price', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Цена курса',
                'constraints' => [
                    new PositiveOrZero(['message' => 'Курс не может стоить меньше 0!'])]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
