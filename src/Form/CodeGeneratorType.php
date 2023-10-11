<?php

namespace App\Form;

use App\Entity\Polling;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CodeGeneratorType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pollings= $options['pollings'];
        $builder
            ->add('prefix',TextType::class)
            ->add('count',NumberType::class,[
                'label'=>'Ilość',
                'html5'=>true,
                'attr'=>[
                    'min'=>1,
                    'step'=>1,
                    'max'=>200
                ]
            ])
            ->add('multi',CheckboxType::class,[
                'label'=>'Wielokrotnego użytku',
                'required'=>false
            ])
            ->add('polling',EntityType::class,[
                'label'=>'Ankieta',
                'class'=>Polling::class,
                'choice_label' => 'name',
                'choices' => $pollings,
                'placeholder'=>'Wybierz ankiętę'
            ])
            ->add('submit',SubmitType::class,['label'=>'Generuj'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'pollings' => null
        ]);
    }
}
