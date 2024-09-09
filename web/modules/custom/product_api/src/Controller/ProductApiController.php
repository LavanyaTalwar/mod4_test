<?php

namespace Drupal\product_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a controller for the Product API.
 */
class ProductApiController extends ControllerBase {

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\UrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a ProductApiController object.
   *
   * @param \Drupal\Core\UrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   */
  public function __construct($file_url_generator) {
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * Creates an instance of the controller.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   A new instance of this controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_url_generator')
    );
  }

  /**
   * Lists all products.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the product information.
   */
  public function listProducts(Request $request) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'products')
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

    $products = [];
    foreach ($nodes as $node) {
      $image = $node->get('field_product_image')->entity;
      $image_url = $image ? $this->fileUrlGenerator->generateAbsoluteString($image->getFileUri()) : NULL;

      $products[] = [
        'title' => $node->getTitle(),
        'description' => $node->get('body')->value,
        'price' => $node->get('field_price')->value,
        'images' => $image_url,
      ];
    }

    return new JsonResponse($products);
  }
}
