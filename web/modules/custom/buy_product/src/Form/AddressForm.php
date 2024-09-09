<?php

namespace Drupal\buy_product\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for users to enter their address for purchasing a product.
 */
class AddressForm extends FormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an AddressForm object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(MessengerInterface $messenger, Connection $database) {
    $this->messenger = $messenger;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'address_form';
  }

  /**
   * Builds the address form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\node\Entity\Node|null $node
   *   The node object.
   *
   * @return array
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();
    $query = $this->database->select('customer_address', 'ca')
      ->fields('ca', ['address'])
      ->condition('ca.uid', $uid)
      ->execute()
      ->fetchField();

    if ($query) {
      $this->messenger->addMessage($this->t('Order placed! Address already exists...'));
      $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
      return $form;
    }

    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit and Buy'),
    ];

    $form_state->setTemporaryValue('node', $node);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();
    $address = $form_state->getValue('address');
    $node = $form_state->getTemporaryValue('node');


    $this->database->merge('customer_address')
      ->key(['uid' => $uid])
      ->fields(['address' => $address])
      ->execute();

    $this->messenger->addStatus($this->t('Product purchased successfully!'));
    $form_state->setRedirect('entity.node.canonical', ['node' => $node->id()]);
  }
}
