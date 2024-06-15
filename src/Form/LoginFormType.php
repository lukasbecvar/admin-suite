<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * Class LoginFormType
 *
 * The auth login form type
 *
 * @package App\Form
 */
class LoginFormType extends AbstractType
{
    /**
     * Build the auth login form
     *
     * @param FormBuilderInterface $builder The form builder
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
                    new NotBlank(['message' => 'Please enter a username'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password'])
                ]
            ])
            ->add('remember', CheckboxType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false
            ]);
    }

    /**
     * Configure the options
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
