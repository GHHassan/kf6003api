<?php

/**
 * Autoloader class
 * 
 * This class is responsible for including all the classes 
 * in the "src" folder as and when they are needed.
 * The methods of this class are called in the "api.php" file only once.
 * It assumes that all the classes are in the "src" folder 
 * and the files are named the same as the class name.
 * The abstract constructor makes sure that the class is not
 * instantiated.
 * 
 * @param string $className The name of the class to be loaded.
 * @throws \Exception Throws an exception if the file is not found or not readable.
 * 
 * @author G H Hassani <W20017074@northumbria.ac.uk>
 */
class Autoloader
{
    
    static function autoload($className)
    {
        $filename = $className . ".php";
        $filename = str_replace('\\', DIRECTORY_SEPARATOR, $filename);

        if (!is_readable($filename)) {
            throw new \Exception("File $filename not found");
        } 

        include_once $filename;
    }

    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }
}
