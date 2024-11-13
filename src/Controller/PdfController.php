<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class PdfController extends AbstractController
{
    #[Route('/imprimir-pdf', name: 'app_imprimir_pdf')]
    public function imprimirPdf(): Response
    {
        // Configuración de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);

        // Contenido HTML simple para el PDF
        $html = '<h1>Hola Mundo</h1>';

        // Cargar el contenido HTML
        $dompdf->loadHtml($html);

        // Establecer el tamaño de la página
        $dompdf->setPaper('A4', 'portrait');

        // Renderizar el PDF
        $dompdf->render();

        // Devolver el PDF como respuesta
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="hola_mundo.pdf"'
        ]);
    }
}
