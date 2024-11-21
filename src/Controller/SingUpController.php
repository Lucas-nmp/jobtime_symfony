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
            $reason = $request->request->get('reason');

            // Validar que el usuario, año, mes, día y hora estén presentes
            if (empty($userId) || empty($year) || empty($month) || empty($day) || empty($time) || empty($reason)) {
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
                            // Consultar cuántos fichajes existen en la misma fecha para determinar el valor de 'entry'
                            // Establecer el inicio y fin del día para hacer la consulta
                            $startOfDay = (clone $datetime)->setTime(0, 0);
                            $endOfDay = (clone $datetime)->setTime(23, 59, 59);

                            // Contar los fichajes realizados en la misma fecha
                            $existingSigningsCount = $entityManager->getRepository(Signing::class)
                                ->createQueryBuilder('s')
                                ->where('s.datetime BETWEEN :startOfDay AND :endOfDay')
                                ->setParameter('startOfDay', $startOfDay)
                                ->setParameter('endOfDay', $endOfDay)
                                ->select('COUNT(s.id)')
                                ->getQuery()
                                ->getSingleScalarResult();

                            // El valor de 'entry' será true si el número de fichajes es par, y false si es impar
                            $entry = ($existingSigningsCount % 2 === 0); // True si es par (entrada), false si es impar (salida)

                            // Crear el objeto Signing
                            $signing = new Signing();
                            $signing->setUser($user);
                            $signing->setDatetime($datetime);
                            $signing->setEntry($entry);  // Asignar el valor de 'entry'
                            $signing->setType("Manual - " . $reason);
                            
                            

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
