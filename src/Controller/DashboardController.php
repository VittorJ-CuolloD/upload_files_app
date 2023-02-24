<?php

namespace App\Controller;

use DateTime;
use App\Entity\File;
use App\Form\FileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class DashboardController extends AbstractController
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $file = new File();
        $form = $this->createForm(FileType::class, $file);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $brochureFile = $form->get('image')->getData();

            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);

                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                try {
                    $brochureFile->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                $file->setImage($newFilename);
                $file->setTitle('');
                $file->setUserId($this->getUser()->getId());
                $file->setDate(new DateTime());

                $this->em->persist($file);
                $this->em->flush();
                $this->addFlash('success', 'Imágen almacenada correctamente.');
            }

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('dashboard/index.html.twig', [
            'formulario' => $form->createView()
        ]);
    }

    #[Route('/dashboard/list', name: 'app_dashboard_list')]
    public function listFiles(Request $request): Response
    {
        $files = $this->em->getRepository(File::class)->findBy(['userId' => $this->getUser()->getId()]);

        $arrayFiles = [];

        foreach ($files as $key => $file) {
            $arrayFiles[] = [
                'id'=> $file->getId(),
                'fecha'=> $file->getDate(),
                'image'=> $file->getImage()
            ];
        }

        return $this->render('dashboard/list.html.twig', [
            'files' => $arrayFiles
        ]);
    }
}
