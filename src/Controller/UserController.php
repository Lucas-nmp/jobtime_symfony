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
use App\Entity\Signing;
use App\Form\UserType;
use App\Service\PdfGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use DateTimeImmutable;
use App\Repository\SigningRepository;


class UserController extends AbstractController
{
    
    #[Route('/user', name: 'app_user')]
    public function index(Request $request, EntityManagerInterface $entityManager, Security $security, PdfGenerator $pdfGenerator): Response
    {
        // Obtener todos los usuarios para el spinner
        $users = $entityManager->getRepository(User::class)->findAll();
        // Obtener el usuario logeado
        $user = $security->getUser();
        // Obtener el rol del usuario logeado
        $role = $user->getRoles()[0] ?? null;
    
        // Obtener parámetros de fecha y usuario
        $userId = $request->query->get('userId');
        $year = $request->query->get('year');
        $month = $request->query->get('month');
        $day = $request->query->get('day');

    
        // Inicializar variables de rango de fechas
        $startDate = null;
        $endDate = null;
        $formattedDate = "Por favor, seleccione un año"; // Mensaje por defecto si no se selecciona año
    
        // Definir rango de fechas según los selectores
        if ($year) {
            $startDate = new DateTimeImmutable("$year-01-01 00:00:00");
            $endDate = new DateTimeImmutable("$year-12-31 23:59:59");
            $formattedDate = $year;
    
            if ($month) {
                $startDate = $startDate->setDate($year, $month, 1);
                $endDate = $startDate->setDate($year, $month, (int) $startDate->format('t'))->setTime(23, 59, 59);
                $formattedDate = sprintf('%02d-%s', $month, $year);
    
                if ($day) {
                    $startDate = $startDate->setDate($year, $month, $day)->setTime(0, 0, 0);
                    $endDate = $startDate->setDate($year, $month, $day)->setTime(23, 59, 59);
                    $formattedDate = sprintf('%02d-%02d-%s', $day, $month, $year);
                }
            }
        }
    
        // Construir el repositorio de fichajes y aplicar filtro de fechas
        $query = $entityManager->createQueryBuilder()
            ->select('s')
            ->from(Signing::class, 's')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId);
    
        // Si el rango de fechas está definido, agregar el filtro
        if ($startDate && $endDate) {
            $query->andWhere('s.datetime BETWEEN :startDate AND :endDate')
                    ->setParameter('startDate', $startDate)
                    ->setParameter('endDate', $endDate);
        }
    
        // Ejecutar consulta de fichajes
        $signings = $query->getQuery()->getResult();

        // Contar los días distintos en los que se ha fichado
        $uniqueDays = [];
        foreach ($signings as $signing) {
            $date = $signing->getDatetime()->format('Y-m-d'); // Extraer solo la fecha (sin hora)
            if (!in_array($date, $uniqueDays)) {
                $uniqueDays[] = $date; // Añadir día único a la lista
            }
        }

        // Número total de días distintos trabajados
        $distinctDaysCount = count($uniqueDays);
        $totalTheoreticalHours = 0;
        $dailyWorkHours = 0;
        $diffHours = 0;
        $diffMinutes = 0;
        $formattedTheoreticalHours = 0;
        $differenceFormatted = 0;
        $diffSeconds = 0;
        
         

        $session = $request->getSession();
        $session->set('signings', $signings);
        $session->set('selectedUser', $userId);

        

        // Obtener total de horas trabajadas en el rango de fechas
        $totalHours = null; // Valor predeterminado

