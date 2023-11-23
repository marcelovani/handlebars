# Handlebars
Provides integration with Handlebars (https://handlebarsjs.com/)

### Installation
Install the module as usual

### Using it
When you enable the module, nothing happens by default. Other modules need to
implement hook_handlebars_templates() or hook_handlebars_templates_alter()
to attach libraries to the entity, see handlebars.api.php. i.e.

```php
/**
 * Implements hook_handlebars_templates().
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
```

The libraries are defined like you would do for a Js library but you will provide
the path for the Handlebars templates. **Make sure that handlebar template name is _exactly same_ as the library name.**

example.libraries.yml
```
article.block.foo:
  version: 1.x
  js:
    `templates/article.block.foo.handlebars: { }
```
@todo add details about the contents of templates/article.block.foo.handlebars.

The next step is to call the renderer, passing the path to the object and data i.e.

```javascript
let data = {
  foo: 'bar'
};

let html = handlebarsRenderer.render('article.block.foo', data);
```

The templates need to have the Handlebars syntax, see https://handlebarsjs.com/guide/

### Partials
It is possible to render other Handlebars templates using Handlebars partials, see
Docs https://handlebarsjs.com/api-reference/runtime.html#handlebars-registerpartial-name-partial.

Handlebars will register partials automatically when the library name contains the string 'partial'
and can be used inside templates:

i.e. hello_world.handlebars
```
<div>
  Hello {{> my_partial value='World' }}
</div>
```

i.e. my_partial.handlebars
```
<span>{{ value }}</span>
```

# Troubleshooting
- How do I know what variables are available to use in a Handlebars template?
  - You can use `{{log this }}` to list all variables in the Console.

# Todo
- Handlebars  - Twig for Js
- Add Loades
- Provide use cases
- Document Helpers shipped with the module
- Document how to create new Helpers
- Document how to alter and compile js
- Add Example modules - connecting to local or remote js i.e. Amazon or Algolia
  - https://paramountshop.com/collections/all.atom
  - https://colourpop.com/collections/all.atom
  - https://sewingmachinecentre.nz/collections/all.atom
  - https://wisepops.com/blog/shopify-stores
- Find a way to automate the library association without hook_handlebars_templates()
- Explain how to use compiled version of handlebars
-
