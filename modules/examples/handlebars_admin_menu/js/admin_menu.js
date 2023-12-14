/**
 * @file
 * JS code for decoupled menus rendered with Handlebars.
 */

(function ($) {
    if (typeof Handlebars !== 'object') {
        return;
    }

    // Get admin menu.
    $.get('/api/menu_items/admin', function (data) {
        // Create a new div and set
        var newDiv = document.createElement('div');

        // Render Handlebars template.
        newDiv.innerHTML = handlebarsRenderer.render('menu', data[0]);

        // Append new div after existing div.
        var el = document.querySelector('#toolbar-item-administration-tray');
        el.append(newDiv);
    });
})(jQuery);
