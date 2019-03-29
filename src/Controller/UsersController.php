<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{
    /**
     * @Route("/users", name="users_list")
     */
    public function index()
    {
        $user_id = $this->getUser()->getId();

        $entityManager = $this->getDoctrine()->getManager();
        $users = $entityManager->getRepository(User::class)->findAll();

        $subscriptions = $entityManager->getRepository(Subscription::class)->findBy([
            '_from' => $user_id
        ]);
        $_subscriptions = [];
        foreach ($subscriptions as $subscription) {
            $_subscriptions[$subscription->getTo()] = true;
        }
        unset($subscriptions);

        return $this->render('users/index.html.twig', [
            'users' => $users,
            'subscriptions' => $_subscriptions
        ]);
    }

    /**
     * @Route("/users/{id}/subscribe", name="users_subscribe")
     */
    public function subscribe($id, \Swift_Mailer $mailer) {
        $user = $this->getUser();
        $user_id = $user->getId();

        if ($user_id != $id) {
            $_user = $this->getDoctrine()->getManager()->getRepository(User::class)->find($id);
            if (!$_user) {
                throw $this->createNotFoundException(
                    'Пользователь с таким id не найден'
                );
            }

            $subscription = new Subscription();
            $subscription->setFrom($user_id);
            $subscription->setTo($id);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($subscription);
            $entityManager->flush();

            $message = (new \Swift_Message('Новая подписка'))
                ->setFrom('TestSite@margol.in')
                ->setTo($_user->getEmail())
                ->setBody(
                    $this->renderView('emails/subscription.html.twig', [
                        'user' => $_user,
                        'is_subscribed' => true
                    ]),
                    'text/html'
                );
            $mailer->send($message);
        }

        return $this->redirectToRoute('users_list');
    }

    /**
     * @Route("/users/{id}/unsubscribe", name="users_unsubscribe")
     */
    public function unsubscribe($id, \Swift_Mailer $mailer) {
        $user = $this->getUser();
        $user_id = $user->getId();

        $_user = $this->getDoctrine()->getManager()->getRepository(User::class)->find($id);
        if (!$_user) {
            throw $this->createNotFoundException(
                'Пользователь с таким id не найден'
            );
        }

        $entityManager = $this->getDoctrine()->getManager();
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy([
            '_from' => $user_id,
            '_to' => $id
        ]);

        if (!$subscription) {
            throw $this->createNotFoundException(
                'Вы не подписаны на данного пользователя ('.$id.')'
            );
        }

        $entityManager->remove($subscription);
        $entityManager->flush();

        $message = (new \Swift_Message('От вас отписались'))
            ->setFrom('TestSite@margol.in')
            ->setTo($_user->getEmail())
            ->setBody(
                $this->renderView('emails/subscription.html.twig', [
                    'user' => $_user,
                    'is_subscribed' => false
                ]),
                'text/html'
            );
        $mailer->send($message);

        return $this->redirectToRoute('users_list');
    }
}
