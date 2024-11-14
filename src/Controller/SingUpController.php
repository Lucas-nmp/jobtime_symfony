<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class SingUpController extends AbstractController
{
    #[Route('/sing/up', name: 'sing_up')]
    public function singUp(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Obtener todos los usuarios para el spinner
        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('sing_up/index.html.twig', [
            'users' => $users,
        ]);
    }
}
