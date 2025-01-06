<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit878c21d6f548cbb81f126f5033241491
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit878c21d6f548cbb81f126f5033241491', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit878c21d6f548cbb81f126f5033241491', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit878c21d6f548cbb81f126f5033241491::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
