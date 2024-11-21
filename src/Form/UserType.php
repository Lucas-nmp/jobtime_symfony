<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'label' => 'User ID',
                'attr' => ['min' => 1000, 'max' => 9999],
            ])
            

            ->add('name', TextType::class, [
                'constraints' => [new NotBlank(['message' => 'El nombre es obligatorio'])]
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'El email es obligatorio']),
                    new Email(['message' => 'Introduce un email válido'])
                ]
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [new NotBlank(['message' => 'La contraseña es obligatoria'])]
            ])
            ->add('phone', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'El teléfono es obligatorio']),
                    new Length([
                        'min' => 9,
                        'max' => 9,
                        'exactMessage' => 'El número de teléfono no puede tener más de 9 dígitos'
                    ])
                ]
            ])

            ->add('dailyWorkHours', NumberType::class, [
                'label' => 'Horas Diarias de Trabajo',
                'attr' => [
                    'min' => 0,
                    'step' => 0.5, // Permitir incrementos de 0.5
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Este campo es obligatorio']),
                ]
            ]);       
    }

    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
