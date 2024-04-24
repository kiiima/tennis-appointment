<?php

namespace App\DataFixtures;

use App\Entity\BusyAppointments;
use App\Entity\TennisGround;
use App\Entity\User;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(
        private UserPasswordHasherInterface $hasherPassword
    )
    {
        
    }

    public function load(ObjectManager $manager): void
    {
        $user1 = new User();
        $user1->setEmail('test1@test1.com');
        $user1->setPassword(
            $this->hasherPassword->hashPassword(
                $user1,
                '12345678'
            )   
        );
        $user1->setVerified(true);
        $manager->persist($user1);

        $user2 = new User();
        $user2->setEmail('test2@test2.com');
        $user2->setPassword(
            $this->hasherPassword->hashPassword(
                $user2,
                '12345678'
            )   
        );
        $manager->persist($user2);

        $user3 = new User();
        $user3->setEmail('test3@test3.com');
        $user3->setPassword(
            $this->hasherPassword->hashPassword(
                $user3,
                '12345678'
            )   
        );
        $manager->persist($user3);

        $ground1 = new TennisGround();
        $ground1->setName('Ground1');
        $manager->persist($ground1);

        $ground2 = new TennisGround();
        $ground2->setName('Ground2');
        $manager->persist($ground2);

        $ground3 = new TennisGround();
        $ground3->setName('Ground3');
        $manager->persist($ground3);

        $ground4 = new TennisGround();
        $ground4->setName('Ground4');
        $manager->persist($ground4);

        $today = new DateTime();
        $booking1 = new BusyAppointments($user1, $ground1);
        $booking1->setTime(9);
        $booking1->setDate($today);
        $manager->persist($booking1);

        $booking2 = new BusyAppointments($user2, $ground3);
        $booking2->setTime(18);
        $booking2->setDate($today);
        $manager->persist($booking2);

        $booking3 = new BusyAppointments($user3, $ground2);
        $booking3->setTime(13);
        $booking3->setDate($today);
        $manager->persist($booking3);

        $booking4 = new BusyAppointments($user1, $ground4);
        $booking4->setTime(10);
        $booking4->setDate($today);
        $manager->persist($booking4);

        $nextDay = clone $today;
        $booking6 = new BusyAppointments($user1, $ground1);
        $booking6->setTime(9);
        $booking6->setDate($nextDay->modify('+1 day'));
        $manager->persist($booking6);

        $booking5 = new BusyAppointments($user2, $ground3);
        $booking5->setTime(9);
        $booking5->setDate($today);
        $manager->persist($booking5);

        $manager->flush();
    }
}
