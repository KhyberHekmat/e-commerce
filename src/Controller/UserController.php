<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin/user/{id}/to/editor', name: 'app_user_to_editor')]
    public function changeRole(EntityManagerInterface $entityManager, User $user):Response
    {
        $user->setRoles(["ROLE_EDITOR", "ROLE_USER"]);
        $entityManager->flush();

        $this->addFlash('success', 'The editor role is added to the user');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/editor', name: 'app_user_remove_editor')]
    public function removeRoleEditor(EntityManagerInterface $entityManager, User $user):Response
    {
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('danger', 'The editor role is removed from user');

        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/user', name: 'app_user_remove')]
    public function removeUser(EntityManagerInterface $entityManager,$id, UserRepository $userRepository):Response
    {
        $findUser = $userRepository->find($id);
        $entityManager->remove($findUser);
        $entityManager->flush();

        $this->addFlash('danger', 'The user is deleted successfully!');

        return $this->redirectToRoute('app_user');
    }
}