# Handlebars
Provides integration with Handlebars (https://handlebarsjs.com/)

### Installation
Install the module as usual

### Using it
When you enable the module, nothing happens by default, until you define the
Handlebars templates, by defining them as Drupal libraries.

The libraries are defined like you would do for a Js library but you will provide
the path for the Handlebars templates. **Make sure that handlebar template name
is _exactly same_ as the library name.** @todo Check if we can remove or automate this condition

my_module.libraries.yml
```
article.block.foo:
  version: 1.x
  # Specify that this library is a Handlebars template.
  type: handlebars_template
  js:
    # Specify the location of the template.
    templates/article.block.foo.handlebars: { }
```

The templates need to have the Handlebars syntax, see https://handlebarsjs.com/guide/

Example of `templates/article.block.foo.handlebars`
```
<h1>Here is the content of foo: {{ foo }}</h1>
```

The next step is to call the renderer, passing the path to the object and data i.e.

```javascript
// Creating the data object, the data source can be json from a REST endpoint. 
let data = {
  foo: 'bar'
};

// Render the Handlebars template using the data.
var html = handlebarsRenderer.render('article.block.foo', data);

// Update the container with the rendered content.
var el = document.getElementsByID("container");
container.innerHTML = html;
```

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

Will output
```
<div>
  Hello <span>World</span>
</div>
```

# Troubleshooting
- How do I know what variables are available to use in a Handlebars template?
  - You can use `{{log this }}` to list all variables in the Console.

# Todo
- Handlebars: Twig for Js
- Test if partials are working.
- Test aggregated js
- Add Loaders
- Document how to create new Helpers
- Document how to alter and compile js
- Add test cases
- Provide use cases
- Add Example modules - connecting to local or remote js i.e. Amazon or Algolia
  - https://paramountshop.com/collections/all.atom
  - https://colourpop.com/collections/all.atom
  - https://sewingmachinecentre.nz/collections/all.atom
  - https://wisepops.com/blog/shopify-stores
