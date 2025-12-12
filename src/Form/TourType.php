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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];                 // can be null
        $lockEmail = (bool) $options['lock_email']; // user edit: true, admin: false

        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control'],
            ])

            // ✅ Email is mapped to entity, BUT we do NOT force 'data' here.
            // We'll prefill safely in PRE_SET_DATA only when empty.
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'disabled' => $lockEmail, // ✅ user cannot edit
                'attr' => ['class' => 'form-control'],
            ])

            ->add('phoneNumber', TelType::class, [
                'label' => 'Phone',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('numberOfGuests', IntegerType::class, [
                'label' => 'Guests',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date & time',
                'required' => true,
                'attr' => ['class' => 'form-control'],
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
                'attr' => ['class' => 'form-control'],
            ])
            ->add('requestedExhibition', TextType::class, [
                'label' => 'Or type what they want (optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g., Impressionist paintings, Modern sculpture, Student group tour...',
                    'class' => 'form-control',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 4, 'class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Cancelled' => 'cancelled',
                ],
                'disabled' => true,     // user/admin cannot edit here; admin uses update-status route
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ]);

        // ✅ Safely prefill email ONLY if Tour.email is currently empty AND user exists
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
            $tour = $event->getData();
            $form = $event->getForm();

            if (!$tour instanceof Tour) return;
            if (!$user) return;

            if (!$tour->getEmail()) {
                $form->get('email')->setData($user->getUserIdentifier());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tour::class,
            'user' => null,
            'lock_email' => false, // ✅ default admin behavior
        ]);
    }
}
