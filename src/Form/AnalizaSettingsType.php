<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnalizaSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_from',DateType::class,array(
                'label'=>'Data od','html5'=>true,
                'widget'=>'single_text',
            ))
            ->add('date_to',DateType::class,array(
                'label'=>'Data do','html5'=>true,
                'widget'=>'single_text',
            ))
            ->add('all_data', CheckboxType::class,array(
                'label' => 'Wszystkie wyniki',
                'required' => false,
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Pokaż wyniki'
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
