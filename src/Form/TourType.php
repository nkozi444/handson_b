<?php
// src/Form/TourType.php
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
        $builder
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('phoneNumber', TelType::class, ['label' => 'Phone', 'required' => false])
            ->add('numberOfGuests', IntegerType::class, ['label' => 'Guests'])

            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date & time',
                'required' => true, // matches your #[Assert\NotBlank]
            ])

            // ✅ Exhibition dropdown
            ->add('exhibition', EntityType::class, [
                'class' => Exhibition::class,
                'label' => 'Select an exhibition (optional)',
                'required' => false,
                'placeholder' => '— No preference —',

                // Since you have __toString(), this is optional, but explicit is fine:
                'choice_label' => 'title',

                // ✅ sort: Active first, then Title
                'query_builder' => function (ExhibitionRepository $er) {
                    return $er->createQueryBuilder('e')
                        ->orderBy('e.isActive', 'DESC')
                        ->addOrderBy('e.title', 'ASC');
                },

                // Optional: disable inactive exhibitions so they can’t be selected
                'choice_attr' => function (?Exhibition $e) {
                    if (!$e) return [];
                    return $e->isActive()
                        ? []
                        : ['disabled' => 'disabled', 'class' => 'opacity-50'];
                },
            ])

            // ✅ Free text request if not listed
            ->add('requestedExhibition', TextType::class, [
                'label' => 'Or type what they want (optional)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g., Impressionist paintings, Modern sculpture, Student group tour...',
                ],
            ])

            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 4],
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
