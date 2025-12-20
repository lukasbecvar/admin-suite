<?php

namespace App\Form\Todo;

use App\Entity\Todo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Class CreateTodoFormType
 *
 * The todo create form
 *
 * @extends AbstractType<Todo>
 *
 * @package App\Form\Todo
 */
class CreateTodoFormType extends AbstractType
{
    /**
     * Build todo create form
     *
     * @param FormBuilderInterface<Todo|null> $builder
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('todo_text', TextareaType::class, [
            'label' => false,
            'attr' => [
                'autocomplete' => 'off',
                'maxlength' => 1024,
                'rows' => 1
            ],
            'constraints' => new Sequentially([
                new NotBlank(message: 'Please enter a todo text'),
                new Length(
                    min: 1,
                    max: 1024,
                    minMessage: 'Your todo text should be at least {{ limit }} characters',
                    maxMessage: 'Your todo text cannot be longer than {{ limit }} characters'
                )
            ])
        ]);
    }

    /**
     * Configure options for the todo create form
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
