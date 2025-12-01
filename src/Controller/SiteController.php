<?php

// src/Controller/SiteController.php

namespace App\Controller;

use App\Entity\Tour;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Exhibition;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\ExhibitionRepository;



class SiteController extends AbstractController
{
	/**
	 * Book a tour (show form, validate, persist Tour)
	 */
	#[Route('/book-a-tour', name: 'app_book_a_tour', methods: ['GET','POST'])]
	public function bookATour(Request $request, EntityManagerInterface $em): Response
	{
		$tour = new Tour();

		$form = $this->createFormBuilder($tour)
			->add('name', TextType::class, [
				'label' => 'Full name',
				'attr' => ['placeholder' => 'Jane Doe'],
			])
			->add('email', EmailType::class, [
				'label' => 'Email',
				'attr' => ['placeholder' => 'you@example.com'],
			])
			->add('phoneNumber', TelType::class, [
				'label' => 'Phone number',
				'required' => false,
				'attr' => ['placeholder' => '+1 555 555 5555'],
			])
			->add('numberOfGuests', IntegerType::class, [
				'label' => 'Guests',
				'attr' => ['min' => 1, 'placeholder' => '2'],
			])
			->add('date', DateTimeType::class, [
				'label' => 'Preferred date & time',
				'widget' => 'single_text',
				'html5' => true,
				'required' => false,
				'attr' => ['placeholder' => 'YYYY-MM-DD HH:MM'],
			])
                ->add('exhibition', EntityType::class, [
        'class' => Exhibition::class,
        'choice_label' => function (Exhibition $e) {
            // helpful label with title and type
            return $e->getTitle() . ($e->getType() ? ' — ' . $e->getType() : '');
        },
        'placeholder' => 'Select an exhibition (optional)',
        'required' => false,
    ])

			->add('notes', TextareaType::class, [
				'label' => 'Concerns / Questions',
				'required' => false,
				'attr' => ['rows' => 4, 'placeholder' => 'Anything we should know?'],
			])
			->add('save', SubmitType::class, ['label' => 'Book Tour'])
			->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$em->persist($tour);
			$em->flush();

			$this->addFlash('success', 'Thanks — your tour request was received. We will contact you soon!');

			return $this->redirectToRoute('app_book_a_tour');
		}

		return $this->render('site/book_a_tour.html.twig', [
			'tourForm' => $form->createView(),
		]);
	}

	/**
	 * About page
	 */
	#[Route('/about', name: 'app_about', methods: ['GET'])]
	public function about(): Response
	{
		return $this->render('site/about.html.twig');
	}

	/**
	 * Contact page (simple DB insert using DBAL connection)
	 */
    
	#[Route('/contact', name: 'app_contact', methods: ['GET','POST'])]
	public function contact(Request $request, Connection $db): Response
	{
		if ($request->isMethod('POST')) {
			$name = trim((string) $request->request->get('name', ''));
			$email = trim((string) $request->request->get('email', ''));
			$message = trim((string) $request->request->get('message', ''));

			if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $message !== '') {
				$db->insert('contact_messages', [
					'name' => $name,
					'email' => $email,
					'message' => $message,
					'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
				]);

				$this->addFlash('success', 'Message sent.');

				return $this->redirectToRoute('app_contact');
			}

			$this->addFlash('error', 'All fields required. Use a valid email.');
		}

		return $this->render('site/contact.html.twig');
	}
}