<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Form\SettingType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/settings')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'app_settings_index', methods: ['GET'])]
    public function index(ManagerRegistry $doctrine, Request $request): Response
    {
        /** @var \App\Repository\SettingRepository $repo */
        $repo = $doctrine->getRepository(Setting::class);

        $group = $request->query->get('group');

        $settings = $group
            ? $repo->findBy(['groupName' => $group], ['keyName' => 'ASC'])
            : $repo->findBy([], ['groupName' => 'ASC', 'keyName' => 'ASC']);

        $all = $repo->findBy([], ['groupName' => 'ASC']);
        $groups = array_values(array_unique(array_map(fn ($s) => $s->getGroupName(), $all)));

        return $this->render('settings/index.html.twig', [
            'settings' => $settings,
            'groups' => $groups,
            'currentGroup' => $group,
        ]);
    }

    #[Route('/new', name: 'app_settings_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $setting = new Setting();
        $setting->setGroupName('general');
        $setting->setIsActive(true);

        $form = $this->createForm(SettingType::class, $setting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($setting);
            $em->flush();

            $this->addFlash('success', 'Setting created.');
            return $this->redirectToRoute('app_settings_index');
        }

        return $this->render('settings/form.html.twig', [
            'title' => 'Add Setting',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_settings_edit', methods: ['GET','POST'])]
    public function edit(Setting $setting, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SettingType::class, $setting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Setting updated.');
            return $this->redirectToRoute('app_settings_index');
        }

        return $this->render('settings/form.html.twig', [
            'title' => 'Edit Setting',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_settings_delete', methods: ['POST'])]
    public function delete(Setting $setting, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_setting_'.$setting->getId(), (string) $request->request->get('_token'))) {
            $em->remove($setting);
            $em->flush();
            $this->addFlash('success', 'Setting deleted.');
        }

        return $this->redirectToRoute('app_settings_index');
    }
}
