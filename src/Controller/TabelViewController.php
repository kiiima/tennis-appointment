<?php

namespace App\Controller;

use App\Entity\BusyAppointments;
use DateTime;
use App\Entity\TennisGround;
use App\Form\ButtonType;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TabelViewController extends AbstractController
{

    /** @var unset $viewDate */
    private $viewDate;

    private function selectForm(): Form
    {
        $today = new DateTime();
        $dates = [];

        for($i = 0; $i < 7; $i++)
        {
            $dates[$i] = clone $today;
            $dates[$i] = $dates[$i]->modify("+$i day")->format('l, d.m.Y');
        }


        $form = $this->createFormBuilder()
            ->add('dateView', ChoiceType::class, [
                'choices' => array_combine(range(0,6),$dates),
                'choice_label' => function($value, $key, $index)
                {
                    return $value;
                },
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'hover:bg-gray-100'];
                },
                'preferred_choices' => [ reset($dates) ]
            ])
            ->getForm();

        return $form;
    }

    /** @param String $selectDate */
    #[Route('/tabel_view/{selectDate}', name: 'app_tabel_view', defaults:[ 'selectDate' => null ])]
    public function index($selectDate, EntityManagerInterface $menager, Request $request): Response
    {

        //pratimo koji je datum selectovan sve vreme
        if(empty($selectDate))
        {
            $temp = new DateTime();
            $selectDate = $temp->format('l, d.m.Y');
        }

        $grounds = $menager->getRepository(TennisGround::class)->findAll();
        define('start_time', 8);
        define('end_time',21);

        $currentUser = $this->getUser();

        $formSelect = $this->selectForm();
        
        $formSelect->handleRequest($request);

        //pravimo formu za select element
        if($formSelect->isSubmitted())
        {
            $date1 = $formSelect->getData();
            $viewDate = $date1['dateView'];
            return $this->redirectToRoute('app_tabel_view', ['selectDate' => $date1['dateView']]);
        }

        //dohvati sve zakazane termine koji postoje za selektovan datum i prikazati ih
        $findDateString = date('Y-m-d',strtotime($selectDate));
        $findDate = new DateTime($findDateString);
        $appointments = $menager->getRepository(BusyAppointments::class)->findBy(['date' => $findDate]);

        //prikaz forme
        return $this->render('tabel_view/index.html.twig', [
            'grounds' => $grounds,
            'start_time' => start_time,
            'end_time' => end_time,
            'today' => new DateTime(),
            'currUser' => $currentUser,
            'formSelect' => $formSelect,
            'date' => $selectDate,
            'appointments' => $appointments
        ]);
    }

   
    /* @param Integer $time 
     @param Integer $ground 
    #[Route('/tabel_view/booking/{time}/{ground}', name:'app_booking')]
    public function bookingAppointment($time, $ground, Request $request):Response
    {

        if(empty($viewDate))
        {
            $tmp = new DateTime();
            $viewDate = $tmp->format('l, d.m.Y');
        }

        dd($time . $ground);
        $today = new DateTime();

        $form = $this->createFormBuilder()
        ->add('time', TextType::class, [
            'data' => $time ?? '',
            'disabled' => true
        ])
        ->add('ground', TextType::class, [
            'data' => $ground ?? '',
            'disabled' => true
        ])
        ->add('user', TextType::class, [
            'data' => $this->getUser() ?? '',
            'disabled' => true
        ])
        ->add('date', TextType::class, [
            'data' => $viewDate,
            'disabled' => true
        ])
        ->getForm();

        if($form->isSubmitted())
        {
            $datas = $form->getData();
           // dd($datas['time'] . $datas['ground']);
            return $this->redirectToRoute('');
        }

        return $this->render('tabel_view/booking.html.twig',[
            'form' => $form
        ]);

    }
*/

    /*#[Route('/button_fuction', name:'app_button_function')]
    public function bookAppointment(Request $request, EntityManagerInterface $menager):Response
    {   

        $time = $request->request->get('time');
        $ground = $request->request->get('ground') - 1;
        //dd($request->request->get('date'));

          @var BusyAppointments $appointment 
        $appointment = new BusyAppointments();
        $appointment->addUser($this->getUser());

        $groundName = 'ground' . $ground;
        $date = $this->viewDate ?? new DateTime();
        dd($date->format('d.m.Y') . $groundName . $time);

        //$menager->getRepository(TennisGround::class)->findOneBy('');
    

        return $this->render('/tabel_view/book_button.html.twig',[]);
    }*/


}
