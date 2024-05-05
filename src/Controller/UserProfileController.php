<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Form\LoginType;
use App\Form\UserProfileType;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserProfileController extends AbstractController
{

    #[Route('/user/profile', name: 'app_user_profile')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $request, UserRepository $menager): Response
    {

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        //reach profil, if not exists make one for current user
        $userProfile = $currentUser->getProfile() ?? new UserProfile(); 

        $form = $this->createForm(
            //tip forme i gde pakujemo sve potrebne podatke
            UserProfileType::class, $userProfile
        );

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $userProfile = $form->getData(); //move date in instance of class UserProfile

            //save profil
            //automatski se cuva i profil ovako
            $currentUser->setProfile($userProfile);
            // add flash card
            $menager->add($currentUser,true);
            $this->addFlash(
                'successChangeProfil',
                'Your user profile setting were saved.'
            );
            //redirect 
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user_profile/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
