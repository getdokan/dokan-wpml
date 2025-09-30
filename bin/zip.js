const fs = require( 'fs-extra' );
const path = require( 'path' );
const { exec } = require( 'child_process' );
const chalk = require( 'chalk' );
const _ = require( 'lodash' );

const pluginFiles = [
    'languages/',
    'build',
    'changelog.txt',
    'readme.txt',
    'dokan-wpml.php',
    'wpml-config.xml',

];

const removeFiles = [ '.gitignore', '.svnignore', 'package-lock.json', 'package.json' ];

const allowedVendorFiles = {};

const { version } = JSON.parse( fs.readFileSync( 'package.json' ) );

exec(
    'rm -rf *',
    {
        cwd: 'dist',
    },
    ( error ) => {
        if ( error ) {
            console.log(
                chalk.yellow(
                    `⚠️ Could not find the build directory.`
                )
            );
            console.log(
                chalk.green(
                    `🗂 Creating the build directory ...`
                )
            );
            // Making build folder.
            fs.mkdirp( 'dist' );
        }

        const dest = 'dist/dokan-wpml'; // Temporary folder name after coping all the files here.
        fs.mkdirp( dest );

        console.log( `🗜 Started making the zip ...` );
        try {
            console.log( `⚙️ Copying plugin files ...` );

            // Coping all the files into build folder.
            pluginFiles.forEach( ( file ) => {
                fs.copySync( file, `${ dest }/${ file }` );
            } );
            console.log( `📂 Finished copying files.` );
        } catch ( err ) {
            console.error( chalk.red( '❌ Could not copy plugin files.' ), err );
            return;
        }

        // Removing files that is not needed in the production now.
        removeFiles.forEach( ( file ) => {
            fs.removeSync( `${ dest }/${ file }` );
        } );


        // Output zip file name.
        const zipFile = `dokan-wpml-v${ version }.zip`;

        console.log( `📦 Making the zip file ${ zipFile } ...` );

        // Making the zip file here.
        exec(
            `zip ${ zipFile } dokan-wpml -rq`,
            {
                cwd: 'dist'
            },
            ( error ) => {
                if ( error ) {
                    console.log(
                        chalk.red( `❌ Could not make ${ zipFile }.` )
                    );
                    console.log( chalk.bgRed.black( error ) );

                    return;
                }

                fs.removeSync( dest );
                console.log(
                    chalk.green( `✅  ${ zipFile } is ready. 🎉` )
                );
            }
        );
    }
);
