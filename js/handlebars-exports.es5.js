Handlebars = require("handlebars");
exports.Handlebars = Handlebars;

/**
 * Render Handlebars templates.
 *
 * @param {string} id
 *   The library id i.e "article.block.foo"
 *
 * @param {object} data
 *   The data.
 *
 * @returns {object}
 *   Returns the object containing the value and ellipsis information.
 */
function handlebarsRender(id, data) {
  const templates = (typeof window !== 'undefined')
    ? window.HandlebarsTemplates
    : HandlebarsTemplates;

  if (!templates || templates.length === 0) {
    // It was noticed that sometimes, after a cache clear the hook
    // handlebars_templates() doesn't get called for some reason.
    // Throwing an error to monitor this.
    throw new Error("No handlebars templates found on the page.");
  }

  // Register templates as Handlebars partials.
  Object.keys(HandlebarsTemplates).forEach(function setPartial(id, content) {
    // Check if the template id contains the word 'partial'.
    if (id.indexOf('partial') > -1) {
      Handlebars.registerPartial(id, HandlebarsTemplates[id]);
    }
  });

  // Get the source/pre-compiled template.
  const template = templates[id];
  if (typeof template !== 'string') {
    throw new Error("HandlebarJS template not found for id:." + id);
  }
  if (drupalSettings.Handlebars.compiledHandlebars) {
    return template(data);
  }

  // Compile source template.
  const render = Handlebars.compile(template);

  // Return rendered template using data provided.
  return render(data);
}

exports.render = function render(
  template,
  data
) {
  return handlebarsRender(template, data);
};

/**
 * Helpers
 */

/**
 * Register helper for string translations.
 * Limitation: Only supports @ filter and one argument.
 * @todo Find a way to support multiple arguments and other filters.
 * @todo Support contexts.
 */
Handlebars.registerHelper('t', (str, args, options) => {
  args = args.hash || {};
  options = options || {};

  // Add @ to each key.
  Object.keys(args).map((key) => {
    args[`@${key}`] = args[key];
  });

  return Drupal.t(str, args, options);
});

/**
 * Register helper render other templates.
 */
Handlebars.registerHelper('render', (template, data) => handlebarsRender(template, data));

/**
 * Register helpers for comparison i.e.
 *
 * {{#if (or
 *       (eq section1 "foo")
 *       (ne section2 "bar")
 *       (not section3))}}
 *   .. content
 * {{/if}}
 */
Handlebars.registerHelper({
  eq: (v1, v2) => v1 === v2,
  ne: (v1, v2) => v1 !== v2,
  lt: (v1, v2) => v1 < v2,
  gt: (v1, v2) => v1 > v2,
  lte: (v1, v2) => v1 <= v2,
  gte: (v1, v2) => v1 >= v2,
  and() {
    return Array.prototype.every.call(arguments, Boolean);
  },
  or() {
    return Array.prototype.slice.call(arguments, 0, -1).some(Boolean);
  },
  // Helper to implement logical not operator.
  not: v => !v
});

/**
 * Prepares a string for use as a CSS identifier (element, class, or ID name).
 * This is a copy of Drupal.cleanCssIdentifier();
 */
Handlebars.registerHelper('cleanCssIdentifier', (identifier) => {
  let cleanedIdentifier = identifier;

  // In order to keep '__' to stay '__' we first replace it with a different
  // placeholder after checking that it is not defined as a filter.
  cleanedIdentifier = cleanedIdentifier
    .replaceAll('__', '##')
    .replaceAll(' ', '-')
    .replaceAll('_', '-')
    .replaceAll('/', '-')
    .replaceAll('[', '-')
    .replaceAll(']', '')
    .replaceAll('##', '__');

  // Valid characters in a CSS identifier are:
  // - the hyphen (U+002D)
  // - a-z (U+0030 - U+0039)
  // - A-Z (U+0041 - U+005A)
  // - the underscore (U+005F)
  // - 0-9 (U+0061 - U+007A)
  // - ISO 10646 characters U+00A1 and higher
  // We strip out any character not in the above list.
  cleanedIdentifier = cleanedIdentifier.replaceAll(/[^\u{002D}\u{0030}-\u{0039}\u{0041}-\u{005A}\u{005F}\u{0061}-\u{007A}\u{00A1}-\u{FFFF}]/gu, '');

  // Identifiers cannot start with a digit, two hyphens, or a hyphen followed by a digit.
  cleanedIdentifier = cleanedIdentifier.replace(/^[0-9]/, '_').replace(/^(-[0-9])|^(--)/, '__');

  return cleanedIdentifier.toLowerCase();
});

/**
 * Creates variables on the fly.
 * Usage:
 *  - First set foo with value bar: {{set 'foo' 'bar'}}
 *  - Then you can print foo: {{@root.foo}}
 */
Handlebars.registerHelper('set', function(name, val, globals) {
  globals.data.root[name] = val;
});

/**
 * Helps to prepare class value.
 * Usage:
 *  - {{ addClass 'foo' 'hide' }}
 *  - {{ addClass 'foo' 'baz' }}
 *  - Now {{ @root.foo }} will print 'hide baz'.
 */
Handlebars.registerHelper('addClass', function () {
  var args = [].concat.apply([], arguments);
  var classVar = args[0];
  var globals = args.pop();

  if (typeof globals.data.root[classVar] === 'undefined') {
    globals.data.root[classVar] = '';
  }

  globals.data.root[classVar] = globals.data.root[classVar] === ''
    ? args[1]
    : ' ' + args[1];
});

/**
 * Helps to prepare style value.
 * Usage:
 *  - {{ addStyle 'foo' '' }}
 *  - Now {{ @root.foo }} will print '';
 *  - {{ addStyle 'foo' 'color' 'red'}}
 *  - Now {{ @root.foo }} will print 'color:red;'.
 */
Handlebars.registerHelper('addStyle', function () {
  var args = [].concat.apply([], arguments);
  var styleVar = args[0];
  var globals = args.pop();

  // Set the style variable if it is not yet defined.
  if (typeof globals.data.root[styleVar] === 'undefined') {
    globals.data.root[styleVar] = '';
  }

  if (arguments[1] === '') {
    globals.data.root[styleVar] = '';
  } else if (typeof arguments[2] !== 'undefined') {
    globals.data.root[styleVar] = globals.data.root[styleVar] + arguments[1] + ':' + arguments[2] + ';';
  } else {
    globals.data.root[styleVar] = globals.data.root[styleVar] + arguments[1];
  }
});
