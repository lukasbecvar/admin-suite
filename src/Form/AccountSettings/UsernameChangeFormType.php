<?php

namespace App\Form\AccountSettings;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class UsernameChangeFormType
 *
 * The username change form
 *
 * @extends AbstractType<User>
 *
 * @package App\Form\AccountSettings
 */
class UsernameChangeFormType extends AbstractType
{
    /**
     * Build the form
     *
     * @param FormBuilderInterface<User|null> $builder The form builder
     * @param array<string> $options The form options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a username']),
                    new Length([
                        'min' => 3,
                        'max' => 155,
                        'minMessage' => 'Your username should be at least {{ limit }} characters',
                        'maxMessage' => 'Your username cannot be longer than {{ limit }} characters'
                    ])
                ]
            ]);
    }

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver The options resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }
}
