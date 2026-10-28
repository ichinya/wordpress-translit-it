<?php
/** Build a WordPress installation ZIP without development files. */
if ( 'cli' !== PHP_SAPI ) {
    exit;
}

$root = dirname( __DIR__ );
preg_match( '/^ \* Version:\s*(\S+)/m', file_get_contents( $root . '/index.php' ), $matches );
if ( empty( $matches[1] ) || ! class_exists( 'ZipArchive' ) ) {
    throw new RuntimeException( 'A plugin version and the PHP zip extension are required.' );
}
$output = $argv[1] ?? $root . '/dist/translit-it-' . $matches[1] . '.zip';
if ( ! is_dir( dirname( $output ) ) && ! mkdir( dirname( $output ), 0775, true ) ) {
    throw new RuntimeException( 'Cannot create the output directory.' );
}
$files = array( 'index.php', 'readme.txt', 'LICENSE.txt' );
foreach ( array( 'includes', 'assets', 'languages' ) as $directory ) {
    $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $directory, FilesystemIterator::SKIP_DOTS ) );
    foreach ( $iterator as $file ) {
        if ( $file->isFile() ) {
            $files[] = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
        }
    }
}
sort( $files );
$zip = new ZipArchive();
if ( true !== $zip->open( $output, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
    throw new RuntimeException( 'Cannot open the release archive.' );
}
foreach ( $files as $file ) {
    if ( ! $zip->addFile( $root . '/' . $file, 'translit-it/' . $file ) ) {
        throw new RuntimeException( 'Cannot add a release file.' );
    }
}
if ( ! $zip->close() ) {
    throw new RuntimeException( 'Cannot finish the release archive.' );
}
echo $output . PHP_EOL;
