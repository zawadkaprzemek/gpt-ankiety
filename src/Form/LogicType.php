<?php

namespace App\Form;

use App\Entity\Logic;
use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data=$options['data'];
        $question=$data->getQuestion();
        $builder
            ->add('begin_action',ChoiceType::class,[
                'label'=>'Jeżeli odpowiadający...',
                'choices'=>$this->generateActionChoices($question),
                'mapped'=>false,
                'data'=>($data->getIfAction()['begin_action']?? null)
            ])
            ->add('begin_action_value',ChoiceType::class,[
                'label'=>false,
                'choices'=>$this->generateActionValues($question),
                'mapped'=>false,
                'data'=>($data->getIfAction()['begin_action_value'] ?? null)
                ])
            ->add('end_action',ChoiceType::class,[
                'label'=>'to...',
                'choices'=>[
                    'Idź do strony'=>'go_to_page',
                    'Pomiń stronę'=>'skip_page',
                    'Wymuś zakończenie ankiety'=>'end_polling'
                ],
                'mapped'=>false,
                'data'=>($data->getThenAction()['end_action'] ?? null )
            ])
            ->add('end_action_value',ChoiceType::class,[
                'label'=>false,
                'choices'=>$this->generateEndValues($question),
                'mapped'=>false,
                'data'=>($data->getThenAction()['end_action_value'] ?? null)
            ])
            ->add('submit',SubmitType::class,['label'=>'Zapisz'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logic::class,
        ]);
    }


    private function generateActionChoices(Question $question)
    {
        $array=[
            'Odpowie na to pytanie'=>'answer_question',
            'Nie odpowie na to pytanie'=> 'dont_answer_question'
        ];


        if($question->getType()->getId()==2)
        {
            $tmp=[
                'Wybierze odpowiedź' => 'choose_answer',
                'Nie wybierze odpowiedzi' => 'dont_choose_answer'
            ];
            $array=array_merge($array,$tmp);
        }elseif($question->getType()->getId()==3)
            {
                $tmp=[
                    'Wybierze wartość <' => 'answer_value_less',
                    'Wybierze wartość =' => 'answer_value_equal',
                    'Wybierze wartość >' => 'answer_value_greather',
                ];
                $array=array_merge($array,$tmp);
            }
        return $array;

    }

    private function generateActionValues(Question $question)
    {
        $array=[];


        if($question->getType()->getId()==2)
        {
            $answers=$question->getAnswers();
            foreach($answers as $answer)
            {
                $array[$answer->getContent()]=$answer->getId();
            }
        }elseif($question->getType()->getId()==3)
            {
                for($i=0;$i<=10;$i++)
                {
                    $array[$i]=$i;
                }
            }
        return $array;

    }

    private function generateEndValues(Question $question)
    {
        $array=[];
        $pages=$question->getPolling()->getPages();
        foreach($pages as $page)
        {
            if($page->getNumber()!=$question->getPage()->getNumber())
            {
                $array['Strona '.$page->getNumber()]=$page->getNumber();
            }
        }

        return $array;
    }
}
