<?php

namespace App\Controller;

use App\Entity\WorkingTime;
use App\Form\WorkingTimeType;
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

        $shift = $manager->getRepository(WorkingTime::class)->findOneBy(['defaultTime' => true]);

        $form = $this->createForm(WorkingTimeType::class, $shift);
        
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $shift = $form->getData();
            
            $manager->getRepository(WorkingTime::class)->add($shift,true);
           
            return $this->redirectToRoute('app_admin');
        }


        return $this->render('admin/index.html.twig', [
            'currentUser' => $currentUser,
            'timeForm' => $form
        ]);
    }
}
