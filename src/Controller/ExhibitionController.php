<?php

namespace App\Controller;

use App\Entity\Exhibition;
use App\Form\ExhibitionType;
use App\Repository\ExhibitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ActivityLogger; // Import ActivityLogger service

#[Route('/exhibition')]
class ExhibitionController extends AbstractController
{
    private $activityLogger;

    // Inject the ActivityLogger service
    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    #[Route('/', name: 'app_exhibition_index', methods: ['GET'])]
    public function index(ExhibitionRepository $repo): Response
    {
        return $this->render('exhibition/index.html.twig', [
            'exhibitions' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_exhibition_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $exhibition = new Exhibition();
        $form = $this->createForm(ExhibitionType::class, $exhibition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $uploadDir = $this->getParameter('exhibitions_directory');
                $fileName = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($uploadDir, $fileName);
                } catch (FileException $e) {
                    dd($e);
                }

                $exhibition->setImagePath($fileName);
            }

            $em->persist($exhibition);
            $em->flush();

            // Log the creation of the new exhibition (using getTitle instead of getName)
            $this->activityLogger->log(
                'CREATE',
                'Exhibition#' . $exhibition->getId() . ' (' . $exhibition->getTitle() . ') created.',
                $this->getUser()
            );

            return $this->redirectToRoute('app_exhibition_index');
        }

        return $this->render('exhibition/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_exhibition_show', methods: ['GET'])]
    public function show(Exhibition $exhibition): Response
    {
        return $this->render('exhibition/show.html.twig', [
            'exhibition' => $exhibition,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_exhibition_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exhibition $exhibition, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ExhibitionType::class, $exhibition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $uploadDir = $this->getParameter('exhibitions_directory');
                $fileName = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($uploadDir, $fileName);
                } catch (FileException $e) {
                    dd($e);
                }

                $exhibition->setImagePath($fileName);
            }

            $em->flush();

            // Log the update of the exhibition (using getTitle instead of getName)
            $this->activityLogger->log(
                'UPDATE',
                'Exhibition#' . $exhibition->getId() . ' (' . $exhibition->getTitle() . ') updated.',
                $this->getUser()
            );

            return $this->redirectToRoute('app_exhibition_index');
        }

        return $this->render('exhibition/edit.html.twig', [
            'form' => $form->createView(),
            'exhibition' => $exhibition,
        ]);
    }

    #[Route('/{id}', name: 'app_exhibition_delete', methods: ['POST'])]
    public function delete(Request $request, Exhibition $exhibition, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exhibition->getId(), $request->request->get('_token'))) {

            // OPTIONAL: Delete image file from disk if exists
            if ($exhibition->getImagePath()) {
                $imageFile = $this->getParameter('exhibitions_directory') . '/' . $exhibition->getImagePath();
                if (file_exists($imageFile)) {
                    unlink($imageFile);
                }
            }

            $em->remove($exhibition);
            $em->flush();

            // Log the deletion of the exhibition (using getTitle instead of getName)
            $this->activityLogger->log(
                'DELETE',
                'Exhibition#' . $exhibition->getId() . ' (' . $exhibition->getTitle() . ') deleted.',
                $this->getUser()
            );
        }

        return $this->redirectToRoute('app_exhibition_index');
    }
}
