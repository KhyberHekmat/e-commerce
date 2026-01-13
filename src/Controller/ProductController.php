<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductHistory;
use App\Form\ProductType;
use App\Form\ProductHistoryType;
use App\Form\ProductUpdateType;
use App\Repository\ProductHistoryRepository;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/editor/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }


    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo = $form->get('photo')->getData();

            if($photo)
            {
                $orignalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = $slugger->slug($orignalName);
                $newFileName = $safeFileName.'-'.uniqid().'.'.$photo->guessExtension();
                
                try{
                    $photo->move(
                    $this->getParameter('image_dir'),
                    $newFileName
                    );

                }catch(FileException $exception){

                }

                $product->setPhoto($newFileName);
                
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $stockHistory = new ProductHistory();
            $stockHistory->setQuantity($product->getStock());
            $stockHistory->setProduct($product);
            $stockHistory->setCreatedAt(new DateTimeImmutable());
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success','The product is added to the system');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success','The product is updated successfully');

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('danger','The product is deleted successfully!');
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['POST','GET'])]
    public function addStock($id, EntityManagerInterface $entityManager, Request $request, ProductRepository $productRepository):Response
    {
        $addStock = new ProductHistory();
        $form = $this->createForm(ProductHistoryType::class,$addStock);
        $form->handleRequest($request);
        
        $product = $productRepository->find($id);

        if($form->isSubmitted() && $form->isValid()){
            if($addStock->getQuantity()>0){
                $newQuantity = $product->getStock() + $addStock->getQuantity();
                $product->setStock($newQuantity);

                $addStock->setCreatedAt(new DateTimeImmutable());
                $addStock->setProduct($product);
                
                $entityManager->persist($addStock);
                $entityManager->flush();

                $this->addFlash('success','The stock for the mentioned product is modified');
                
                return $this->redirectToRoute('app_product_index');
            }else{
                $this->addFlash('danger','The stock should be greater than 0 ');
                return $this->redirectToRoute('app_product_stock_add',['id' => $product->getId()]);
            }
        }

        return $this->render('product/addStock.html.twig',
                ['form' => $form->createView(),
                    'product' => $product
                ]

            );
    }

    #[Route('/product/{id}/stock/history', name: 'product_stock_history', methods:['GET'])]
    public function productHistory($id, ProductRepository $productRepository, ProductHistoryRepository $prHistoryRepo):Response
    {
        $product = $productRepository->find($id);
        $productHistory=$prHistoryRepo->findBy(['product' => $product], ['id' => 'DESC']);
        
        return $this->render('product/productStockHistory.html.twig',[
            'productHistory' => $productHistory,
            'product' => $product
        ]);
    }
}