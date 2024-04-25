<?php

namespace App\Form;

use DateTime;
use App\Entity\WorkingTime;
use Doctrine\DBAL\Types\DateTimeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class WorkingTimeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startTime', TimeType::class, [
                'widget' => 'choice',
                'input' => 'datetime',
                'input_format' => 'H',
                'with_minutes' => false
            ])
            ->add('endTime', TimeType::class, [
                'widget' => 'choice',
                'input' => 'datetime',
                'input_format' => 'H',
                'with_minutes' => false
            ])
            //->add('description')
            //->add('defaultTime')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkingTime::class,
        ]);
    }
}
