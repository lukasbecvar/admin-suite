<?php

namespace App\Form\Todo;

use App\Entity\Todo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class CreateTodoFormType
 *
 * The new todo creation form
 *
 * @package App\Form\Todo
 */
class CreateTodoFormType extends AbstractType
{
    /**
     * Build todo creation form
     *
     * @param FormBuilderInterface $builder
     *
     * @param array<string> $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('todo_text', TextType::class, [
            'label' => false,
            'attr' => [
                'autocomplete' => 'off',
                'maxlength' => 512
            ],
            'constraints' => [
                new NotBlank(['message' => 'Please enter a todo text']),
                new Length([
                    'min' => 1,
                    'max' => 512,
                    'minMessage' => 'Your todo text should be at least {{ limit }} characters',
                    'maxMessage' => 'Your todo text cannot be longer than {{ limit }} characters'
                ])
            ]
        ]);
    }

    /**
     * Configure options for the todo creation form
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Todo::class
        ]);
    }
}
