<?php

namespace App\Form\Database;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Class QueryConsoleFormType
 *
 * The query console form
 *
 * @package App\Form\Database
 */
class QueryConsoleFormType extends AbstractType
{
    /**
     * Builds the query console form
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array<string> $options The form options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('query', TextareaType::class, [
            'label' => false,
            'attr' => [
                'autocomplete' => 'off',
                'cols' => 125,
                'rows' => 10
            ],
            'constraints' => [
                new NotBlank(['message' => 'Please enter a query']),
                new Length([
                    'min' => 1,
                    'max' => 100000000,
                    'minMessage' => 'Your query should be at least {{ limit }} characters',
                    'maxMessage' => 'Your query cannot be longer than {{ limit }} characters'
                ])
            ]
        ]);
    }
}
