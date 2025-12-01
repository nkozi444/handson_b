<?php

namespace App\Form;

use App\Entity\Exhibition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ExhibitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Exhibition Title',
                'attr' => ['class' => 'input-text'],
            ])

            ->add('type', TextType::class, [
                'label' => 'Type (optional)',
                'required' => false,
                'attr' => ['class' => 'input-text'],
            ])

            ->add('period', TextType::class, [
                'label' => 'Period (optional)',
                'required' => false,
                'attr' => ['class' => 'input-text'],
            ])

            ->add('artists', TextType::class, [
                'label' => 'Artist(s) (optional)',
                'required' => false,
                'attr' => ['class' => 'input-text'],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description (optional)',
                'required' => false,
                'attr' => ['rows' => 6, 'class' => 'input-textarea'],
            ])

            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'input-text'],
            ])

            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'input-text'],
            ])

            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-label'],
            ])

            // IMAGE UPLOAD (NON-MAPPED)
            ->add('imageFile', FileType::class, [
                'label' => 'Exhibition Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '6M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'input-file'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exhibition::class,
        ]);
    }
}
