Mbn (Multi-byte number) Library

Library for PHP and JS to do calculations with any precision and correct (half-up/away-from-zero) rounding

Project page: https://mbn.li

Available for PHP Composer: https://packagist.org/packages/mblajek/mbn
Available for JS npm: https://www.npmjs.com/package/mblajek-mbn

Repository contains
    library files
    home page
    simple PWA calculator
    dockerfiles with supported environments
    and some tools for:
        updating page from GitHub
        testing php and js versions
        preparing library files to download

Page needs env interface in env.php file:

interface env {
    const ssl = false; // redirect to ssl
    const docker = true; // docker environment for testing all versions from docker-compose
    // url to GitHub repository zip download
    const githubZip = 'https://github.com/mblajek/Mbn/archive/refs/heads/master.zip';
}

Code is optimized for speed and size; not for readability
Library is PHP 5.4 compatible, so entire repository is limited to PHP 5.4
