<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Signing;
use DateTime;

class SingUpController extends AbstractController
{
    #[Route('/sing/up', name: 'sing_up')]
    public function singUp(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Obtener todos los usuarios para el spinner
        $users = $entityManager->getRepository(User::class)->findAll();

        if ($request->isMethod('POST')) {
            // Obtener los datos del formulario
            $userId = $request->request->get('user_id');
            $year = $request->request->get('year');
            $month = $request->request->get('month');
            $day = $request->request->get('day');
            $time = $request->request->get('time');

            // Validar que el usuario, año, mes, día y hora estén presentes
            if (empty($userId) || empty($year) || empty($month) || empty($day) || empty($time)) {
                $this->addFlash('error', 'Todos los campos son obligatorios.');
            } else {
                // Validar el formato de la hora
                if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
                    $this->addFlash('error', 'La hora debe tener el formato HH:MM.');
                } else {
                    // Validar si el usuario existe
                    $user = $entityManager->getRepository(User::class)->find($userId);
                    if (!$user) {
                        $this->addFlash('error', 'El usuario seleccionado no existe.');
                    } else {
                        // Crear el objeto Signing y establecer sus valores
                        $datetimeString = "$year-$month-$day $time";
                        $datetime = DateTime::createFromFormat('Y-m-d H:i', $datetimeString);

                        if ($datetime === false) {
                            $this->addFlash('error', 'Fecha y hora no válidas.');
                        } else {
                            $signing = new Signing();
                            $signing->setUser($user);
                            $signing->setDatetime($datetime);

                            // Persistir el fichaje en la base de datos
                            $entityManager->persist($signing);
                            $entityManager->flush();

                            // Mostrar mensaje de éxito
                            $this->addFlash('success', 'Fichaje registrado correctamente.');
                        }
                    }
                }
            }
        }

        return $this->render('sing_up/index.html.twig', [
            'users' => $users,
        ]);
    }
}
