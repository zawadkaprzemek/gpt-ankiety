<?php

namespace App\Form;

use App\Entity\Polling;
use App\Entity\User;
use App\Service\PollingService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CodeGeneratorType extends AbstractType
{
    private $token;
    private $pollingService;

    public function __construct(TokenStorageInterface $token, PollingService $pollingService)
    {
        $this->token = $token;
        $this->pollingService = $pollingService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user=$this->token->getToken()->getUser();
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
            ->add('randomLength',NumberType::class,[
                'label'=> 'Długość losowego ciągu znaków',
                'html5' => true,
                'data' => 4,
                'attr'=>[
                    'min'=>4,
                    'step'=>1,
                    'max'=>15
                ]
            ])
            ->add('randomContent',ChoiceType::class,[
                'label'=>'Skład losowego ciągu znaków',
                'choices'=>$this->generateRandomContentChoices(),
                'multiple'=> true,
                'expanded' => false,
                'placeholder'=> 'Wybierz skład losowego ciągu znaków'
            ])
            ->add('excludeFromRandomContent',TextType::class,[
                'label'=> 'Wyklucz z losowego ciągu znaków',
                'required' =>false
            ])
            ->add('multi',CheckboxType::class,[
                'label'=>'Wielokrotnego użytku',
                'required'=>false
            ])
            ->add('usesLimit',NumberType::class,[
                'label' => 'Limit użyc',
                'html5' => true,
                'data' => 10,
                'attr'=>[
                    'min'=>2,
                    'step'=>1,
                    'max'=>100
                ]
            ])
            ->add('polling',EntityType::class,[
                'label'=>'Ankieta',
                'class'=>Polling::class,
                'choice_label' => 'name',
                'choices' => $this->pollingService->getPollingsToCodeGenerator($user),
                'placeholder'=>'Wybierz ankiętę'
            ])
            ->add('submit',SubmitType::class,['label'=>'Generuj'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }

    private function generateRandomContentChoices(): array
    {
        return [
            'Cyfry' =>1,
            'Małe litery' => 2,
            'Duże litery' => 3,
        ];

    }
}
