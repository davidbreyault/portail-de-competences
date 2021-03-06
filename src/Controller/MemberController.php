<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Experience;
use App\Form\UserType;
use App\Form\ApplicationType;
use App\Form\SearchMemberType;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class MemberController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/profile/members", name="members")
     */
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $picture = $user->getPicture();
        $profiles = $this->entityManager->getRepository(User::class)->findAll();

        $form = $this->createForm(ProfileType::class, $profiles);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profiles = $form->getData();
            $status = $profiles['is_employed'];
            $profiles = $this->entityManager->getRepository(User::class)->findBy(['is_employed' => $status]);

            return $this->render('member/index.html.twig', [
                'user'              => $user,
                'picture'           => $picture,
                'profiles'          => $profiles,
                'profile_form'      => $form->createView()
            ]);
        }

        return $this->render('member/index.html.twig', [
            'user'              => $user,
            'picture'           => $picture,
            'profiles'          => $profiles,
            'profile_form'      => $form->createView()
        ]);
    }

    /**
     * @Route("/profile/last-updated-members", name="last-updated-members")
     * 
     * Affiche les derniers profils mis ?? jour
     */
    public function last_updated_members(Request $request): Response
    {
        $user = $this->getUser();
        $picture = $user->getPicture();
        $profiles = $this->entityManager->getRepository(User::class)->lastUpdatedProfiles();

        $form = $this->createForm(ProfileType::class, $profiles);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profiles = $form->getData();
            $status = $profiles['is_employed'];
            $profiles = $this->entityManager->getRepository(User::class)->findBy(['is_employed' => $status]);

            return $this->render('member/index.html.twig', [
                'user'              => $user,
                'picture'           => $picture,
                'profiles'          => $profiles,
                'profile_form'      => $form->createView()
            ]);
        }

        return $this->render('member/index.html.twig', [
            'user'              => $user,
            'picture'           => $picture,
            'profiles'          => $profiles,
            'profile_form'      => $form->createView()
        ]);
    }

    /**
     * @Route("/profile/last-created-members", name="last-created-members")
     * 
     * Affiche les derniers profils cr??es
     */
    public function last_created_members(Request $request): Response
    {
        $user = $this->getUser();
        $picture = $user->getPicture();
        $profiles = $this->entityManager->getRepository(User::class)->lastCreatedProfiles();

        $form = $this->createForm(ProfileType::class, $profiles);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profiles = $form->getData();
            $status = $profiles['is_employed'];
            $profiles = $this->entityManager->getRepository(User::class)->findBy(['is_employed' => $status]);

            return $this->render('member/index.html.twig', [
                'user'              => $user,
                'picture'           => $picture,
                'profiles'          => $profiles,
                'profile_form'      => $form->createView()
            ]);
        }

        return $this->render('member/index.html.twig', [
            'user'              => $user,
            'picture'           => $picture,
            'profiles'          => $profiles,
            'profile_form'      => $form->createView()
        ]);
    }

    /**
     * @Route("/profile/members/search", name="search_members")
     * 
     * Filtre les profils en fonction de diff??rents crit??res
     */
    public function search(Request $request)
    {
        $searchMemberForm = $this->createForm(SearchMemberType::class);
        $profiles = null;
        $searchMemberForm->handleRequest($request);

        if ($searchMemberForm->isSubmitted() && $searchMemberForm->isValid()) {
            $settings = $searchMemberForm->getData();
            //dd($settings);
            $profiles = $this->entityManager->getRepository(User::class)->searchProfile($settings);
        }

        return $this->render('member/search.html.twig', [
            'search_member_form'       => $searchMemberForm->createView(),
            'profiles'                 => $profiles
        ]);
    }

    /**
     * @Route("/profile/member/{id}", name="card_member")
     * 
     * Affiche les donn??es d'un utilisateur
     */
    public function card(Request $request, int $id)
    {
        $user = $this->getUser();
        $picture = $user->getPicture();
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        $file = $profile->getUpload();
        $expertises = $profile->getExpertise()->toArray();
        $experiences = $profile->getExperience()->toArray();

        return $this->render('member/card.html.twig', [
            'user'              => $user,
            'picture'           => $picture,
            'file'              => $file,
            'profile'           => $profile,
            'expertises'        => $expertises,
            'experiences'       => $experiences
        ]);
    }

    /**
     * @Route("/profile/members/add", name="add_member")
     * 
     * Ajoute un utilisateur
     */
    public function add(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        function unaccent($str)
        {
            $search  = array('??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??', '??');
            $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y');
            $str = str_replace($search, $replace, $str);
            return $str;
        }
        $profile = new User;
        $addMemberForm = $this->createForm(UserType::class, $profile);
        $addMemberForm->handleRequest($request);

        if ($addMemberForm->isSubmitted() && $addMemberForm->isValid()) {
            $profile = $addMemberForm->getData();
            // Encodage du mot de passe
            $profile->setPassword($passwordEncoder->encodePassword($profile, $profile->getPassword()));
            // Transformation du nom de et de la ville de l'utilisateur en majuscule
            $profile->setLastname(strtoupper(unaccent($profile->getLastname())));
            $profile->setTown(strtoupper(unaccent($profile->getTown())));
            $profile->setCreatedAt(new \DateTimeImmutable());
            $profile->setUpdatedAt(new \DateTimeImmutable());
            // Si le nouveau profil compte parmi l'effectif de l'entreprise
            if ($profile->getIsEmployed()) {
                // Attribution de son r??le
                $profile->setRoles(['ROLE_EMPLOYEE']);
            }

            // Transfert en base de donn??ees
            $this->entityManager->persist($profile);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le nouvel utilisateur a bien ??t?? cr??e !');
            return $this->redirectToRoute('members');
        }

        return $this->render('member/add.html.twig', [
            'add_member_form'           => $addMemberForm->createView()
        ]);
    }

    /**
     * @Route("/profile/member/{id}/update", name="update_member")
     * 
     * Modifie les donn??es d'un utilisateur
     */
    public function update(Request $request, int $id): Response
    {
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        $user = $this->getUser();

        $form = $this->createForm(ApplicationType::class, $profile);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profile = $form->getData();
            $profile->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($profile);
            $this->entityManager->flush();
            $this->addFlash('success', 'Les donn??es de ' . $profile->getFirstname() . ' ont bien ??t?? mises ?? jour.');
            return $this->redirectToRoute('card_member', ['id' => $profile->getId()]);
        }

        return $this->render('member/update.html.twig', [
            'user'                  => $user,
            'profile'               => $profile,
            'profile_form'          => $form->createView()
        ]);
    }

    /**
     * @Route("/profile/member/{id}/delete/confirmation", name="delete_member_confirmation")
     * 
     * Confirme la supression d'un utilisateur
     */
    public function delete_confirmation(int $id): Response
    {
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        
        return $this->render('member/delete.html.twig', [
            'profile'           => $profile
        ]);
    }

    /**
     * @Route("/profile/member/{id}/delete", name="delete_member")
     * 
     * Supprime un compte utilisateur
     */
    public function delete(Request $request, int $id): Response
    {
        // La suppression d'un utilisateur implique d'abord la suppression de ses exp??riences et de ses comp??tences pour ??viter les erreurs relationnelles SQL.
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        $picture = $profile->getPicture();
        $upload = $profile->getUpload();
        
        // Suppression de la photo de profil
        if (!is_null($picture)) {
            // Suppression du fichier dans le dossier
            $fileName = $picture->getName();
            $filesystem = new Filesystem();
            $filesystem->remove(['uploads/pictures/'.$fileName]);
            // Suppression du fichier dans la base de donn??es
            $this->entityManager->remove($picture);
        }

        // Suppression du document
        if (!is_null($upload)) {
            // Suppression du fichier dans le dossier
            $fileName = $upload->getName();
            $filesystem = new Filesystem();
            $filesystem->remove(['uploads/cv/'.$fileName]);
            // Suppression du fichier dans la base de donn??es
            $this->entityManager->remove($upload);
        }

        // Suppression des exp??riences
        $experiences = $profile->getExperience();
        foreach($experiences as $experience) {
            $profile->removeExperience($experience);
            $this->entityManager->remove($experience);
        }

        // Suppression des comp??tences
        $expertises = $profile->getExpertise();
        foreach($expertises as $expertise) {
            $profile->removeExpertise($expertise);
            $this->entityManager->remove($expertise);
        }

        // Enfin, suppression de l'utilisateur
        $this->entityManager->remove($profile);
        $this->entityManager->flush();
        $this->addFlash('success', 'L\'utilisateur a bien ??t?? supprim??.');
        return $this->redirectToRoute('members');
    }

    /**
     * @Route("/profile/member/{id}/takeon", name="takeon_candidate")
     * 
     * Embauche et ajoute un candidat dans l'effectif de l'entreprise
     */
    public function takeOn(Request $request, int $id): Response
    {
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        $profile->setIsEmployed(1);
        $profile->setRoles(['ROLE_EMPLOYEE']);
        $profile->setUpdatedAt(new \DateTimeImmutable());
        // Ajout d'une exp??rience par d??faut lors de l'embauche d'un candidat
        $experience = new Experience;
        $experience->setProfession('Concepteur D??veloppeur d\'Applications Web');
        $experience->setCompanyName('IS Corp');
        $experience->setWorkplaceTown('Tours');
        $experience->setDateStart(new \DateTimeImmutable());
        $experience->setDescription('Toute l\'??quipe IS Corp vous souhaite la bienvenue !');
        $experience->setUser($profile);

        $this->entityManager->persist($profile);
        $this->entityManager->persist($experience);
        $this->entityManager->flush();
        $this->addFlash('success', 'F??licitations ! Vous avez embauch?? ' . $profile->getFirstname() . ' ' . $profile->getLastname());
        return $this->redirectToRoute('card_member', ['id' => $profile->getId()]);
    }

    /**
     * @Route("/profile/member/{id}/strikeoff", name="srikeoff_employee")
     * 
     * Radier un membre de l'entreprise
     */
    public function strikeOff(Request $request, int $id): Response
    {
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        $profile->setIsEmployed(0);
        $profile->setRoles([]);
        $profile->setUpdatedAt(new \DateTimeImmutable());
        // Met fin ?? la mission actuellement suivi en entreprise
        if (!empty($profile->getExperience()->toArray())) {
            if (is_null($profile->getExperience()[0]->getDateEnd())) {
                $profile->getExperience()[0]->setDateEnd(new \DateTimeImmutable());
            }
        }

        $this->entityManager->persist($profile);
        $this->entityManager->flush();
        $this->addFlash('success', $profile->getFirstname() . ' ' . $profile->getLastname() . ' a ??t?? radi?? de votre effectif entreprise.');
        return $this->redirectToRoute('card_member', ['id' => $profile->getId()]);
    }

    /**
     * @Route("/profile/member/{id}/demote", name="demote")
     * 
     * R??trograder un membre de l'entreprise 
     */
    public function demote(Request $request, int $id): Response
    {
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        $profile->setRoles(['ROLE_EMPLOYEE']);
        $profile->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($profile);
        $this->entityManager->flush();
        $this->addFlash('success', $profile->getFirstname() . ' ' . $profile->getLastname() . ' a bien ??t?? r??trograd??.');
        return $this->redirectToRoute('card_member', ['id' => $profile->getId()]);
    }

    /**
     * @Route("/profile/member/{id}/promote", name="promote")
     * 
     * Promouvoir un membre de l'entreprise
     */
    public function promote(Request $request, int $id): Response
    {
        $profile = $this->entityManager->getRepository(User::class)->find($id);
        $profile->setRoles(['ROLE_COMMERCIAL']);
        $profile->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($profile);
        $this->entityManager->flush();
        $this->addFlash('success', $profile->getFirstname() . ' ' . $profile->getLastname() . ' a bien ??t?? prom??.');
        return $this->redirectToRoute('card_member', ['id' => $profile->getId()]);
    }
}
