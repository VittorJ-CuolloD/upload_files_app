<?php
/**
 *
 * CONTROLADOR PARA EL REGISTRO DE USUARIOS DE LA TABLA USER:
 *
 */
namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    protected $em;

    //CARGAMOS LA INTEFACE EntityManagerInterface PARA PODER REALIZAR CONSULTAS A LA BD HACIENDO REFERENCIA A LA ENTIDAD:
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    //PARA PODER ENCRIPTAR LA CONTRASEÑA USAMOS UserPasswordHasherInterface
    #[Route('/register', name: 'app_user')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        //CREAMOS UN FORMULARIO CON LOS DATOS ESPECIFICADOS EN UserType Y LO GENERAMOS DIRECTAMENTE CON LAS CLASES DE BOOTSTRAP:
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //VERIFICAMOS SI EL USUARIO YA EXISTE CON EL CORREO SOLICITADO
            $emailGetVerification = $this->em->getRepository(User::class)->findOneBy(['email' => $form['email']->getData()]);

            if ($emailGetVerification != null) {
                $this->addFlash('errorEmail', 'Email ya existe.');

                return $this->redirectToRoute('app_user');
            }

            //DE NO EXISTIR REALIZAMOS EL HASH DE LA CONTRASEÑA Y LA ALMACENAMOS EN LA BASE DE DATOS:
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form['password']->getData()
            );
            $user->setPassword($hashedPassword);

            //SE PUEDE DEJAR EN BLANCO YA QUE POR DEFECTO SYMFONY TOMA LOS ROLES DE USUARIO COMO ROLE_USER SI NO SE LE ESPECIFICA:
            $user->setRoles(['ROLE_USER']);

            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash('success', 'Se ha registrado exitosamente');
        }

        return $this->render('user/index.html.twig', [
            'formulario' => $form->createView()
        ]);
    }
}
