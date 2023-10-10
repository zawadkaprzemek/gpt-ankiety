<?php

namespace App\Form;

use App\Entity\Polling;
use App\Entity\User;
use App\Repository\CodeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CodeGeneratorType extends AbstractType
{
    private $token;
    private CodeRepository $repository;

    public function __construct(TokenStorageInterface $token, CodeRepository $repository)
    {
        $this->token = $token;
        $this->repository = $repository;
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
            ->add('multi',CheckboxType::class,[
                'label'=>'Wielokrotnego użytku',
                'required'=>false
            ])
            ->add('polling',EntityType::class,[
                'label'=>'Ankieta',
                'class'=>Polling::class,
                'choice_label' => 'name',
                'choices' => ($user->isAdmin() ? $this->repository->findAll() : $user->getPollings()),
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
}
