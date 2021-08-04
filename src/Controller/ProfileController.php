<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ApplicationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProfileController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user'          => $user
        ]);
    }

    /**
     * @Route("/profile/delete_confirmation", name="delete_profile_confirmation")
     */
    public function delete_profile_confirmation(): Response
    {
        return $this->render('profile/delete/confirmation.html.twig');
    }

    /**
     * @Route("/profile/delete", name="delete_profile")
     */
    public function delete_profile(): Response
    {
        $user = $this->getUser();

        $this->entityManager->persist($user);
        // Déconnexion en force de l'utilisateur avant suppression de ce dernier    
        $this->get('security.token_storage')->setToken(null);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'Votre compte a bien été supprimé !');

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/profile/update_data", name="update_data_profile")
     */
    public function update_data_profile(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ApplicationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            // Transformation du nom de et de la ville de l'utilisateur en majuscule
            $user->setLastname(strtoupper($user->getLastname()));
            $user->setTown(strtoupper($user->getTown()));
            // Transfert en base de donnée
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Vos données personnelles ont bien été modifiées.');
            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/update/update_data.html.twig', [
            'user'                     => $user,
            'update_profile_data_form' => $form->createView()
        ]);
    }
}
