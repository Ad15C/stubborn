<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       $builder
        ->add('name', TextType::class, [
            'label' => 'Nom',
            'constraints' => [new NotBlank(['message' => 'Veuillez saisir un nom'])],
        ])
        ->add('email', EmailType::class, ['label' => 'Email'])
        ->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'first_options' => ['label' => 'Mot de passe'],
            'second_options' => ['label' => 'Confirmer le mot de passe'],
            'invalid_message' => 'Les mots de passe doivent correspondre.',
            'mapped' => false,
            'constraints' => [
                new NotBlank(['message' => 'Veuillez saisir un mot de passe']),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                    'max' => 4096,
                ]),
            ],
        ])
        ->add('deliveryAddress', TextType::class, [
            'label' => 'Adresse de livraison',
            'required' => false,
            'attr' => ['placeholder' => 'ex: 8 Rue du Bac, 54100 Nancy']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}