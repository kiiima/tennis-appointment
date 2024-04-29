<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\WorkingTime;
use App\Entity\TennisGround;
use App\Form\WorkingTimeType;
use App\Entity\BusyAppointments;
use App\Form\AddTennisGroundType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TennisGroundRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class AdminController extends AbstractController
{


    public function __construct(private ParameterBagInterface $serviceParam)
    {
        
    }


    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $manager, Request $request): Response
    {
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        //dohvatamo difoltnu smenu i sve terene
        $shift = $manager->getRepository(WorkingTime::class)->findOneBy(['defaultTime' => true]);
        $grounds =$manager->getRepository(TennisGround::class)->findBy(['isDelete' => false]);

        $form1 = $this->createForm(WorkingTimeType::class, $shift);
        
        $form1->handleRequest($request);

        //promena radnog vremena
        if($form1->isSubmitted() && $form1->isValid())
        {
            /** @var WorkingTime $shift */
            $shift = $form1->getData();
            $start_time = $shift->getStartTime();
            $end_time = $shift->getEndTime();

            $deleteAppointment = $manager->getRepository(BusyAppointments::class)->findByOutOfRange($start_time,$end_time);
            
            foreach($deleteAppointment as $appointment)
            {
                $appointment->setDelete(true);
                $manager->persist($appointment);
            }
    
            $manager->flush();

            //dohvatanje svih termina koji su sada izvan granica smene

            $manager->getRepository(WorkingTime::class)->add($shift,true);
           
            return $this->redirectToRoute('app_admin');
        }

        //pravljenje forme za dodavanje teniskih terena
        $form2 = $this->createForm(AddTennisGroundType::class);

        $form2->handleRequest($request);
        //dodavanje teniskog terena
        if($form2->isSubmitted() && $form2->isValid())
        {
            $data = $form2->getData();

            $groundExist = $manager->getRepository(TennisGround::class)->findOneBy(['name' => $data['name']]);
            if(empty($groundExist))
            {
                //napravi instancu terena
                $newGround = new TennisGround();
                $newGround->setBlocked(false);
                $newGround->setName($data['name']);
                $newGround->setDelete(false);

                //sacuvaj u bazi
                $manager->persist($newGround);
                $manager->flush();

                //flash kartice
                $this->addFlash('addGround','You added new groud.');
            }
            else
            {
                if($groundExist->isDelete() == true)
                {
                    $groundExist->setDelete(false);
                    //sacuvaj u bazi
                    $manager->persist($groundExist);
                    $manager->flush();

                    //flash kartice
                    $this->addFlash('addGround','You added new groud.');
                }
                else
                {
                    $this->addFlash('alreadyExist', 'Ground with that name already exist');
                }

            }

            //redirekcija
            return $this->redirectToRoute('app_admin');

        }


        //dohvati sve usere
        $users = $manager->getRepository(User::class)->findAll();
        $adminEmail = $this->serviceParam->get('admin_mail');

        return $this->render('admin/index.html.twig', [
            'currentUser' => $currentUser,
            'timeForm' => $form1,
            'grounds' => $grounds,
            'addGroundForm' => $form2,
            'users' => $users,
            'adminEmail' => $adminEmail
        ]);
    }

    /** @param String idGround */
    #[Route('/admin/deleteGround/{idGround}', name:'app_delete_ground')]
    public function deleteGround($idGround, EntityManagerInterface $manager) :Response
    {
        $id = intval($idGround);
        
        //dohvati teren koji treba da obrisemo
        $ground = $manager->getRepository(TennisGround::class)->findOneBy(['id'=> $id]);
        $deleteAppointments = $manager->getRepository(BusyAppointments::class)->findBy(['ground' => $ground]);
        
        //podesiti da su termini na tom terenu obrisani, posle prvog prikaza korisniku svih obrisanih termina, brisu se iz baze
        foreach($deleteAppointments as $appointment)
        {
            $appointment->setDelete(true);
            $manager->persist($appointment);
        }

        $manager->flush();

        //obeleziti teren da je obrisan
        $ground->setDelete(true);
        $manager->persist($ground);
        $manager->flush();

        $this->addFlash('deleteGround', 'You are delete ground!');

        return $this->redirectToRoute('app_admin');
    }

    /** @param String idGround */
    #[Route('/admin/blockedGround/{idGround}', name:'app_blocked_ground')]
    public function BlockedGround($idGround, EntityManagerInterface $manager): Response
    {
        $id = intval($idGround);
        
        //dohvati teren koji treba da obrisemo
        $ground = $manager->getRepository(TennisGround::class)->findOneBy(['id'=> $id]);   
        if($ground->isBlocked())
        {
            $ground->setBlocked(false);
            $manager->persist($ground);
            $manager->flush();
            $this->addFlash('blockedGround', 'You are blocked ground!');
        }
        else
        {
            $ground->setBlocked(true);
            $manager->persist($ground);
            $manager->flush();
            $this->addFlash('unblockedGround', 'You are unblocked ground!');
        }


        return $this->redirectToRoute('app_admin');
    }

    /** @param String email */
    #[Route( 'admin/blockedUser/{email}' ,name : 'app_blocked_user')]
    public function blockedUser($email,EntityManagerInterface $manager) :Response
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email'=> $email]);
        
        if($user->isBlocked() == false)
        {
            $user->setBlocked(true);
            $manager->persist($user);
            $manager->flush($user);
            $this->addFlash('unblockedUser', 'You are unblocked user!');
        }
        else
        {
            $user->setBlocked(false);
            $manager->persist($user);
            $manager->flush($user);
            $this->addFlash('blockedUser', 'You are blocked user!');
        }


        return $this->redirectToRoute('app_admin');
    }

}
