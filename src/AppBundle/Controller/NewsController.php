<?php

namespace AppBundle\Controller;

use AppBundle\Entity\News;
use AppBundle\Entity\Repository\NewsRepository;
use AppBundle\Form\NewsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class NewsController extends Controller
{
    public function indexAction($id = null)
    {
        if (null === $id) {
            $articles = $this->getDoctrine()->getRepository(News::class)->findAll();

            return $this->render('@App/news.html.twig', [
                'articles' => $articles
            ]);
        }
        $article = $this->getDoctrine()->getRepository(News::class)->findOneBy(['id' => $id]);

        return $this->render('@App/news_show.html.twig', [
            'article' => $article
        ]);
    }

    public function newAction(Request $request)
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($data);
            $em->flush();

            return $this->redirectToRoute('front');
        }
        return $this->render(
            '@App/news_new.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }
}
