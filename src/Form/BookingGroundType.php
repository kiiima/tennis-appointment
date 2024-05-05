<?php

namespace App\Form;

use DateTime;
use PHPUnit\Framework\Constraint\GreaterThan;
use PHPUnit\Framework\Constraint\LessThan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

class BookingGroundType extends AbstractType
{

    /** @param User $user */
    public static function getUserUsername($user)
    {
        return $user->getEmail();
    }

    
    /** @param TennisGround $value */
    private static function getTennisGroundName($value)
    {
        return $value->getName();
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $today = new DateTime();
        $dates = [];

        for($i = 0; $i < 7; $i++)
        {
            $dates[$i] = clone $today;
            $dates[$i] = $dates[$i]->modify("+$i day")->format('l, d.m.Y');
        }


        $builder
            ->add('hourlyRate', TimeType::class, [
                'widget' => 'choice',
                'input' => 'datetime',
                'input_format' => 'H',
                'with_minutes' => false
            ])
            ->add('hourlyRateEnd', TimeType::class, [
                'widget' => 'choice',
                'input' => 'datetime',
                'input_format' => 'H',
                'with_minutes' => false
            ])
            ->add('bookGround', ChoiceType::class, [
                'choices' => array_map([BookingGroundType::class, 'getTennisGroundName'] ,$options['grounds']),
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
                'preferred_choices' => [ $options['selectDate'] ]
            ])
            ->add('fullName', TextType::class,[
                'constraints' =>[
                    new NotBlank([
                        'message' => 'Please enter your full name!'
                    ])
                ]
            ])
            ->add('phone', TextType::class,[
                'constraints' =>[
                    new NotBlank([
                        'message' => 'Please enter your phone!'
                    ]),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Phone must have minimum 3 number!',
                        'max' => 10,
                        'maxMessage' => 'Phone must have maximum 10 number!'
                    ]),
                    new Type([
                        'type' => 'integer',
                        'message' => 'Phone must consist of numbers only!'
                    ])
                ]
            ]);

            
            if(isset($options['currentUser']) && $options['currentUser']->getEmail() == $options['adminEmail'])
            {//ako je admin imamo choicetype za username
                $builder->add('userName', ChoiceType::class, [
                    'choices' => array_map([BookingGroundType::class, 'getUserUsername'],$options['users']),
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
                $builder->add('userName',TextType::class,[
                    'attr' => [
                        'readonly' => true
                    ]
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'selectDate' => null,
            'start_time' => null,
            'end_time' => null, 
            'grounds' => [],
            'users' => [],
            'currentUser' => null,
            'adminEmail' => null
        ]);
    }
}
