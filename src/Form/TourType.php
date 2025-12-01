<?php
// src/Form/TourType.php
namespace App\Form;

use App\Entity\Tour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
          ->add('name', TextType::class, ['label' => 'Name'])
          ->add('email', EmailType::class, ['label' => 'Email'])
          ->add('phoneNumber', TelType::class, ['label' => 'Phone'])
          ->add('numberOfGuests', IntegerType::class, ['label' => 'Guests'])
          ->add('date', DateTimeType::class, [
              'widget' => 'single_text',
              'label' => 'Date & time',
              'required' => false,
          ])
          ->add('notes', TextareaType::class, [
              'label' => 'Notes', 'required' => false, 'attr' => ['rows' => 4]
          ])
          ->add('status', ChoiceType::class, [
              'label' => 'Status',
              'choices' => [
                  'Pending' => 'pending',
                  'Confirmed' => 'confirmed',
                  'Cancelled' => 'cancelled',
              ],
              'preferred_choices' => ['pending'],
          ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tour::class,
        ]);
    }
}
