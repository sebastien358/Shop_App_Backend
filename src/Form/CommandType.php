<?php

namespace App\Form;

use App\Entity\Command;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('address', TextType::class)
            ->add('zipCode', TextType::class)
            ->add('city', TextType::class)
            ->add('country', ChoiceType::class, [
                'choices' => [
                    'France' => 'french',
                    'Allemage' => 'germany',
                    'Belgique' => 'belgium',
                ]
            ])
            ->add('phoneNumber', TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Command::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
