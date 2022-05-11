<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    /**
     * @var UserPasswordHasherInterface
     */
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user=new User();
        $user
            ->setRoles(["ROLE_USER","ROLE_ADMIN"])
            ->setEmail("gpvoting@gpteam.pl")
            ->setIsVerified(true)
            ->setUserType(User::ADMIN_TYPE)
            ->setPassword($this->encoder->hashPassword($user,"GPTeam1234"))
        ;
        $manager->persist($user);

        $manager->flush();
    }
}
