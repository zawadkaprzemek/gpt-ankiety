<?php

namespace App\Form;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\QuestionType as EntityQuestionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $question=$options['data'];
        $builder
            ->add('content',TextareaType::class,['label'=>'Treść pytania'])
            ->add('required',CheckboxType::class,[
                'label'=>'Wymagane','required'=>false,
                'attr'=>['checked'=>$question->isRequired()]
                ])
            ->add('commentOn',CheckboxType::class,[
                'label'=>'Komentarz','mapped'=>false,'required'=>false,
                'attr'=>['checked'=>$question->getComment()!==null]
                ])
            ->add('comment',TextareaType::class,['label'=>'Treść komentarza','required'=>false])
            ->add('valueLabels',CheckboxType::class,[
                'label'=>'Etykiety do wartości','mapped'=>false,'required'=>false,
                'attr'=>['checked'=>$question->getMinValText()!==null]
                ])
            ->add('minValText',TextType::class,['attr'=>['placeholder'=>'Etykieta lewa'],'label'=>false,'required'=>false])
            ->add('middleValueLabel',CheckboxType::class,[
                'label'=>'Etykieta do środkowej wartości','mapped'=>false,'required'=>false,
                'attr'=>['checked'=>$question->getMiddleValText()!==null]
                ])
            ->add('middleValText',TextType::class,['attr'=>['placeholder'=>'Etykieta środkowa'],'label'=>false,'required'=>false])
            ->add('maxValText',TextType::class,['attr'=>['placeholder'=>'Etykieta prawa'],'label'=>false,'required'=>false])
            ->add('type',EntityType::class,[
                'label'=>(sizeof($question->getVotes())>0 ? false : 'Typ pytania'),
                'class'=>EntityQuestionType::class,
                'placeholder'=>'Wybierz typ pytania',
                'attr'=>['class'=>(sizeof($question->getVotes())>0 ? 'd-none' : '')]
                ])
            ->add('answers',CollectionType::class,[
                    'label'=>'Odpowiedzi',
                    'entry_type'=>AnswerType::class,
                    'entry_options'=>['label'=>false],
                    'allow_add'=>true,
                    'allow_delete'=>true,
                    'prototype' => true,
                    'by_reference' => false
                ])
            
            ->add('submit',SubmitType::class,['label'=>'Zapisz'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}
