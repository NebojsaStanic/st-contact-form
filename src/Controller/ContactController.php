<?php
namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use function MongoDB\BSON\toJSON;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ContactController extends Controller
{
    public function new(Request $request, LoggerInterface $logger)
    {
        $contact = new Contact();

        $form = $this->createForm(ContactType::class, $contact);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contact = $form->getData();

            $this->writeToFile($contact);
            $this->saveToDatabase($contact);

            return $this->render('contact/success.html.twig', array(
                'contact' => $contact
            ));
        }

        return $this->render('contact/form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    private function writeToFile(Contact $contact)
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists('contacts.txt')) {
            $fileSystem->appendToFile('contacts.txt', $this->json($contact));
        } else {
            $fileSystem->touch('contacts.txt');
            $fileSystem->appendToFile('contacts.txt', $this->json($contact));
        }
    }

    private function saveToDatabase(Contact $contact)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($contact);
        $entityManager->flush();
    }
}