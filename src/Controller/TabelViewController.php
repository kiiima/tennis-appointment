<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Form\ButtonType;
use App\Entity\TennisGround;
use App\Entity\BusyAppointments;
use App\Repository\BusyAppointmentsRepository;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TabelViewController extends AbstractController
{

    /** @var unset $viewDate */
    private $viewDate;

    public function __construct(private ParameterBagInterface $serviceParam)
    {
        
    }

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

    private function bookingForm(): Form
    {
        $form = $this->createFormBuilder()
            ->add('hourlyRate', TextType::class)
            ->add('bookGround', TextType::class)
            ->add('username', TextType::class)
            ->add('dateSelect', TextType::class)
            ->getForm();

        return $form;
    }


    /** @param String $selectDate */
    #[Route('/tabel_view/{selectDate}', name: 'app_tabel_view', defaults:[ 'selectDate' => null ])]
    public function index($selectDate, EntityManagerInterface $menager, Request $request, BusyAppointmentsRepository $managerBooking): Response
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
        if($formSelect->isSubmitted() && $formSelect->isValid())
        {
            $date1 = $formSelect->getData();
            $viewDate = $date1['dateView'];
            return $this->redirectToRoute('app_tabel_view', ['selectDate' => $date1['dateView']]);
        }

        //dohvati sve zakazane termine koji postoje za selektovan datum i prikazati ih
        $findDateString = date('Y-m-d',strtotime($selectDate));
        $findDate = new DateTime($findDateString);
        $appointments = $menager->getRepository(BusyAppointments::class)->findBy(['date' => $findDate]);

        //get admin email from service
        $adminEmail = $this->serviceParam->get('admin_mail');
        
        //make form for booking
        $formBooking = $this->bookingForm();
       // $formBooking->get('dateSelect')->setData($selectDate);

        $formBooking->handleRequest($request);

        if($formBooking->isSubmitted() && $formBooking->isValid())
        {
            //redirect
            $date2 = $formBooking->getData();
            $groundName = $date2['bookGround'];
            $userName = $date2['username'];
            

            $ground = $menager->getRepository(TennisGround::class)->findOneBy(['name'=> $groundName]);
            $user = $menager->getRepository(User::class)->findOneBy(['email'=>$userName]);


            $appointment = new BusyAppointments($user, $ground);
            $appointment->setTime(intval($date2['hourlyRate']));
            $format = 'l, d.m.Y';
            $date = DateTime::createFromFormat($format,$date2['dateSelect']);
            $appointment->setDate($date);


            $managerBooking->add($appointment,true);
            $this->addFlash('success','Your appointment is booked');
            return $this->redirectToRoute('app_tabel_view', ['selectDate' => $selectDate]);
        }

        //prikaz forme
        return $this->render('tabel_view/index.html.twig', [
            'grounds' => $grounds,
            'start_time' => start_time,
            'end_time' => end_time,
            'today' => new DateTime(),
            'currUser' => $currentUser,
            'formSelect' => $formSelect->createView(),
            'date' => $selectDate,
            'appointments' => $appointments,
            'adminEmail' => $adminEmail,
            'formBooking' => $formBooking->createView(),
            'selectDate' => $selectDate
        ]);
    }

}
