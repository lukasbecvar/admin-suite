<?php

namespace App\Form\Settings;

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
 * The password change form type
 *
 * @package App\Form\Settings
 */
class PasswordChangeForm extends AbstractType
{
    /**
     * Builds the password update form
     *
     * @param FormBuilderInterface $builder
     * @param array<string> $options
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
     * Configures the options for the registration form
     *
     * @param OptionsResolver $resolver
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
