<?php

namespace App\Form;

use App\Entity\Exhibition;
use App\Entity\Tour;
use App\Repository\ExhibitionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
        $user = $options['user'];  // Pass user to the form from the controller

        // If user is null, set email field to empty or handle it gracefully
        $email = $user ? $user->getUserIdentifier() : ''; // use getUserIdentifier() for newer Symfony versions

        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'data' => $email, // Pre-fill the email field
                'attr' => ['class' => 'form-control']
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Phone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('numberOfGuests', IntegerType::class, [
                'label' => 'Guests',
                'attr' => ['class' => 'form-control']
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date & time',
                'required' => true, // matches your #[Assert\NotBlank]
                'attr' => ['class' => 'form-control']
            ])
            ->add('exhibition', EntityType::class, [
                'class' => Exhibition::class,
                'label' => 'Select an exhibition (optional)',
                'required' => false,
                'placeholder' => '— No preference —',
                'choice_label' => 'title',
                'query_builder' => function (ExhibitionRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.isActive', 'DESC')
                        ->addOrderBy('e.title', 'ASC');
                },
                'choice_attr' => function (?Exhibition $e) {
                    if (!$e) return [];
                    return $e->isActive() ? [] : ['disabled' => 'disabled', 'class' => 'opacity-50'];
                },
                'attr' => ['class' => 'form-control']
            ])
            ->add('requestedExhibition', TextType::class, [
                'label' => 'Or type what they want (optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g., Impressionist paintings, Modern sculpture, Student group tour...',
                    'class' => 'form-control'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-control']
            ])
            // Automatically set status to 'pending' for user-submitted tours
            // User cannot modify it
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed', // This would be handled by admins
                    'Cancelled' => 'cancelled', // This would be handled by admins
                ],
                'data' => 'pending', // Default value for users
                'disabled' => true, // Make the field read-only for users
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tour::class,
            'user' => null, // Add user to the form options to pre-fill the email
        ]);
    }
}
