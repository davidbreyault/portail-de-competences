<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/profile/categories", name="categories")
     */
    public function index(): Response
    {
        $user = $this->getUser();
        $categories = $this->entityManager->getRepository(Category::class)->findAll();

        return $this->render('category/index.html.twig', [
            'user'                => $user,
            'categories'          => $categories
        ]);
    }

    /**
     * @Route("/profile/category/add", name="add_category")
     */
    public function add(Request $request): Response
    {
        $category = new Category;
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            $this->addFlash('success', $category->getName() . ' a bien été ajouté à votre liste de catégories.');
            return $this->redirectToRoute('categories');
        }

        return $this->render('category/add.html.twig', [
            'category_form'      => $form->createView()
        ]);
    }

    /**
     * @Route("/profile/category/{id}/update", name="update_category")
     */
    public function update(Request $request, int $id): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($id);
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            $this->addFlash('success', $category->getName() . ' a bien été ajouté à votre liste de catégories.');
            return $this->redirectToRoute('categories');
        }

        return $this->render('category/add.html.twig', [
            'category'           => $category,
            'category_form'      => $form->createView()
        ]);
    }

    /**
     * @Route("/profile/category/{id}/delete", name="delete_category")
     */
    public function delete(Request $request, int $id): Response
    {
        $category = $this->entityManager->getRepository(Category::class)->find($id);

        $this->entityManager->persist($category);
        $this->entityManager->remove($category);
        $this->entityManager->flush();
        $this->addFlash('success', 'La catégorie ' . $category->getName() . ' a bien été supprimée.');
        return $this->redirectToRoute('categories');
    }
}
