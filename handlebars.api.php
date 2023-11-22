<?php

/**
 * @file
 * Hooks specific to the handlebars module.
 */

/**
 * Allows modules to define their own handlebars templates.
 *
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being processed.
 *
 * @return array
 *   The list of libraries.
 */
function hook_handlebars_templates(\Drupal\Core\Entity\EntityInterface $entity) {
  // List of handlebars libraries to be attached to the entity.
  return [
    'article.block.foo' => 'my_module_name',
  ];
}

/**
 * Allows a different module to override the original template.
 *
 * @param array $templates
 *   The array of templates to alter.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity being processed.
 */
function hook_handlebars_templates_alter(array &$templates, \Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity->bundle() !== 'page') {
    return;
  }

  $templates['article.block.foo'] = 'my_module_name';
}
