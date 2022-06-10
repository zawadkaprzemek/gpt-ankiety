<?php


namespace App\Service;


use App\Entity\MeetingVoting;
use App\Entity\Polling;
use App\Entity\Question;
use App\Entity\SessionUser;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExcellGenerator
{
    private ParameterBagInterface $parameterBag;
    private EntityManagerInterface $em;
    
    public function __construct(ParameterBagInterface $parameterBag,EntityManagerInterface $em)
    {
        $this->parameterBag = $parameterBag;
        $this->em=$em;
    }

    private function getParameter(string $name)
    {
        return $this->parameterBag->get($name);
    }

    public function createExcel(Polling $polling): Spreadsheet
    {
        $excell=new Spreadsheet();
        $excell->getDefaultStyle()->getFont()->setName('Arial');
        $excell->getDefaultStyle()->getFont()->setSize(8);

        return $this->generateSheets($excell,$polling);
     
    }

    private function generateSheets(Spreadsheet $excell,Polling $polling)
    {
        foreach ($polling->getQuestions() as $key => $question)
        {
            if($key ==0)
            {
                $sheet=$excell->getActiveSheet();
            }else{
                $sheet=new Worksheet();
                $excell->addSheet($sheet);
            }
            $sheet->setTitle('Pytanie '.($key+1));
            $sheet->getDefaultColumnDimension()->setWidth(15);
            $sheet=$this->printVotingTitle($sheet,$question);
            

        }
        $excell->setActiveSheetIndex(0);

        return $excell;
    }

    private function saveFile($excell)
    {
        $writer= new Xlsx($excell);
        $writer->setOffice2003Compatibility(true);
        $file=$this->getParameter('excell_xlsx_path').'course_raport.xlsx';
        $writer->save($file);
    }

    private function printVotingTitle(Worksheet $sheet, Question $question)
    {
        $sheet
            ->mergeCells('A1:B1')
            ->mergeCells('A2:B2')
            ->mergeCells('A3:B3')
            ->setCellValue('A1',$question->getContent())
            ->setCellValue('A2',"Typ pytania: ".$question->getType()->getName())
            ->setCellValue('A3',"Wymagane: ".($question->isRequired()? "Tak": "Nie"))
            ;
        $sheet=$this->printAnswers($sheet,$question);
        return $this->printVotesHeaders($sheet);
    }

    private function printAnswers(Worksheet $sheet,Question $question)
    {
        $answers=[];
        $votes=$question->getVotes();
        $results=[];
        switch($question->getType()->getId())
        {
            case 1:
                foreach($question->getVotes() as $vote)
                {
                    $answers[]=$vote->getAnswer()[0];
                }
                break;
            case 2:
                foreach($question->getAnswers() as $answer)
                {
                    $answers[$answer->getId()]=$answer->getContent();
                    $results[$answer->getId()]=['count'=>0,'proc'=>0];
                }
                break;
            case 3:
                for($i=0;$i<=10;$i++)
                {
                    $answers[$i]=$i;
                    $results[$i]=['count'=>0,'proc'=>0];
                }
                break;
            default:
            $answers=[];
                break;
        }
        /** @var $repo SessionUserRepository */
        $repo=$this->em->getRepository(SessionUser::class);
        $allUsers=sizeof($repo->getAllUsersForPolling($question->getPolling()));
        $voted=[];
        foreach($votes as $vote)
        {
            if($vote->getAnswer()[0]!=""&&$vote->getAnswer()[0]!==null)
            $voted[]=$vote;
        }
        $voted=sizeof($voted);
        $skipped=$allUsers-$voted;
        if($allUsers==0)
        {
            $votedProc=0;
            $skippedProc=0;
        }else{
            if($voted==0)
            {
                $votedProc=0;
                $skippedProc=100;
            }elseif($skipped==0)
            {
                $votedProc=100;
                $skippedProc=0;
            }else{
                $votedProc=round(($voted/$allUsers)*100,2);
                $skippedProc=round(($skipped/$allUsers)*100,2);
            }
        }
        
        $sheet->setCellValue('E1',$voted." (".$votedProc."%)");
        $sheet->setCellValue('G1',$skipped." (".$skippedProc."%)");
        $number=5;
        foreach($answers as $answer)
        {
            $sheet
             ->setCellValue('A'.$number,$answer);
             $number++;
        }

        if($question->getType()->getId()>1)
        {
            $sheet
            ->setCellValue('D4',"Ilość")
            ->setCellValue('E4',"Procenty")
            ;
            $sum=0;

            foreach($votes as $vote)
            {
                $results[$vote->getAnswer()[0]]['count']++;
                if($question->getType()->getId()==3)
                {
                    $sum+=$vote->getAnswer()[0];
                }
            }


            $number=5;
            foreach($results as $result)
            {
                if($result['count']>0)
                {
                    $result['proc']=round(($result['count']/$voted)*100,2);
                }
                $sheet
                ->setCellValue('D'.$number,$result['count'])
                ->setCellValue('E'.$number,$result['proc']."%")
                ;
                $number++;
            }

            if($question->getType()->getId()==3)
            {
                if($voted>0)
                {
                    $med=round($sum/$voted,2);
                }else{
                    $med=0;
                }
                $sheet
                ->setCellValue('F4',"Średnia")
                ->setCellValue('G4',$med)
                ;
                $number++;
            }
        }
        return $sheet;
    }

    private function printVotesHeaders(Worksheet $sheet)
    {
        $sheet
            ->setCellValue('D1',"Odpowiedzi:")
            ->setCellValue('F1',"Pominięć:")
            ;
        
        return $sheet;
    }

    

    private function colorCell(Worksheet $sheet,string $cell): Worksheet
    {
        $styleArray = [
            'font' => [
                'bold' => false,
            ],
            'alignment' => [
                'horizontal' => Alignment::VERTICAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => array(
                'type' => Fill::FILL_SOLID,
                'color' => array('rgb' => 'FF0000')
            ),
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet
            ->getStyle($cell)
            ->applyFromArray($styleArray)
        ;
        $sheet->setCellValue($cell,'X');
        return $sheet;
    }

}