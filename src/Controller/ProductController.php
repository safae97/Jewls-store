<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ProductController extends AbstractController
{

    private $productRepository;
    private $entityManager;

    public function __construct(
        ProductRepository $productRepository,
        ManagerRegistry $doctrine)
    {
        $this->productRepository = $productRepository;
        $this->entityManager = $doctrine->getManager();
    }

    #[Route('/product', name: 'product_list')]
    /**
     * @IsGranted("ROLE_ADMIN", statusCode=404, message="Page not found")
     */
    public function index(): Response
    {
        $products = $this->productRepository->findAll();
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/store/product', name: 'product_store')]
    /**
     * @IsGranted("ROLE_ADMIN", statusCode=404, message="Page not found")
     */
    public function store(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class,$product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $product = $form->getData();
            if($request->files->get('product')['image']){
                $image = $request->files->get('product')['image'];
                $image_name = time().'_'.$image->getClientOriginalName();
                $image->move($this->getParameter('image_directory'),$image_name);
                $product->setImage($image_name);
            }
            // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $this->entityManager->persist($product);

            // actually executes the queries (i.e. the INSERT query)
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Your product was saved'
            );
            return $this->redirectToRoute('product_list');
        }

        return $this->renderForm('product/create.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/product/details/{id}', name: 'product_show')]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'photo_url' => 'http://127.0.0.1:8000/uploads/'
        ]);
    }

    #[Route('/product/edit/{id}', name: 'product_edit')]
    /**
     * @IsGranted("ROLE_ADMIN", statusCode=404, message="Page not found")
     */
    public function editProduct(Product $product,Request $request): Response
    {
        $form = $this->createForm(ProductType::class,$product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $product = $form->getData();
            if($request->files->get('product')['image']){
                $image = $request->files->get('product')['image'];
                $image_name = time().'_'.$image->getClientOriginalName();
                $image->move($this->getParameter('image_directory'),$image_name);
                $product->setImage($image_name);
            }
            // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $this->entityManager->persist($product);

            // actually executes the queries (i.e. the INSERT query)
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Your product was updated'
            );
            return $this->redirectToRoute('product_list');
        }

        return $this->renderForm('product/edit.html.twig', [
            'form' => $form
        ]);       
    }

    #[Route('/product/delete/{id}', name: 'product_delete')]
    /**
     * @IsGranted("ROLE_ADMIN", statusCode=404, message="Page not found")
     */
    public function delete(Product $product): Response
    {
        $filesystem = new Filesystem(); 
        $imagePath = './uploads/'.$product->getImage();
        if($filesystem->exists($imagePath)){
            $filesystem->remove($imagePath);
        }

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $this->entityManager->remove($product);

        // actually executes the queries (i.e. the INSERT query)
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'Your product was removed'
        );
        return $this->redirectToRoute('product_list');
    }
}