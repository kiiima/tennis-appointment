<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\TennisGround;
use App\Entity\BusyAppointments;
use App\Entity\WorkingTime;
use App\Repository\BusyAppointmentsRepository;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TabelViewController extends AbstractController
{

    /** @var unset $viewDate */
    private $viewDate;

    public function __construct(private ParameterBagInterface $serviceParam)
    {
        
    }

    /** @param User $user */
    public static function getUserUsername($user)
    {
        return $user->getEmail();
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

    /** @param TennisGround $value */
    private static function getTennisGroundName($value)
    {
        return $value->getName();
    }
    
    
    private function bookingForm($selectDate, $start_time, $end_time, $grounds, $users, $currentUser): Form
    {
        $nameGrounds = array_column($grounds,'name');

        $today = new DateTime();
        $dates = [];

        for($i = 0; $i < 7; $i++)
        {
            $dates[$i] = clone $today;
            $dates[$i] = $dates[$i]->modify("+$i day")->format('l, d.m.Y');
        }


        $form = $this->createFormBuilder()
            ->add('hourlyRate', ChoiceType::class, [
                'choices' => array_combine(range($start_time,$end_time,1),range($start_time,$end_time,1)),
                'choice_label' => function($value, $key, $index)
                {
                    return $value;
                },
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'hover:bg-gray-100'];
                }
            ])
            ->add('hourlyRateEnd', ChoiceType::class, [
                'choices' => array_combine(range($start_time,$end_time,1),range($start_time,$end_time,1)),
                'choice_label' => function($value, $key, $index)
                {
                    return $value;
                },
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'hover:bg-gray-100'];
                }
            ])
            ->add('bookGround', ChoiceType::class, [
                'choices' => array_map([TabelViewController::class, 'getTennisGroundName'] ,$grounds),
                'choice_label' => function($value, $key, $index)
                {
                    return $value;
                },
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'hover:bg-gray-100'];
                }
            ])
            ->add('dateSelect', ChoiceType::class, [
                'choices' => array_combine(range(0,6),$dates),
                'choice_label' => function($value, $key, $index)
                {
                    return $value;
                },
                'choice_attr' => function($choice, $key, $value) {
                    return ['class' => 'hover:bg-gray-100'];
                },
                'preferred_choices' => [ $selectDate ]
            ])
            ->add('fullName', TextType::class, [
                'constraints' =>[
                    new NotBlank([
                        'message' => 'Please enter your full name!'
                    ])
                ]
            ])
            ->add('phone', TelType::class,[
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your full name!'
                    ]),
                    new Length([
                        'min' => 8,
                        'max' => 15
                    ])
                ]
            ]);

            if(isset($currentUser) && $currentUser->getEmail() == $this->serviceParam->get('admin_mail'))
            {//ako je admin imamo choicetype za username
                $form->add('userName', ChoiceType::class, [
                    'choices' => array_map([TabelViewController::class, 'getUserUsername'],$users),
                    'choice_label' => function($value, $key, $index)
                    {
                        return $value;
                    },
                    'choice_attr' => function($choice, $key, $value) {
                        return ['class' => 'hover:bg-gray-100'];
                    }
                ]);
            }
            else
            {//ako je user imamo texttype sa readonly class
                $form->add('userName',TextType::class,[
                    'attr' => [
                        'readonly' => true
                    ]
                ]);
            }


        return $form->getForm();
    }


    /** @param String $selectDate */
    #[Route('/tabel_view/{selectDate}', name: 'app_tabel_view', defaults:[ 'selectDate' => null ])]
    public function index($selectDate, EntityManagerInterface $menager, Request $request, BusyAppointmentsRepository $managerBooking): Response
    {
        //get admin email from service
        $adminEmail = $this->serviceParam->get('admin_mail');

        /** @var User $currentUser */
        $currentUser = $this->getUser();


        //pratimo koji je datum selectovan sve vreme
        if(empty($selectDate))
        {
            $temp = new DateTime();
            $selectDate = $temp->format('l, d.m.Y');
        }

        //dohvati termin koji ti treba i terene
        $grounds = $menager->getRepository(TennisGround::class)->findBy(['isDelete' => false]);

        //define('start_time', 8);
        //define('end_time',21);
        $default_shift = $menager->getRepository(WorkingTime::class)->findOneBy(['defaultTime' => true]);
        $start_time = (int) $default_shift->getStartTime()->format('H');
        $end_time = (int) $default_shift->getEndTime()->format('H');


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
        $appointments = $menager->getRepository(BusyAppointments::class)->findBy(['date' => $findDate, 'isDelete' => false]);
        $users = $menager->getRepository(User::class)->findAll();
        
        //make form for booking
        $formBooking = $this->bookingForm($selectDate, $start_time, $end_time,$grounds,$users,$currentUser);
       // $formBooking->get('dateSelect')->setData($selectDate);

        $formBooking->handleRequest($request);

        //zakazivanje termina
        if($formBooking->isSubmitted() && $formBooking->isValid())
        {
            //redirect
            $date2 = $formBooking->getData();
            $groundName = $date2['bookGround'];
            $userName = $date2['userName'];
            $startTime = new DateTime();
            $intStartTime = intval($date2['hourlyRate']);
            $startTime->setTime($intStartTime,0,0);
            $endTime = new DateTime();
            $intEndTime = intval($date2['hourlyRateEnd']);
            $endTime->setTime($intEndTime,0,0);
            $date  = $date2['dateSelect'];


            //provera da li je taj termin vec rezervisan
            $format = 'l, d.m.Y';
            $fullDate = DateTime::createFromFormat($format,$date);
            $ground = $menager->getRepository(TennisGround::class)->findOneBy(['name'=> $groundName]);
            
            $sameTermin = $menager->getRepository(BusyAppointments::class)->findBy(['ground' => $ground, 'date' => DateTime::createFromFormat($format, $date) ]);
            //$sameTermin = $menager->getRepository(BusyAppointments::class)->findByInOfRange($startTime, $endTime, $fullDate, $ground);
            $isExistTermin = false;

            foreach($sameTermin as $termin)
            {
                if(($startTime->format('H') <= $termin->getStartTime()->format('H') ||($startTime->format('H') == $termin->getStartTime()->format('H') && $startTime->format('M') <= $termin->getStartTime()->format('M'))) &&  
                ($endTime->format('H') >= $termin->getEndTime()->format('H') ||($endTime->format('H') == $termin->getEndTime()->format('H') && $endTime->format('M') <= $termin->getEndTime()->format('M'))))
                {
                    $isExistTermin = true;
                }
            }

            if($isExistTermin == false)
            {//mozemo da zakazemo termin
                $howManyTermin = (($endTime->format('H') * 60 + $endTime->format('i')) - ($startTime->format('H') * 60 + $startTime->format('i'))) / 60;

                $user = $menager->getRepository(User::class)->findOneBy(['email'=>$userName]);
                $format = 'l, d.m.Y';
                $date = DateTime::createFromFormat($format,$date2['dateSelect']);

                for($i = 0; $i < $howManyTermin; $i++ )
                {
                    $appointment = new BusyAppointments($user, $ground);
                    $appointment->setDate($date);
                    $appointment->setStartTime($startTime);
                    $appointment->setEndTime($endTime);
                    $appointment->setFullName($date2['fullName']);
                    $appointment->setPhone($date2['phone']);
        
        
                    $managerBooking->add($appointment,true);
                    $startTime->modify('+1 hour');
                    $endTime->modify('+1 hour');
                }
                $this->addFlash('success','Your appointment is booked');
            }
            else
            {
                $this->addFlash('bookingTermin', 'Termin is already taken');
            }
            
            return $this->redirectToRoute('app_tabel_view', ['selectDate' => $selectDate]);
        }

        //poruke za obrisane termine
        $myAppointment = $menager->getRepository(BusyAppointments::class)->findBy(['user' => $currentUser, 'isDelete' => true]);
        foreach($myAppointment as $myappoint)
        {
            $today = new DateTime(); 
            $today->setTime(0,0,0);
            $sTime = $myappoint->getStartTime();
            $eTime = $myappoint->getEndTime();
            $date = $myappoint->getDate();
            $date->setTime(0,0,0);
            if($today <= $date)
            {
                $message = 'Your appointment at ' . $sTime->format('H:i') . ' - ' . $eTime->format('H:i') . ' ' . $myappoint->getDate()->format('l, d.m.Y') . ' on ground '. $myappoint->getGround()->getName() . 'is disabled';
                

                $this->addFlash('deleteMyAppointment', $message);
            }

            $menager->remove($myappoint);
            $menager->flush();

            $thisGround = $myappoint->getGround();
            if($thisGround->isDelete() == true)
            {
                $restAppointment = $menager->getRepository(BusyAppointments::class)->findBy(['ground' => $thisGround]);
                if(empty($restAppointment))
                {
                    $menager->remove($thisGround);
                    $menager->flush();
                }
            }
        }

        //prikaz forme
        return $this->render('tabel_view/index.html.twig', [
            'grounds' => $grounds,
            'start_time' => $start_time,
            'end_time' => $end_time,
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


    #[Route('/bookingMouseOver', name:'booking_mouse_over')]
    public function bookingMouseOver(Request $request)
    {
        $data = json_decode($request->getContent(), true);
    
        // Pristupanje podacima
        $startTime = $data['startTime'];
        $groundName = $data['groundName'];
        $userEmail = $data['userEmail'];
        $selectDate = $data['selectDate'];

        dd($selectDate);
    }


   /* #[Route('/bookingMouseOver', name:'booking_mouse_over')]
    public function bookingMouseOver(SessionInterface $session)
    {
        $startTime = $session->get('startTime');
        //var_dump($startTime);

        
    }*/
}

