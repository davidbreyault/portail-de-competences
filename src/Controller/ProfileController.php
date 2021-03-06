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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

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
        $picture = $user->getPicture();

        return $this->render('profile/index.html.twig', [
            'user'          => $user,
            'picture'       => $picture
        ]);
    }

    /**
     * @Route("/profile/delete_confirmation", name="delete_profile_confirmation")
     * 
     * Confirmation de la suppression du compte utilisateur
     */
    public function delete_profile_confirmation(): Response
    {
        return $this->render('profile/delete/confirmation.html.twig');
    }

    /**
     * @Route("/profile/delete", name="delete_profile")
     * 
     * Supprime le compte de l'utilisateur connecté (seulement pour les candidats)
     */
    public function delete_profile(): Response
    {
        $user = $this->getUser();
        $picture = $user->getPicture();
        $upload = $user->getUpload();
        // Suppression de la photo de profil
        if (!is_null($picture)) {
            // Suppression du fichier dans le dossier
            $fileName = $picture->getName();
            $filesystem = new Filesystem();
            $filesystem->remove(['uploads/pictures/'.$fileName]);
            // Suppression du fichier dans la base de données
            $this->entityManager->remove($picture);
        }
        // Suppression du document
        if (!is_null($upload)) {
            // Suppression du fichier dans le dossier
            $fileName = $upload->getName();
            $filesystem = new Filesystem();
            $filesystem->remove(['uploads/cv/'.$fileName]);
            // Suppression du fichier dans la base de données
            $this->entityManager->remove($upload);
        }
        // Déconnexion en force de l'utilisateur avant suppression de ce dernier    
        $this->get('security.token_storage')->setToken(null);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'Votre compte a bien été supprimé !');

        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/profile/data/update", name="update_data_profile")
     * 
     * Modifie les données de l'utilsateur connecté
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
            $user->setUpdatedAt(new \DateTimeImmutable());
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
