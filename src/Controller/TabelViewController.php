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
                'mapped' => false,
                'constraints' =>[
                    new NotBlank([
                        'message' => 'Please enter your full name!'
                    ])
                ]
            ])
            ->add('phone', TelType::class,[
                'mapped' => false,
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
        $grounds = $menager->getRepository(TennisGround::class)->findAll();

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

        if($formBooking->isSubmitted() && $formBooking->isValid())
        {
            //redirect
            $date2 = $formBooking->getData();
            $groundName = $date2['bookGround'];
            $userName = $date2['userName'];
            $time = $date2['hourlyRate'];
            $date  = $date2['dateSelect'];

            //provera da li je taj termin vec rezervisan
            $format = 'l, d.m.Y';
            $ground = $menager->getRepository(TennisGround::class)->findOneBy(['name'=> $groundName]);
            $sameTermin = $menager->getRepository(BusyAppointments::class)->findBy(['time' => intval($time) , 'ground' => $ground, 'date' => DateTime::createFromFormat($format, $date) ]);
            
            if(empty($sameTermin))
            {//mozemo da zakazemo termin
                $user = $menager->getRepository(User::class)->findOneBy(['email'=>$userName]);


                $appointment = new BusyAppointments($user, $ground);
                //$appointment->setTime(intval($date2['hourlyRate']));
                $format = 'l, d.m.Y';
                $date = DateTime::createFromFormat($format,$date2['dateSelect']);
                $appointment->setDate($date);
    
    
                $managerBooking->add($appointment,true);
                $this->addFlash('success','Your appointment is booked');
            }
            else
            {
                $this->addFlash('bookingTermin', 'Termin is already taken');
            }
            
            return $this->redirectToRoute('app_tabel_view', ['selectDate' => $selectDate]);
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

}

/*
'userName', TextType::class, 
                'constraints' => 
                    new NotNul([
                        'message' => 'Please enter username!'
                    ]
                
            
*/