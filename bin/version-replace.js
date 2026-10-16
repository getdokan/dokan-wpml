const fs = require( 'fs' );
const path = require( 'path' );

/**
 * Replaces the DOKAN_WPML_SINCE docblock placeholder with the release
 * version from package.json. Runs as part of `npm run release`, before
 * zipping — mirrors dokan-lite's bin/version-replace.js.
 *
 * Note: mutates the listed files in place, so it is meant to run on a
 * release build/CI checkout, not a development working tree.
 */

const pluginFiles = [
    'dokan-wpml.php',
    'src',
];

const { version } = JSON.parse( fs.readFileSync( 'package.json' ) );

const replaceInFile = ( file ) => {
    const content = fs.readFileSync( file, 'utf8' );

    if ( ! content.includes( 'DOKAN_WPML_SINCE' ) ) {
        return;
    }

    fs.writeFileSync( file, content.replace( /DOKAN_WPML_SINCE/g, version ) );
    console.log( `✅ Replaced DOKAN_WPML_SINCE with ${ version } in ${ file }` );
};

const walk = ( target ) => {
    if ( ! fs.existsSync( target ) ) {
        return;
    }

    if ( fs.statSync( target ).isDirectory() ) {
        fs.readdirSync( target ).forEach( ( entry ) =>
            walk( path.join( target, entry ) )
        );
        return;
    }

    if ( /\.(php|js|jsx|ts|tsx)$/.test( target ) ) {
        replaceInFile( target );
    }
};

pluginFiles.forEach( walk );
