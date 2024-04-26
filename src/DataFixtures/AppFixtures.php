<?php

namespace App\DataFixtures;

use App\Entity\BusyAppointments;
use App\Entity\TennisGround;
use App\Entity\User;
use App\Entity\WorkingTime;
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

        $workingTime = new WorkingTime();

        // Postavite vrednost za startTime na 8 sati
        $startTime = new \DateTime();
        $startTime->setTime(8, 0, 0);
        $workingTime->setStartTime($startTime);

        // Postavite vrednost za endTime na 21 sat
        $endTime = new \DateTime();
        $endTime->setTime(21, 0, 0);
        $workingTime->setEndTime($endTime);

        // Sačuvajte instancu u bazi podataka
        $workingTime->setDescription('shift1');
        $workingTime->setDefaultTime(true);
        $manager->persist($workingTime);
        $manager->flush();

        $user1 = new User();
        $user1->setEmail('test1@test1.com');
        $user1->setPassword(
            $this->hasherPassword->hashPassword(
                $user1,
                '12345678'
            )   
        );
        $user1->setBlocked(false);
        $user1->setVerified(true);
        $manager->persist($user1);

        $user2 = new User();
        $user2->setEmail('test2@test2.com');
        $user2->setBlocked(false);
        $user2->setPassword(
            $this->hasherPassword->hashPassword(
                $user2,
                '12345678'
            )   
        );
        $manager->persist($user2);

        $user3 = new User();
        $user3->setEmail('test3@test3.com');
        $user3->setBlocked(false);
        $user3->setPassword(
            $this->hasherPassword->hashPassword(
                $user3,
                '12345678'
            )   
        );
        $manager->persist($user3);

        
        $user4 = new User();
        $user4->setEmail('admin@admin.com');
        $user4->setBlocked(false);
        $user4->setPassword(
            $this->hasherPassword->hashPassword(
                $user2,
                '12345678'
            )   
        );

        $array = ['ROLE_ADMIN'];
        $user4->setRoles($array);
        $user4->setVerified(true);
        $manager->persist($user4);

        $ground1 = new TennisGround();
        $ground1->setName('Ground1');
        $ground1->setBlocked(false);
        $manager->persist($ground1);

        $ground2 = new TennisGround();
        $ground2->setName('Ground2');
        $ground2->setBlocked(false);
        $manager->persist($ground2);

        $ground3 = new TennisGround();
        $ground3->setName('Ground3');
        $ground3->setBlocked(false);
        $manager->persist($ground3);

        $ground4 = new TennisGround();
        $ground4->setName('Ground4');
        $ground4->setBlocked(false);
        $manager->persist($ground4);

        $today = new DateTime();
        $booking1 = new BusyAppointments($user1, $ground1);
        $time = new \DateTime();
        $time->setTime(9, 0, 0);
        $booking1->setStartTime($time);
        $time = new \DateTime();
        $time->setTime(10,0,0);
        $booking1->setEndTime($time);
        $booking1->setDate($today);
        $booking1->setFullName('Mika Mikic');
        $booking1->setPhone(1234567123);
        $manager->persist($booking1);

        $booking2 = new BusyAppointments($user2, $ground3);
        $time = new \DateTime();
        $time->setTime(10, 0, 0);
        $booking2->setStartTime($time);
        $time = new \DateTime();
        $time->setTime(11,0,0);
        $booking2->setEndTime($time);
        $booking2->setDate($today);
        $booking2->setFullName('Zika Zikic');
        $booking2->setPhone(1234567123);
        $manager->persist($booking2);

        $booking3 = new BusyAppointments($user3, $ground2);
        $time = new \DateTime();
        $time->setTime(13, 0, 0);
        $booking3->setStartTime($time);
        $time = new \DateTime();
        $time->setTime(14,0,0);
        $booking3->setEndTime($time);
        $booking3->setDate($today);
        $booking3->setFullName('Rika Rikic');
        $booking3->setPhone(1234567123);
        $manager->persist($booking3);

        $booking4 = new BusyAppointments($user1, $ground4);
        $time = new \DateTime();
        $time->setTime(10, 0, 0);
        $booking4->setStartTime($time);
        $time = new \DateTime();
        $time->setTime(11,0,0);
        $booking4->setEndTime($time);
        $booking4->setDate($today);
        $booking4->setFullName('Rika Rikic');
        $booking4->setPhone(1234567123);
        $manager->persist($booking4);

        $nextDay = clone $today;
        $booking6 = new BusyAppointments($user1, $ground1);
        $time = new \DateTime();
        $time->setTime(9, 0, 0);
        $booking6->setStartTime($time);
        $time = new \DateTime();
        $time->setTime(10,0,0);
        $booking6->setEndTime($time);
        $booking6->setDate($nextDay->modify('+1 day'));
        $booking6->setFullName('Tika Tikic');
        $booking6->setPhone(1234567123);
        $manager->persist($booking6);

        $booking5 = new BusyAppointments($user2, $ground3);
        $time = new \DateTime();
        $time->setTime(9, 0, 0);
        $booking5->setStartTime($time);
        $time = new \DateTime();
        $time->setTime(10,0,0);
        $booking5->setEndTime($time);
        $booking5->setDate($today);
        $booking5->setFullName('Rika Sikic');
        $booking5->setPhone(1234567123);
        $manager->persist($booking5);

        $manager->flush();
    }
}
