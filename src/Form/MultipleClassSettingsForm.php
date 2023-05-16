<?php

namespace Drupal\multiple_class\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config settings for Multiple Class.
 */
class MultipleClassSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_class_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['multiple_class.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('multiple_class.settings');
    $num = $form_state->get('num_items');
    $wrapper_id = 'addclass-fieldset-wrapper';
    $form['#tree'] = TRUE;

    if ($num === NULL) {
      $num = $config->get('add_class') ? count($config->get('add_class')) : 1;
      $form_state->set('num_items', $num);
    }

    $form['wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Class'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $form['wrapper']['add_class'] = [
      '#type' => 'fieldset',
    ];

    for ($i = 0; $i < $num; $i++) {
      $form['wrapper']['add_class'][$i] = [
        'extra_class' => [
         '#type' => 'textfield',
         '#default_value' => $config->get('add_class')[$i]['extra_class'] ?? NULL,
         '#maxlength' => 256,
          '#size' => 20,
        ],
      ];
    }

    $form['wrapper']['actions'] = [
      '#type' => 'actions',
    ];

    $form['wrapper']['actions']['add'] = [
     '#type' => 'submit',
      '#value' => $this->t('Add More'),
      '#submit' => [
        [self::class, 'addOne'],
      ],
      '#ajax' => [
        'callback' => [self::class, 'ajaxCallback'],
        'wrapper' => $wrapper_id,
      ],
    ];

    if ($num > 1) {
       $form['wrapper']['actions']['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
         '#submit' => [
            [self::class, 'removeOne'],
         ],
         '#ajax' => [
           'callback' => [self::class, 'ajaxCallback'],
           'wrapper' => $wrapper_id,
         ],
       ];
    }

    $form['specific_pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. An example path is %node-wildcard for every node page. %front is the front page.", [
        '%node-wildcard' => '/node/*',
        '%front' => '<front>',
      ]),
      '#default_value' => $config->get('specific_pages'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $specific_pages = $form_state->getValue('specific_pages');
    if (!empty($specific_pages)) {
      $get_pages = explode(PHP_EOL, $specific_pages);
      foreach ($get_pages as $values) {
        $url = explode('/', $values);
        if (!(str_starts_with($values, '/')) && ($values != '<front>')) {
          $form_state->setErrorByName('specific_pages', $this->t("@url path needs to start with a slash.", ['@url' => $url]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('multiple_class.settings');
    $config->set('specific_pages',  $form_state->getValue('specific_pages'));
    $config->set('add_class', $form_state->getValue('wrapper')['add_class'] ?? []);

    $config->save();

    parent::submitForm($form, $form_state);
  }

    /**
     * Callback for both ajax-enabled buttons.
     */
    public function ajaxCallback(array &$form, FormStateInterface $form_state) {
      return $form['wrapper'];
    }

    /**
     * Submit handler for the "add one" button.
     */
    public function addOne(array &$form, FormStateInterface $form_state) {
      $form_state->set('num_items', $form_state->get('num_items') + 1)->setRebuild();
    }

    /**
     * Submit handler for the "remove one" button.
     */
    public function removeOne(array &$form, FormStateInterface $form_state) {
      if ($form_state->get('num_items') > 1) {
        $form_state->set('num_items', $form_state->get('num_items') - 1)->setRebuild();
      }
    }
  }