        if ($startDate && $endDate) {
            $signingsRepo = $entityManager->getRepository(Signing::class);
            $totalHours = $signingsRepo->getTotalHoursWorked($userId, $startDate, $endDate);


            // Obtener las horas diarias de trabajo directamente del repositorio
            $userRepo = $entityManager->getRepository(User::class);
            $userSelec = $userRepo->find($userId); // Obtener el usuario

            // Asegurarnos de que las horas diarias se traten como un float
            $dailyWorkHours = (float) $userSelec->getDailyWorkHours();

            // Calcular el total de horas a trabajar
            $totalTheoreticalHours = $dailyWorkHours * $distinctDaysCount;

            if(floor($totalTheoreticalHours) == $totalTheoreticalHours){
                $formattedTheoreticalHours = sprintf('%02d:00:00', (int) $totalTheoreticalHours);
            } else {
                $formattedTheoreticalHours = sprintf('%02d:30:00', (int) floor($totalTheoreticalHours));
            }

            // Separar totalHours (total de horas trabajadas) en hh:mm:ss
            list($totalHoursInt, $totalMinutes, $totalSeconds) = sscanf($totalHours, '%d:%d:%d');
            $totalHoursInSeconds = $totalHoursInt * 3600 + $totalMinutes * 60 + $totalSeconds;

            // Separar formattedTheoreticalHours en hh:mm:ss
            list($theoreticalHoursInt, $theoreticalMinutes, $theoreticalSeconds) = sscanf($formattedTheoreticalHours, '%d:%d:%d');
            $theoreticalHoursInSeconds = $theoreticalHoursInt * 3600 + $theoreticalMinutes * 60 + $theoreticalSeconds;

            // Calcular la diferencia entre ambos en segundos
            $differenceInSeconds = abs($totalHoursInSeconds - $theoreticalHoursInSeconds);

            

            // Convertir la diferencia de segundos a hh:mm:ss
            $diffHours = floor($differenceInSeconds / 3600);
            $diffMinutes = floor(($differenceInSeconds % 3600) / 60);
            $diffSeconds = $differenceInSeconds % 60;

            // Formatear la diferencia como hh:mm:ss
            $differenceFormatted = sprintf('%02d:%02d:%02d', $diffHours, $diffMinutes, $diffSeconds);

             

        } else {
            $totalHours = "Por favor, seleccione una fecha válida";
        }
        
    
        return $this->render('user/index.html.twig', [
            'users' => $users,
            'user' => $user,
            'role' => $role,
            'signings' => $signings,
            "totalHours" => $totalHours,
            'diffHours' => $diffHours,
            'diffMinutes' => $diffMinutes,
            'diffSeconds' => $diffSeconds,
            'differenceFormatted' => $differenceFormatted,
            'formattedTheoreticalHours' => $formattedTheoreticalHours,
            'selectedDate' => $formattedDate, // Pasar la fecha seleccionada al template
            'formattedDate' => $formattedDate,
        ]);
    }
        

    
    #[Route('/user/print-pdf', name: 'app_user_print_pdf')]
    public function printPdf(Request $request, EntityManagerInterface $entityManager, PdfGenerator $pdfGenerator)
    {
        
        $totalHours = $request->query->get('totalHours');
        $formattedDate = $request->query->get('formattedDate');

        $formattedTheoreticalHours = $request->query->get('formattedTheoreticalHours');
        $differenceFormatted = $request->query->get('differenceFormatted');

        $session = $request->getSession();
        $signings = $session->get('signings');
        $userId = $session->get('selectedUser');

      

        $user = $entityManager->getRepository(User::class)->find($userId);
        $userName = $user ? $user->getName() : 'Usuario desconocido';


        $htmlContent = $this->renderView('pdf/signings.html.twig', [
            'userName' => $userName,
            'totalHours' => $totalHours,
            'formattedDate' => $formattedDate,
            'formattedTheoreticalHours' => $formattedTheoreticalHours,
            'differenceFormatted' => $differenceFormatted,
            'signings' => $signings,
        ]);

        return $pdfGenerator->generatePdf($htmlContent, 'fichajes.pdf');
    }
      
        

    #[Route('/user/register', name: 'user_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher  ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validar si dailyWorkHours es un número entero o termina en .5
            $dailyWorkHours = $user->getDailyWorkHours();
            if (!preg_match('/^\d+(\.5)?$/', (string) $dailyWorkHours)) {
                $this->addFlash('error', 'Las horas diarias de trabajo deben ser un número entero o terminar en .5.');
                return $this->render('user/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

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
    public function userDelete(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Crear un formulario simple para ingresar el ID
        $defaultData = ['message' => 'Ingrese el ID del usuario a eliminar'];
        $form = $this->createFormBuilder($defaultData)
            ->add('userId', TextType::class, [
                'label' => 'ID del Usuario',
                'required' => true
            ])
            
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $userId = $data['userId'];

            // Buscar al usuario por ID
            $user = $entityManager->getRepository(User::class)->find($userId);

            if ($user) {
                // Eliminar el usuario
                $entityManager->remove($user);
                $entityManager->flush();

                // Mostrar mensaje de éxito
                $this->addFlash('success', 'Usuario eliminado exitosamente');

                // Redefinir el formulario vacío para limpiar el campo
                $form = $this->createFormBuilder()
                ->add('userId', TextType::class, [
                    'label' => 'ID del Usuario',
                    'required' => true
                ])
                ->getForm();


            } else {
                // Mostrar mensaje de error si no se encuentra el usuario
                $this->addFlash('error', 'Usuario no encontrado');
            }
        }

        return $this->render('user/delete.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/modify', name: 'user_modify')]
    public function modifyUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Obtener todos los usuarios para el spinner
        $users = $entityManager->getRepository(User::class)->findAll();

        // Inicializamos el formulario vacío
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // Comprobamos si el formulario fue enviado
        if ($request->isMethod('POST')) {
            $userId = $request->request->get('user_id');
            $existingUser = $entityManager->getRepository(User::class)->find($userId);

            if (!$existingUser) {
                $this->addFlash('error', 'Usuario no encontrado');
            } else {
                // Llenamos el formulario con los datos enviados
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    // Validar si dailyWorkHours es un número entero o termina en .5
                    $dailyWorkHours = $form->get('dailyWorkHours')->getData();
                    if (!preg_match('/^\d+(\.5)?$/', (string) $dailyWorkHours)) {
                        $this->addFlash('error', 'Las horas diarias de trabajo deben ser un número entero o terminar en .5.');
                        return $this->render('user/modify.html.twig', [
                            'users' => $users,
                            'form' => $form->createView(),
                        ]);
                    }

                    // Validar si el ID o nombre ya están en uso (excepto para el usuario actual)
                    $newUserId = $form->get('id')->getData();
                    $newUserName = $form->get('name')->getData();
                    $newUserEmail = $form->get('email')->getData();

                    $existingUserById = $entityManager->getRepository(User::class)->find($newUserId);
                    if ($existingUserById && $existingUserById->getId() !== $existingUser->getId()) {
                        $this->addFlash('error', 'El ID ingresado ya está en uso.');
                        return $this->render('user/modify.html.twig', [
                            'users' => $users,
                            'form' => $form->createView(),
                        ]);
                    }

                    $existingUserByName = $entityManager->getRepository(User::class)->findOneBy(['name' => $newUserName]);
                    if ($existingUserByName && $existingUserByName->getId() !== $existingUser->getId()) {
                        $this->addFlash('error', 'El nombre de usuario ingresado ya está en uso.');
                        return $this->render('user/modify.html.twig', [
                            'users' => $users,
                            'form' => $form->createView(),
                        ]);
                    }

                    $existingUserByEmail = $entityManager->getRepository(User::class)->findOneBy(['email' => $newUserEmail]);
                    if ($existingUserByEmail && $existingUserByEmail->getId() !== $existingUser->getId()) {
                        $this->addFlash('error', 'El email de usuario ingresado ya está en uso.');
                        return $this->render('user/modify.html.twig', [
                            'users' => $users,
                            'form' => $form->createView(),
                        ]);
                    }

                    // Actualizamos los campos del usuario
                    $existingUser->setName($newUserName);
                    $existingUser->setEmail($newUserEmail);
                    $existingUser->setPhone($form->get('phone')->getData());
                    $existingUser->setId($newUserId);
                    $existingUser->setDailyWorkHours($dailyWorkHours);

                    // Verificamos si se ingresó una nueva contraseña
                    $newPassword = $form->get('password')->getData();
                    if ($newPassword) {
                        $hashedPassword = $passwordHasher->hashPassword($existingUser, $newPassword);
                        $existingUser->setPassword($hashedPassword);
                    }

                    // Guardamos los cambios
                    $entityManager->flush();

                    $this->addFlash('success', 'Usuario modificado exitosamente');
                    return $this->redirectToRoute('user_modify');
                }
            }
        }

        return $this->render('user/modify.html.twig', [
            'users' => $users,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/user/get/{id}', name: 'get_user_data', methods: ['GET'])]
    public function getUserData(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Usuario no encontrado']);
        }

        // Devolver los datos del usuario en formato JSON
        return $this->json([
            'success' => true,
            'user' => [
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'id' => $user->getId(),
                'phone' => $user->getPhone(),
                'dailyWorkHours' => $user->getDailyWorkHours(),
            ],
        ]);
    }


}
