<?php

namespace App\DataFixtures;

use App\Entity\QuestionType;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class QuestionTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $type=new QuestionType();
        $type->setName('Otwarte');
        $type2=new QuestionType();
        $type2->setName('Zamknięte');
        $type3=new QuestionType();
        $type3->setName('NPS');
        $type4=new QuestionType();
        $type4->setName('Wstawka');
        $manager->persist($type);
        $manager->persist($type2);
        $manager->persist($type3);
        $manager->persist($type4);
        $manager->flush();
    }
}
