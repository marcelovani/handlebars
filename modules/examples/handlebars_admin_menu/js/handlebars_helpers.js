if (typeof Handlebars === 'object') {
    // Creates Ids from string.
    Handlebars.registerHelper('id', (identifier) => {
        let cleanedIdentifier = identifier;

        // In order to keep '__' to stay '__' we first replace it with a different
        // placeholder after checking that it is not defined as a filter.
        cleanedIdentifier = cleanedIdentifier
            .replaceAll('__', '##')
            .replaceAll(' ', '-')
            .replaceAll('.', '-')
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
}
