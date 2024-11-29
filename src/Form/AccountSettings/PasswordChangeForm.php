<?php

namespace App\Form\AccountSettings;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

/**
 * Class PasswordChangeForm
 *
 * The user password change form
 *
 * @extends AbstractType<User>
 *
 * @package App\Form\AccountSettings
 */
class PasswordChangeForm extends AbstractType
{
    /**
     * Build password update form
     *
     * @param FormBuilderInterface<User|null> $builder The form builder
     * @param array<string> $options The form options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => false,
                    'constraints' => [
                        new NotBlank(['message' => 'Please enter a password']),
                        new Length([
                            'min' => 8,
                            'max' => 155,
                            'minMessage' => 'Your password should be at least {{ limit }} characters',
                            'maxMessage' => 'Your password cannot be longer than {{ limit }} characters'
                        ])
                    ],
                ],
                'second_options' => ['label' => false]
            ]);
    }

    /**
     * Configure options for registration form
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
