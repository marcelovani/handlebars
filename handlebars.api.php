<?php

/**
 * @file
 * Hooks specific to the handlebars module.
 */

/**
 * Allows modules to define their own handlebars templates.
 * Can be used to control whether to attach templates depending on the context, i.e. entity bundle.
 *
 * @param $context
 *   The context.
 *
 * @return array
 *   The list of libraries.
 */
function hook_handlebars_templates($context) {
  // List of handlebars libraries to be attached to the entity based on context.
  if ($context instanceof \Drupal\Core\Entity\EntityInterface && $context->bundle() !== 'page') {
    return [
      'article.block.foo' => 'my_module_name',
    ];
  }

  return [];
}

/**
 * Allows a different module to override the original template.
 * Can be used to control whether to attach templates depending on the context, i.e. entity bundle.
 *
 * @param array $templates
 *   The array of templates to alter.
 * @param $context
 *   The context.
 */
function hook_handlebars_templates_alter(array &$templates, $context) {
  if ($context instanceof \Drupal\Core\Entity\EntityInterface && $context->bundle() !== 'page') {
    return;
  }

  $templates['article.block.foo'] = 'my_module_name';
}
