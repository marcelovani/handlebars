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
function hook_handlebars_templates(\Drupal\Core\Entity\EntityInterface $entity) {
  // List of handlebars libraries to be attached to the entity.
  return [
    'article.block.foo' => 'my_module_name',
  ];
}
```

The libraries are defined like you would do for a Js library but you will provide
the path for the Handlebars templates. **Make sure that handlebar template name is _exactly same_ as the library name.**

example.libraries.yml
```
article.block.foo:
  version: 1.x
  js:
    handlebars/article.block.foo.handlebars: { }
```

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
