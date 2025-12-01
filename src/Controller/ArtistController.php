<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Form\ArtistType;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/artist')]
class ArtistController extends AbstractController
{
    #[Route('/', name: 'app_artist_index', methods: ['GET'])]
    public function index(ArtistRepository $artistRepository): Response
    {
        return $this->render('artist/index.html.twig', [
            'artists' => $artistRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_artist_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $artist = new Artist();
        $form = $this->createForm(ArtistType::class, $artist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $uploadDir = $this->getParameter('artists_directory');
                $fileName = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($uploadDir, $fileName);
                } catch (FileException $e) {
                    dd($e);
                }

                $artist->setImagePath($fileName);
            }

            $em->persist($artist);
            $em->flush();

            return $this->redirectToRoute('app_artist_index');
        }

        return $this->render('artist/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_artist_show', methods: ['GET'])]
    public function show(Artist $artist): Response
    {
        return $this->render('artist/show.html.twig', [
            'artist' => $artist,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_artist_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Artist $artist, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ArtistType::class, $artist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $uploadDir = $this->getParameter('artists_directory');
                $fileName = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($uploadDir, $fileName);
                } catch (FileException $e) {
                    dd($e);
                }

                $artist->setImagePath($fileName);
            }

            $em->flush();

            return $this->redirectToRoute('app_artist_index');
        }

        return $this->render('artist/edit.html.twig', [
            'form' => $form->createView(),
            'artist' => $artist,
        ]);
    }

    #[Route('/{id}', name: 'app_artist_delete', methods: ['POST'])]
    public function delete(Request $request, Artist $artist, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$artist->getId(), $request->request->get('_token'))) {
            $em->remove($artist);
            $em->flush();
        }

        return $this->redirectToRoute('app_artist_index');
    }
}
