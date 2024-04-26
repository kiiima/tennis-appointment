<?php

namespace App\Controller;

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
    /** @param Array $grounds */
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
            $shift = $form1->getData();
            
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
}
