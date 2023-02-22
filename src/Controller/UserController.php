<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/register', name: 'app_user')]
    public function index(Request $request,UserPasswordHasherInterface $passwordHasher): Response
    {

        $user = new User();
        $form = $this->createForm(UserType::class,$user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form['password']->getData()
            );
            $user->setPassword($hashedPassword);

            dd($request);

            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success','Se ha registrado exitosamente');
        }

        return $this->render('user/index.html.twig', [
            'formulario' => $form->createView()
        ]);
    }
}
