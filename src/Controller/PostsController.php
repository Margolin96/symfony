<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Entity\Post;

class PostsController extends AbstractController
{
    /**
     * @Route("posts", name="posts_list")
     */
    public function index(LoggerInterface $logger)
    {
        $user_id = $this->getUser()->getId();

        $s_repo = $this->getDoctrine()->getManager()->getRepository(Subscription::class);
        $subscribed_ids = $s_repo->getSubscriptions($user_id);
        $subscribed_ids[] = $user_id;

        $subscriptions = $this->getDoctrine()->getManager()->getRepository(User::class)->findBy([
            'id' => $subscribed_ids
        ]);
        $_subscriptions = [];
        foreach ($subscriptions as $subscription) {
            $_subscriptions[$subscription->getId()] = $subscription;
        }
        unset($subscriptions);

        $repository = $this->getDoctrine()->getRepository(Post::class);
        $posts = $repository->findBy(
            ['author_id' => $subscribed_ids],
            ['create_date' => 'DESC']
        );

        return $this->render('posts/index.html.twig', [
            'controller_name' => 'PostsController',
            'posts' => $posts,
            'subscriptions' => $_subscriptions
        ]);
    }

    /**
     * @Route("posts/{id}", name="posts_single", requirements={"id"="\d+"})
     */
    public function show(Post $post)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('posts/single.html.twig', [
            'post' => $post
        ]);
    }

    /**
     * @Route("posts/{id}/delete", name="posts_delete", requirements={"id"="\d+"})
     */
    public function delete($id)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $entityManager = $this->getDoctrine()->getManager();
        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException(
                'Нет записи для данного id ('.$id.')'
            );
        }

        if ($this->getUser()->getId() != $post->getAuthorId()) {
            throw $this->createAccessDeniedException(
                'Нет прав на удаление данной записи'
            );
        }

        $entityManager->remove($post);
        $entityManager->flush();

        return $this->redirectToRoute('posts_list');
    }

    /**
     * @Route("posts/new", name="posts_new")
     */
    public function new(Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $post = new Post();

        $form = $this->createFormBuilder($post)
            ->add('content', TextareaType::class, ['label' => 'Текст поста'])
            ->add('save', SubmitType::class, ['label' => 'Опубликовать'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();
            $post->setAuthorId($this->getUser()->getId());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('posts_list');
        }

        return $this->render('posts/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
