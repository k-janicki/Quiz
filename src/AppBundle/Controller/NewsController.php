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
//        $article = $this->getDoctrine()->getRepository(News::class)->findOneBy(['id' => $id]);
//
//        return $this->render('@App/news_show.html.twig', [
//            'article' => $article
//        ]);
    }

    public function newAction(Request $request)
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $body = $data->getBody();
            $start = strpos($body,'data:image/png;base64,');
            $startLength = strlen('data:image/png;base64,');
            if ($start) {
                $path = '../web/upload/';
                $start = $start + $startLength;
                $end = "data-filename";
                $endPos = strpos($body,' data-filename');
                $endStringLength = $endPos - strlen(substr($body, $endPos)) - $startLength - 2;
                $imgString = $this->getStringBetween($body, 'src="data:image/png;base64,', '"');
                $imgReplaceString = $this->getStringBetween($body, 'src="data:image/png;base64,', '">');
                $img = base64_decode($imgString);
                $str = substr($body, $endPos);
                $fileName = $this->generateRandomString().'.jpg';
                $newBody = str_replace('src="data:image/png;base64,'.$imgReplaceString,"src='/upload/".$fileName."'" , $body);
                file_put_contents($path.$fileName,$img);
                $data->setBody($newBody);
            }

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

    private function getStringBetween($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    private function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }

}
