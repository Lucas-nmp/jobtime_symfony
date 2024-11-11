<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use App\Entity\User;
use App\Form\UserType;


class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/user/register', name: 'user_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher  ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Comprobar si el ID o el nombre ya existen
            $existingUserById = $entityManager->getRepository(User::class)->find($user->getId());
            $existingUserByName = $entityManager->getRepository(User::class)->findOneBy(['name' => $user->getName()]);
            $existingUserByEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
    
            if ($existingUserById) {
                $this->addFlash('error', 'El ID ingresado ya está en uso.');
            } elseif ($existingUserByName) {
                $this->addFlash('error', 'El nombre de usuario ingresado ya está en uso.');
            } elseif ($existingUserByEmail) {
                $this->addFlash('error', 'El email de usuario ingresado ya está en uso.');    
            } else {
                // Codificar la contraseña antes de persistir el usuario
                $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($hashedPassword);
                $user->setRoles(['ROLE_USER']);
    
                // Persistir el usuario en la base de datos
                $entityManager->persist($user);
                $entityManager->flush();
    
                // Limpiar el formulario y mostrar un mensaje de éxito
                $this->addFlash('success', 'Usuario registrado exitosamente');
                return $this->redirectToRoute('user_register');
            }
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/delete', name: 'user_delete')]
    public function delete(): Response
    {
        return $this->render('user/delete.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/user/modify', name: 'user_modify')]
    public function modify(): Response
    {
        return $this->render('user/modify.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }


}
