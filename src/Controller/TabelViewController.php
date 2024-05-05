<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Entity\TennisGround;
use App\Entity\BusyAppointments;
use App\Entity\WorkingTime;
use App\Form\BookingGroundType;
use App\Form\SelectDateType;
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

    private function deleteMyAppointment(User $currentUser, EntityManagerInterface $menager)
    {
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
                  $message = 'Your appointment at ' . $sTime->format('H:i') . ' - ' . $eTime->format('H:i') . ' ' . $myappoint->getDate()->format('l, d.m.Y') . ' on ground '. $myappoint->getGround()->getName() . 'was disabled!';
                  
  
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
    }

    #[Route('/tabel_view', name: 'app_tabel_view')]
    public function index(EntityManagerInterface $menager, Request $request, BusyAppointmentsRepository $managerBooking): Response
    {
        //get admin email from service
        $adminEmail = $this->serviceParam->get('admin_mail');

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if($currentUser != null)
        {
            //brisanje mojih termina
            $this->deleteMyAppointment($currentUser,$menager);
        }

        //pratimo koji je datum selectovan sve vreme
        $temp = new DateTime();
        $selectDate = (string) $temp->format('l, d.m.Y');


        //dohvati termin koji ti treba i terene
        $grounds = $menager->getRepository(TennisGround::class)->findBy(['isDelete' => false]);

    
        $default_shift = $menager->getRepository(WorkingTime::class)->findOneBy(['defaultTime' => true]);
        $start_time = (int) $default_shift->getStartTime()->format('H');
        $end_time = (int) $default_shift->getEndTime()->format('H');


        //$formSelect = $this->selectForm();
        $formSelect = $this->createForm(SelectDateType::class);
        
        $formSelect->handleRequest($request);

        //pravimo formu za select element
        if($formSelect->isSubmitted() && $formSelect->isValid())
        {
            $date1 = $formSelect->getData();
            $viewDate = $date1['dateView'];
            return $this->redirectToRoute('app_tabel_view_sd', ['selectDate' => $date1['dateView']]);
        }

        //dohvati sve zakazane termine koji postoje za selektovan datum i prikazati ih
        $findDateString = date('Y-m-d',strtotime($selectDate));
        $findDate = new DateTime($findDateString);
        $appointments = $menager->getRepository(BusyAppointments::class)->findBy(['date' => $findDate, 'isDelete' => false]);
        $users = $menager->getRepository(User::class)->findAll();
        
        //make form for booking
        $formBooking = $this->createForm(BookingGroundType::class, null, [
        'selectDate' => $selectDate,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'grounds' => $grounds,
        'users' => $users,
        'currentUser' => $currentUser,
        'adminEmail' => $this->serviceParam->get('admin_mail')
        ]);

        $formBooking->handleRequest($request);

        //zakazivanje termina
        if($formBooking->isSubmitted() && $formBooking->isValid())
        {
            //redirect
            $date2 = $formBooking->getData();
            $startTime = $date2['hourlyRate'];
            $endTime = $date2['hourlyRateEnd'];

            //ako imamo dobro odabrano vreme
            if($startTime->format('H') >= $start_time && $endTime->format('H') <= $end_time 
            && $startTime->format('H') < $endTime->format('H'))
            {

                $date  = $date2['dateSelect'];
                $groundName = $date2['bookGround'];
                $userName = $date2['userName'];

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
                    
                    $this->addFlash('successBooking','Your appointment is booked');
                    return $this->redirectToRoute('app_tabel_view');
                }
                $messageTerminTaken = 'Termin is taken on ground ' . $ground->getName() . ' in your time range ( '. $startTime->format('H:i') . ' - '. $endTime->format('H:i') . ')!';
                $this->addFlash('bookingTermin', $messageTerminTaken);
                return $this->redirectToRoute('app_tabel_view');

            }
            $messageNoValid = "Time for termin is already taken or isn't in range of working hours (". $default_shift->getStartTime()->format('H:i') ." - ". $default_shift->getEndTime()->format('H:i') .")!";
            $this->addFlash('noValidForm', $messageNoValid);
            return $this->redirectToRoute('app_tabel_view');   
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

    /** @param String $selectDate */
    #[Route('/tabel_view/{selectDate}', name: 'app_tabel_view_sd')]
    public function indexSelectDate($selectDate, EntityManagerInterface $menager, Request $request, BusyAppointmentsRepository $managerBooking): Response
    {
        //get admin email from service
        $adminEmail = $this->serviceParam->get('admin_mail');

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        //brisanje mojih termina
        $this->deleteMyAppointment($currentUser,$menager);

        //pratimo koji je datum selectovan sve vreme
       /* if(empty($selectDate))
        {
            $temp = new DateTime();
            $selectDate = (string) $temp->format('l, d.m.Y'); 
        }*/

        //dohvati termin koji ti treba i terene
        $grounds = $menager->getRepository(TennisGround::class)->findBy(['isDelete' => false]);

 
        $default_shift = $menager->getRepository(WorkingTime::class)->findOneBy(['defaultTime' => true]);
        $start_time = (int) $default_shift->getStartTime()->format('H');
        $end_time = (int) $default_shift->getEndTime()->format('H');


        //$formSelect = $this->selectForm();
        $formSelect = $this->createForm(SelectDateType::class);
        
        $formSelect->handleRequest($request);

        //pravimo formu za select element
        if($formSelect->isSubmitted() && $formSelect->isValid())
        {
            $date1 = $formSelect->getData();
            $viewDate = $date1['dateView'];
            return $this->redirectToRoute('app_tabel_view_sd', ['selectDate' => $date1['dateView']]);
        }

        //dohvati sve zakazane termine koji postoje za selektovan datum i prikazati ih
        $findDateString = date('Y-m-d',strtotime($selectDate));
        $findDate = new DateTime($findDateString);
        $appointments = $menager->getRepository(BusyAppointments::class)->findBy(['date' => $findDate, 'isDelete' => false]);
        $users = $menager->getRepository(User::class)->findAll();
        
        //make form for booking
       $formBooking = $this->createForm(BookingGroundType::class, null, [
        'selectDate' => $selectDate,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'grounds' => $grounds,
        'users' => $users,
        'currentUser' => $currentUser,
        'adminEmail' => $this->serviceParam->get('admin_mail')
       ]);

        $formBooking->handleRequest($request);

        //zakazivanje termina
        if($formBooking->isSubmitted() && $formBooking->isValid())
        {
            //redirect
            $date2 = $formBooking->getData();
            $startTime = $date2['hourlyRate'];
            $endTime = $date2['hourlyRateEnd'];

            //ako imamo dobro odabrano vreme
            if($startTime->format('H') >= $start_time && $endTime->format('H') <= $end_time 
            && $startTime->format('H') < $endTime->format('H'))
            {

                $date  = $date2['dateSelect'];
                $groundName = $date2['bookGround'];
                $userName = $date2['userName'];

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
                    
                    $this->addFlash('successBooking','Your appointment is booked');
                    return $this->redirectToRoute('app_tabel_view_sd', ['selectDate' => $selectDate]);
                }
                else
                {
                    $messageTerminTaken = 'Termin is taken on ground ' . $ground->getName() . ' in your time range ( '. $startTime->format('H:i') . ' - '. $endTime->format('H:i') . ')!';
                    $this->addFlash('bookingTermin', $messageTerminTaken);
                    return $this->redirectToRoute('app_tabel_view_sd', ['selectDate' => $selectDate]);
                }
                
            }
            else
            { //nisu dobro podesena vremena 
                $messageNoValid = "Time for termin is already taken or isn't in range of working hours (". $default_shift->getStartTime()->format('H:i') ." - ". $default_shift->getEndTime()->format('H:i') .")!";
                $this->addFlash('noValidForm', $messageNoValid);
                return $this->redirectToRoute('app_tabel_view_sd', ['selectDate' => $selectDate]);
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

    #[Route('/deleteAppointment/{idAppointment}' ,name:'app_appointment_delete')]
    public function deleteThisAppointment($idAppointment, EntityManagerInterface $menager): Response
    {
        //brisanje termina
        $appointment = $menager->getRepository(BusyAppointments::class)->findOneBy(['id' => $idAppointment]);
        $appointment->setDelete(true);
        $menager->flush();
        //redirekcija
        return $this->redirectToRoute('app_tabel_view');
    }

}

