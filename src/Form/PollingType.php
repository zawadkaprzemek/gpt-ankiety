<?php

namespace App\Form;

use App\Entity\Polling;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PollingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,['label'=>'Nazwa'])
            ->add('textContent',TextareaType::class,['label'=>'Wiadomość powitalna'])
            ->add('thankYouText',TextareaType::class,['label'=>'Wiadomość końcowa'])
            ->add('submit',SubmitType::class,['label'=>'Zapisz'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Polling::class,
        ]);
    }
}
