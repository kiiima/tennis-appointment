<?php

namespace App\Controller;

use App\Entity\BusyAppointments;
use App\Entity\TennisGround;
use App\Entity\WorkingTime;
use App\Form\AddTennisGroundType;
use App\Form\WorkingTimeType;
use App\Repository\TennisGroundRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $manager, Request $request): Response
    {
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        //dohvatamo difoltnu smenu i sve terene
        $shift = $manager->getRepository(WorkingTime::class)->findOneBy(['defaultTime' => true]);
        $grounds =$manager->getRepository(TennisGround::class)->findAll();

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
            //napravi instancu terena
            $newGround = new TennisGround();
            $newGround->setBlocked(false);
            $newGround->setName($data['name']);
            //sacuvaj u bazi
            $manager->persist($newGround);
            $manager->flush();

            //flash kartice
            $this->addFlash('addGround','You added new groud.');
            //redirekcija
            return $this->redirectToRoute('app_admin');

        }

        return $this->render('admin/index.html.twig', [
            'currentUser' => $currentUser,
            'timeForm' => $form1,
            'grounds' => $grounds,
            'addGroundForm' => $form2
        ]);
    }

    /** @param String idGround */
    #[Route('/admin/deleteGround/{idGround}', name:'app_delete_ground')]
    public function deleteGround($idGround, EntityManagerInterface $manager) :void 
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

        //brisanje terena iz baze
        $manager->remove($ground);
        $manager->flush();


        
    }
}
